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
use Symfony\Component\Routing\Attribute\Route;

final class ReservationController extends AbstractController
{
    #[Route('/games/{id}/reserve', name: 'app_reservation_new', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Game $game,
        Request $request,
        EntityManagerInterface $em,
        ReservationRepository $reservations,
    ): Response {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Blokada wiersza gry (SELECT ... FOR UPDATE) serializuje równoległe rezerwacje tej samej
            // gry: bez niej dwa zgłoszenia mogłyby oba przejść sprawdzenie kolizji i oba się zapisać (TOCTOU).
            $taken = $em->wrapInTransaction(function () use ($em, $game, $reservation, $reservations): bool {
                $em->lock($game, LockMode::PESSIMISTIC_WRITE);

                if ($reservations->overlaps($game, $reservation->getStartDate(), $reservation->getEndDate())) {
                    return true;
                }

                // wartości narzucone serwerowo — celowo NIE pochodzą z formularza
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

        return $this->render('reservation/new.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }
}
