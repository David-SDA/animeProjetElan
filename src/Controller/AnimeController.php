<?php

namespace App\Controller;

use App\Service\AnimeCallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnimeController extends AbstractController
{
    #[Route('/anime', name: 'app_anime')]
    public function index(): Response
    {
        return $this->render('anime/index.html.twig', [
            'controller_name' => 'AnimeController',
        ]);
    }

    #[Route('/anime/{id}', name: 'show_anime')]
    public function show(int $id, AnimeCallApiService $animeCallApiService): Response{
        return $this->render('anime/show.html.twig', [
            'dataOneAnime' => $animeCallApiService->getAnimeDetails($id),
        ]);
    }

    #[Route('/anime/{id}/characters', name: 'characters_anime')]
    public function characters(int $id): Response{
        return $this->render('anime/characters.html.twig');
    }

    #[Route('/anime/{id}/staff', name: 'staff_anime')]
    public function staff(int $id): Response{
        return $this->render('anime/staff.html.twig');
    }
}
