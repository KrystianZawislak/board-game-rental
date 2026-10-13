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
     * Whether the given period overlaps another active reservation of this game
     * (existing.start <= new.end AND existing.end >= new.start). RETURNED reservations don't block.
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
     * Active (non-returned) reservations for the staff panel, with the game joined, ordered by date.
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
