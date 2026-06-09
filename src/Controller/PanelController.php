<?php

namespace App\Controller;

use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panel')]
#[IsGranted('ROLE_STAFF')]
final class PanelController extends AbstractController
{
    #[Route('', name: 'app_panel', methods: ['GET'])]
    public function dashboard(ReservationRepository $reservations): Response
    {
        return $this->render('panel/dashboard.html.twig', [
            'issuedCount' => $reservations->count(['status' => ReservationStatus::ISSUED]),
            'pendingCount' => $reservations->count(['status' => ReservationStatus::PENDING]),
            'confirmedCount' => $reservations->count(['status' => ReservationStatus::CONFIRMED]),
            'pending' => $reservations->findBy(['status' => ReservationStatus::PENDING], ['startDate' => 'ASC']),
        ]);
    }
}
