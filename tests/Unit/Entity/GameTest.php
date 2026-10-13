<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Game;
use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use PHPUnit\Framework\TestCase;

/**
 * Availability is calendar-based: "available today" depends on status and whether
 * the reservation period covers today. Pure entity logic, no database.
 */
final class GameTest extends TestCase
{
    public function testAvailableWhenNoReservations(): void
    {
        self::assertTrue((new Game())->isCurrentlyAvailable());
    }

    public function testIssuedReservationMakesItUnavailable(): void
    {
        // issued and not returned: physically gone regardless of dates
        $game = $this->gameWith($this->reservation('-10 days', '-5 days', ReservationStatus::ISSUED));

        self::assertFalse($game->isCurrentlyAvailable());
    }

    public function testReservationCoveringTodayBlocksToday(): void
    {
        $game = $this->gameWith($this->reservation('-1 day', '+1 day', ReservationStatus::CONFIRMED));

        self::assertFalse($game->isCurrentlyAvailable());
    }

    public function testFutureReservationDoesNotBlockToday(): void
    {
        $game = $this->gameWith($this->reservation('+5 days', '+10 days', ReservationStatus::PENDING));

        self::assertTrue($game->isCurrentlyAvailable());
    }

    public function testReturnedReservationNeverBlocks(): void
    {
        $game = $this->gameWith($this->reservation('-1 day', '+1 day', ReservationStatus::RETURNED));

        self::assertTrue($game->isCurrentlyAvailable());
    }

    public function testHasActiveReservationsIgnoresReturned(): void
    {
        $game = $this->gameWith($this->reservation('-10 days', '-5 days', ReservationStatus::RETURNED));
        self::assertFalse($game->hasActiveReservations());

        $game->addReservation($this->reservation('+1 day', '+2 days', ReservationStatus::PENDING));
        self::assertTrue($game->hasActiveReservations());
    }

    public function testUpcomingReservationsExcludeReturnedAndPastAndAreSortedByStart(): void
    {
        $past = $this->reservation('-10 days', '-5 days', ReservationStatus::CONFIRMED);
        $returned = $this->reservation('+1 day', '+2 days', ReservationStatus::RETURNED);
        $later = $this->reservation('+10 days', '+12 days', ReservationStatus::PENDING);
        $sooner = $this->reservation('+3 days', '+4 days', ReservationStatus::CONFIRMED);

        $game = $this->gameWith($past, $returned, $later, $sooner);

        // only active, not yet ended, soonest first
        self::assertSame([$sooner, $later], $game->getUpcomingReservations());
    }

    private function gameWith(Reservation ...$reservations): Game
    {
        $game = new Game();
        foreach ($reservations as $reservation) {
            $game->addReservation($reservation);
        }

        return $game;
    }

    private function reservation(string $start, string $end, ReservationStatus $status): Reservation
    {
        return (new Reservation())
            ->setStartDate(new \DateTimeImmutable($start))
            ->setEndDate(new \DateTimeImmutable($end))
            ->setStatus($status);
    }
}
