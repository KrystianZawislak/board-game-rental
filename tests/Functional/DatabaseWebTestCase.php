<?php

namespace App\Tests\Functional;

use App\Entity\Game;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\GameCategory;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Baza dla testów funkcjonalnych: świeży schemat bazy na każdy test (izolacja)
 * + drobne fabryki encji. Wymaga istniejącej bazy `*_test` (patrz README).
 */
abstract class DatabaseWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $tool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    /**
     * @param list<string> $roles
     */
    protected function createUser(string $email, array $roles): User
    {
        $user = (new User())->setEmail($email)->setRoles($roles);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, 'pass1234'));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createGame(string $title = 'Test Game'): Game
    {
        $game = (new Game())
            ->setTitle($title)
            ->setDescription('Opis testowy gry.')
            ->setCategory(GameCategory::STRATEGY)
            ->setMinPlayers(2)
            ->setMaxPlayers(4)
            ->setPlayingTime(60)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($game);
        $this->em->flush();

        return $game;
    }

    protected function createReservation(Game $game, ReservationStatus $status, string $start, string $end): Reservation
    {
        $reservation = (new Reservation())
            ->setGuestName('Anna Nowak')
            ->setGuestPhone('600700800')
            ->setStartDate(new \DateTimeImmutable($start))
            ->setEndDate(new \DateTimeImmutable($end))
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setGame($game);

        $this->em->persist($reservation);
        $this->em->flush();

        return $reservation;
    }
}
