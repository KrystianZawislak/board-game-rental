<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Czy podany termin koliduje z inną aktywną rezerwacją tej gry.
     * Kolizja = istniejący.start <= nowy.koniec ORAZ istniejący.koniec >= nowy.start.
     * Rezerwacje zwrócone (RETURNED) nie blokują.
     */
    public function overlaps(Game $game, \DateTimeImmutable $start, \DateTimeImmutable $end): bool
    {
        $count = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.game = :game')->setParameter('game', $game)
            ->andWhere('r.status != :returned')->setParameter('returned', ReservationStatus::RETURNED)
            ->andWhere('r.startDate <= :end AND r.endDate >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Aktywne rezerwacje do obsługi w panelu (bez zwróconych), z dołączoną grą, posortowane wg terminu.
     *
     * @return Reservation[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status != :returned')->setParameter('returned', ReservationStatus::RETURNED)
            ->leftJoin('r.game', 'g')->addSelect('g')
            ->orderBy('r.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
