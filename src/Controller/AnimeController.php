<?php

namespace App\Controller;

use App\Repository\AnimeRepository;
use App\Repository\UserRegarderAnimeRepository;
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
    
    #[Route('/anime/top', name: 'top_anime')]
    public function top(AnimeCallApiService $animeCallApiService): Response{
        return $this->render('anime/top.html.twig', [
            'dataTopAnimes' => $animeCallApiService->getTopAnimes(),
        ]);
    }

    #[Route('anime/seasonal', name: 'seasonal_anime')]
    public function seasonal(AnimeCallApiService $animeCallApiService): Response{
        return $this->render('anime/seasonal.html.twig', [
            'dataSeasonalAnimes' => $animeCallApiService->getSeasonalAnimes(1),
        ]);
    }

    #[Route('anime/all', name: 'all_anime')]
    public function all(AnimeCallApiService $animeCallApiService): Response{
        return $this->render('anime/all.html.twig', [
            'dataAllAnimes' => $animeCallApiService->getAllAnimes(1),
        ]);
    }

    #[Route('/anime/{id}', name: 'show_anime')]
    public function show(int $id, AnimeCallApiService $animeCallApiService, UserRegarderAnimeRepository $userRegarderAnimeRepository, AnimeRepository $animeRepository): Response{
        /* On crée une variable pour déterminer si l'animé est dans la liste de l'utilisateur, on définit que de base, il n'y est pas */
        $animeIsInList = false;
        /* Si l'utilisateur est connecté et que l'on trouve une instance de userRegarderAnime avec le même user et le même anime (que l'on cherche aussi) */
        if($this->getUser() && $userRegarderAnimeRepository->findOneBy(['user' => $this->getUser(), 'anime' => $animeRepository->findOneBy(['idApi' => $id])])){
            /* Alors l'utilisateur a déjà l'anime dans sa liste */
            $animeIsInList = true;
        }
        return $this->render('anime/show.html.twig', [
            'dataOneAnime' => $animeCallApiService->getAnimeDetails($id),
            'animeIsInList' => $animeIsInList,
        ]);
    }

    #[Route('/anime/{id}/characters', name: 'characters_anime')]
    public function characters(int $id, AnimeCallApiService $animeCallApiService): Response{
        return $this->render('anime/characters.html.twig', [
            'dataAllCharactersOneAnime' => $animeCallApiService->getAllCharactersAnime($id),
        ]);
    }

    #[Route('/anime/{id}/staff', name: 'staff_anime')]
    public function staff(int $id, AnimeCallApiService $animeCallApiService): Response{
        return $this->render('anime/staff.html.twig', [
            'dataAllStaffOneAnime' => $animeCallApiService->getAllStaffAnime($id),
        ]);
    }

}
