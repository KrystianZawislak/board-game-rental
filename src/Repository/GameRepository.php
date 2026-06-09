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
     * Wszystkie gry wraz z rezerwacjami (eager load), żeby sprawdzanie dostępności
     * w widoku nie generowało osobnego zapytania na każdą grę (uniknięcie N+1).
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
