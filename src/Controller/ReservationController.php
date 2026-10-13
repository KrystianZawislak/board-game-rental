<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class ReservationController extends AbstractController
{
    #[Route('/games/{id}/reserve', name: 'app_reservation_new', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Game $game,
        Request $request,
        EntityManagerInterface $em,
        ReservationRepository $reservations,
        RateLimiterFactory $reservationLimiter,
    ): Response {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$reservationLimiter->create($request->getClientIp() ?? '')->consume()->isAccepted()) {
                // too many requests from one IP (anti-spam)
                $form->addError(new FormError('Zbyt wiele prób rezerwacji z tego adresu. Spróbuj ponownie za chwilę.'));
            } elseif ($form->isValid()) {
                // Lock the game row (SELECT ... FOR UPDATE) to serialize concurrent bookings of the same
                // game; otherwise two requests could both pass the overlap check (TOCTOU).
                $taken = $em->wrapInTransaction(function () use ($em, $game, $reservation, $reservations): bool {
                    $em->lock($game, LockMode::PESSIMISTIC_WRITE);

                    if ($reservations->overlaps($game, $reservation->getStartDate(), $reservation->getEndDate())) {
                        return true;
                    }

                    // server-controlled values, intentionally not taken from the form
                    $reservation->setGame($game);
                    $reservation->setStatus(ReservationStatus::PENDING);
                    $reservation->setCreatedAt(new \DateTimeImmutable());
                    $em->persist($reservation);

                    return false;
                });

                if ($taken) {
                    $form->addError(new FormError('Wybrany termin jest już zajęty — wybierz inny.'));
                } else {
                    $this->addFlash('success', 'Zgłoszenie rezerwacji przyjęte — skontaktujemy się z Tobą.');

                    return $this->redirectToRoute('app_game_show', ['id' => $game->getId()]);
                }
            }
        }

        return $this->render('reservation/new.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }
}
