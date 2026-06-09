<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Reservation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Walidacja zgłoszenia rezerwacji (Asserty + Assert\Callback na zakresie dat).
 * Sam Validator, bez bazy.
 */
final class ReservationValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidReservationPasses(): void
    {
        self::assertSame([], $this->violatedPaths($this->validReservation()));
    }

    public function testBlankGuestNameIsRejected(): void
    {
        $reservation = $this->validReservation()->setGuestName('');

        self::assertContains('guestName', $this->violatedPaths($reservation));
    }

    public function testGuestNameWithDigitsIsRejected(): void
    {
        $reservation = $this->validReservation()->setGuestName('Jan 123');

        self::assertContains('guestName', $this->violatedPaths($reservation));
    }

    public function testInvalidPhoneIsRejected(): void
    {
        $reservation = $this->validReservation()->setGuestPhone('abc');

        self::assertContains('guestPhone', $this->violatedPaths($reservation));
    }

    public function testStartDateInThePastIsRejected(): void
    {
        $reservation = $this->validReservation()->setStartDate(new \DateTimeImmutable('-1 day'));

        self::assertContains('startDate', $this->violatedPaths($reservation));
    }

    public function testEndDateBeforeStartDateIsRejected(): void
    {
        $reservation = $this->validReservation()
            ->setStartDate(new \DateTimeImmutable('+5 days'))
            ->setEndDate(new \DateTimeImmutable('+2 days'));

        // łapane przez Assert\Callback validateDateRange()
        self::assertContains('endDate', $this->violatedPaths($reservation));
    }

    private function validReservation(): Reservation
    {
        return (new Reservation())
            ->setGuestName('Jan Kowalski')
            ->setGuestPhone('123456789')
            ->setStartDate(new \DateTimeImmutable('today'))
            ->setEndDate(new \DateTimeImmutable('+2 days'));
    }

    /**
     * @return list<string>
     */
    private function violatedPaths(Reservation $reservation): array
    {
        $paths = [];
        foreach ($this->validator->validate($reservation) as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        return $paths;
    }
}
