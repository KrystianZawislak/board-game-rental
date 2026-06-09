<?php

namespace App\Controller;

use App\Entity\Game;
use App\Enum\GameCategory;
use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(GameRepository $games): Response
    {
        // wszystkie gry pogrupowane w wiersze wg kategorii; filtrowanie dzieje się w przeglądarce
        return $this->render('home/index.html.twig', [
            'rows' => $this->groupByCategory($games),
            'categories' => GameCategory::cases(), // źródło prawdy dla dropdownu filtrów
        ]);
    }

    /**
     * @return list<array{category: GameCategory, games: list<Game>}>
     */
    private function groupByCategory(GameRepository $games): array
    {
        $grouped = [];
        foreach ($games->findAllWithReservations() as $game) {
            $grouped[$game->getCategory()->value][] = $game;
        }

        $rows = [];
        foreach (GameCategory::cases() as $category) {
            if (!empty($grouped[$category->value])) {
                $rows[] = ['category' => $category, 'games' => $grouped[$category->value]];
            }
        }

        return $rows;
    }
}
