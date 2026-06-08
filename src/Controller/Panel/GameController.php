<?php

namespace App\Controller\Panel;

use App\Entity\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panel/games')]
#[IsGranted('ROLE_STAFF')]
final class GameController extends AbstractController
{
    #[Route('', name: 'app_panel_games', methods: ['GET'])]
    public function index(GameRepository $games): Response
    {
        return $this->render('panel/games/index.html.twig', [
            'games' => $games->findBy([], ['title' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_panel_game_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $game = new Game();
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $game->setCreatedAt(new \DateTimeImmutable());
            $em->persist($game);
            $em->flush();
            $this->addFlash('success', 'Gra dodana.');

            return $this->redirectToRoute('app_panel_games');
        }

        return $this->render('panel/games/form.html.twig', [
            'form' => $form,
            'heading' => 'Nowa gra',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_panel_game_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Game $game, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Gra zaktualizowana.');

            return $this->redirectToRoute('app_panel_games');
        }

        return $this->render('panel/games/form.html.twig', [
            'form' => $form,
            'heading' => 'Edytuj: '.$game->getTitle(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_panel_game_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')] // trwałe usunięcie tylko dla managera
    public function delete(Game $game, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('panel-game-delete', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Nieprawidłowy token CSRF.');
        }

        if ($game->hasActiveReservations()) {
            $this->addFlash('warning', 'Nie można usunąć gry z aktywną rezerwacją (oczekującą, potwierdzoną lub wydaną).');

            return $this->redirectToRoute('app_panel_games');
        }

        // gra bez aktywnych rezerwacji — kasujemy ją wraz z historią (zwrócone rezerwacje, cascade remove)
        $em->remove($game);
        $em->flush();
        $this->addFlash('success', 'Gra usunięta.');

        return $this->redirectToRoute('app_panel_games');
    }
}
