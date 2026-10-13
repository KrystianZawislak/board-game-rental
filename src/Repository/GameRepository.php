<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * All games with reservations eager-loaded, so availability checks in the view
     * don't trigger a query per game (avoids N+1).
     *
     * @return Game[]
     */
    public function findAllWithReservations(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.reservations', 'r')->addSelect('r')
            ->orderBy('g.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
