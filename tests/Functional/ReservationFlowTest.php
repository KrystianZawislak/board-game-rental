<?php

namespace App\Tests\Functional;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;

/**
 * Guest reservation flow: status is forced to PENDING server-side,
 * and overlapping dates are rejected.
 */
final class ReservationFlowTest extends DatabaseWebTestCase
{
    public function testGuestReservationIsAcceptedAndForcedToPending(): void
    {
        $game = $this->createGame();

        $crawler = $this->client->request('GET', '/games/'.$game->getId().'/reserve');
        $this->client->submit($crawler->selectButton('Wyślij zgłoszenie')->form([
            'reservation[guestName]' => 'Jan Kowalski',
            'reservation[guestPhone]' => '123456789',
            'reservation[startDate]' => (new \DateTimeImmutable('+2 days'))->format('Y-m-d'),
            'reservation[endDate]' => (new \DateTimeImmutable('+4 days'))->format('Y-m-d'),
        ]));

        self::assertResponseRedirects('/games/'.$game->getId());

        $reservations = $this->em->getRepository(Reservation::class)->findAll();
        self::assertCount(1, $reservations);
        // always PENDING; a guest can't set it via the form
        self::assertSame(ReservationStatus::PENDING, $reservations[0]->getStatus());
    }

    public function testOverlappingReservationIsRejected(): void
    {
        $game = $this->createGame();
        $this->createReservation($game, ReservationStatus::CONFIRMED, '+3 days', '+6 days');

        $crawler = $this->client->request('GET', '/games/'.$game->getId().'/reserve');
        $this->client->submit($crawler->selectButton('Wyślij zgłoszenie')->form([
            'reservation[guestName]' => 'Jan Kowalski',
            'reservation[guestPhone]' => '123456789',
            'reservation[startDate]' => (new \DateTimeImmutable('+4 days'))->format('Y-m-d'),
            'reservation[endDate]' => (new \DateTimeImmutable('+5 days'))->format('Y-m-d'),
        ]));

        // stays on the page with an error, no reservation is created
        self::assertSelectorTextContains('.reserve__form', 'zajęty');
        self::assertCount(1, $this->em->getRepository(Reservation::class)->findAll());
    }
}
