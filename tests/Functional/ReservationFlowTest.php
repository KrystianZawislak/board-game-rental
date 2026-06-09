<?php

namespace App\Tests\Functional;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;

/**
 * Zgłaszanie rezerwacji przez gościa: status narzucony serwerowo (PENDING),
 * a termin kolidujący z istniejącą rezerwacją jest odrzucany.
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
        // status zawsze PENDING — gość nie może go ustawić z formularza
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

        // zostajemy na stronie z błędem, nowa rezerwacja nie powstaje
        self::assertSelectorTextContains('.reserve__form', 'zajęty');
        self::assertCount(1, $this->em->getRepository(Reservation::class)->findAll());
    }
}
