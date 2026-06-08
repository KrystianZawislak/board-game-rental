<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\User;
use App\Enum\GameCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DESCRIPTION = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. '
        .'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. '
        .'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.';

    /**
     * Tytuł, kategoria, min graczy, max graczy, czas gry (min).
     *
     * @var list<array{0: string, 1: GameCategory, 2: int, 3: int, 4: int}>
     */
    private const GAMES = [
        ['Carcassonne', GameCategory::STRATEGY, 2, 5, 45],
        ['Scythe', GameCategory::STRATEGY, 1, 5, 115],
        ['Terra Mystica', GameCategory::STRATEGY, 2, 5, 150],
        ['Root', GameCategory::STRATEGY, 2, 4, 90],
        ['Concordia', GameCategory::STRATEGY, 2, 5, 100],
        ['El Grande', GameCategory::STRATEGY, 2, 5, 90],
        ['Twilight Imperium', GameCategory::STRATEGY, 3, 6, 480],
        ['Tigris & Euphrates', GameCategory::STRATEGY, 2, 4, 90],

        ['Ticket to Ride', GameCategory::FAMILY, 2, 5, 60],
        ['Azul', GameCategory::FAMILY, 2, 4, 40],
        ['Kingdomino', GameCategory::FAMILY, 2, 4, 20],
        ['Qwirkle', GameCategory::FAMILY, 2, 4, 45],
        ['Labirynt', GameCategory::FAMILY, 2, 4, 30],
        ['Camel Up', GameCategory::FAMILY, 2, 8, 40],
        ['Patchwork', GameCategory::FAMILY, 2, 2, 30],
        ['Karuba', GameCategory::FAMILY, 2, 4, 40],

        ['Codenames', GameCategory::PARTY, 4, 8, 15],
        ['Dixit', GameCategory::PARTY, 3, 6, 30],
        ['Just One', GameCategory::PARTY, 3, 7, 20],
        ['Telestrations', GameCategory::PARTY, 4, 8, 30],
        ['Wavelength', GameCategory::PARTY, 2, 12, 45],
        ['Skull', GameCategory::PARTY, 3, 6, 30],
        ['Concept', GameCategory::PARTY, 4, 12, 40],
        ["Time's Up!", GameCategory::PARTY, 4, 12, 60],

        ['Sushi Go!', GameCategory::CARD, 2, 5, 15],
        ['Love Letter', GameCategory::CARD, 2, 4, 20],
        ['Exploding Kittens', GameCategory::CARD, 2, 5, 15],
        ['The Mind', GameCategory::CARD, 2, 4, 20],
        ['Dominion', GameCategory::CARD, 2, 4, 30],
        ['Star Realms', GameCategory::CARD, 2, 2, 20],
        ['Munchkin', GameCategory::CARD, 3, 6, 90],
        ['Uno', GameCategory::CARD, 2, 10, 30],

        ['Pandemic', GameCategory::COOP, 2, 4, 45],
        ['Forbidden Island', GameCategory::COOP, 2, 4, 30],
        ['Hanabi', GameCategory::COOP, 2, 5, 25],
        ['Spirit Island', GameCategory::COOP, 1, 4, 120],
        ['The Crew', GameCategory::COOP, 2, 5, 20],
        ['Mysterium', GameCategory::COOP, 2, 7, 45],
        ['Forbidden Desert', GameCategory::COOP, 2, 5, 45],
        ['Zombicide', GameCategory::COOP, 1, 6, 60],

        ['Osadnicy z Catanu', GameCategory::ECONOMIC, 3, 4, 90],
        ['Power Grid', GameCategory::ECONOMIC, 2, 6, 120],
        ['Brass: Birmingham', GameCategory::ECONOMIC, 2, 4, 120],
        ['Acquire', GameCategory::ECONOMIC, 2, 6, 90],
        ['Splendor', GameCategory::ECONOMIC, 2, 4, 30],
        ['Puerto Rico', GameCategory::ECONOMIC, 3, 5, 90],
        ['Terraforming Mars', GameCategory::ECONOMIC, 1, 5, 120],
        ['Le Havre', GameCategory::ECONOMIC, 1, 5, 150],
        ['Monopoly', GameCategory::ECONOMIC, 2, 8, 120],
        ['Food Chain Magnate', GameCategory::ECONOMIC, 2, 5, 180],
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::GAMES as [$title, $category, $minPlayers, $maxPlayers, $playingTime]) {
            $game = (new Game())
                ->setTitle($title)
                ->setDescription(self::DESCRIPTION)
                ->setCategory($category)
                ->setMinPlayers($minPlayers)
                ->setMaxPlayers($maxPlayers)
                ->setPlayingTime($playingTime)
                ->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($game);
        }

        $this->createStaff($manager, 'manager@pionek.test', ['ROLE_MANAGER'], 'manager1234');
        $this->createStaff($manager, 'pracownik@pionek.test', ['ROLE_STAFF'], 'pracownik1234');

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createStaff(ObjectManager $manager, string $email, array $roles, string $plainPassword): void
    {
        $user = (new User())
            ->setEmail($email)
            ->setRoles($roles);

        // Hasło hashowane Argon2id (algorytm ustawiony w config/packages/security.yaml).
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $manager->persist($user);
    }
}
