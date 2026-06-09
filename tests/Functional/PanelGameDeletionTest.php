<?php

namespace App\Tests\Functional;

use App\Entity\Game;
use App\Enum\ReservationStatus;

/**
 * Trwałe usunięcie gry: tylko manager, i tylko gdy gra nie ma aktywnej rezerwacji.
 */
final class PanelGameDeletionTest extends DatabaseWebTestCase
{
    public function testStaffCannotDeleteGame(): void
    {
        $game = $this->createGame();
        $this->client->loginUser($this->createUser('staff@pionek.test', ['ROLE_STAFF']));

        // IsGranted(ROLE_MANAGER) odrzuca zanim akcja w ogóle ruszy — 403
        $this->client->request('POST', '/panel/games/'.$game->getId().'/delete');

        self::assertResponseStatusCodeSame(403);
        self::assertNotNull($this->em->getRepository(Game::class)->find($game->getId()));
    }

    public function testManagerDeletesGameWithoutReservations(): void
    {
        $game = $this->createGame();
        $this->client->loginUser($this->createUser('manager@pionek.test', ['ROLE_MANAGER']));

        $crawler = $this->client->request('GET', '/panel/games');
        $this->client->submit($crawler->selectButton('Usuń')->form());

        self::assertResponseRedirects('/panel/games');
        self::assertNull($this->em->getRepository(Game::class)->find($game->getId()));
    }

    public function testManagerCannotDeleteGameWithActiveReservation(): void
    {
        $game = $this->createGame();
        $this->createReservation($game, ReservationStatus::PENDING, '+3 days', '+6 days');
        $this->client->loginUser($this->createUser('manager@pionek.test', ['ROLE_MANAGER']));

        $crawler = $this->client->request('GET', '/panel/games');
        $this->client->submit($crawler->selectButton('Usuń')->form());

        self::assertResponseRedirects('/panel/games');
        self::assertNotNull($this->em->getRepository(Game::class)->find($game->getId()));
    }
}
