<?php

namespace App\Controller\Panel;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panel/reservations')]
#[IsGranted('ROLE_STAFF')]
final class ReservationController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $auditLogger,
    ) {
    }

    #[Route('', name: 'app_panel_reservations', methods: ['GET'])]
    public function index(ReservationRepository $reservations): Response
    {
        return $this->render('panel/reservations.html.twig', [
            'reservations' => $reservations->findActive(),
        ]);
    }

    #[Route('/{id}/confirm', name: 'app_panel_reservation_confirm', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function confirm(Reservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        return $this->transition($reservation, $request, $em, ReservationStatus::PENDING, ReservationStatus::CONFIRMED, 'Rezerwacja potwierdzona.');
    }

    #[Route('/{id}/reject', name: 'app_panel_reservation_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reject(Reservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        return $this->transition($reservation, $request, $em, ReservationStatus::PENDING, ReservationStatus::RETURNED, 'Rezerwacja odrzucona.');
    }

    #[Route('/{id}/issue', name: 'app_panel_reservation_issue', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function issue(Reservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        return $this->transition($reservation, $request, $em, ReservationStatus::CONFIRMED, ReservationStatus::ISSUED, 'Gra oznaczona jako wydana.');
    }

    #[Route('/{id}/return', name: 'app_panel_reservation_return', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function markReturned(Reservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        return $this->transition($reservation, $request, $em, ReservationStatus::ISSUED, ReservationStatus::RETURNED, 'Gra oznaczona jako zwrócona.');
    }

    private function transition(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $em,
        ReservationStatus $from,
        ReservationStatus $to,
        string $message,
    ): Response {
        if (!$this->isCsrfTokenValid('panel-reservation', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Nieprawidłowy token CSRF.');
        }

        if ($reservation->getStatus() !== $from) {
            $this->addFlash('warning', 'Tej operacji nie można wykonać w obecnym stanie rezerwacji.');
        } else {
            $reservation->setStatus($to);
            $em->flush();

            $this->auditLogger->info('Zmiana stanu rezerwacji', [
                'reservationId' => $reservation->getId(),
                'from' => $from->value,
                'to' => $to->value,
                'by' => $this->getUser()?->getUserIdentifier(),
            ]);

            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_panel_reservations');
    }
}
