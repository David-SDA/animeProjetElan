<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AnimeCallApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserAnimeListController extends AbstractController
{
    #[Route('user/{id}/animeList', name: 'anime_list_user')]
    public function animeList(User $user, AnimeCallApiService $animeCallApiService): Response{

        $animes = $user->getUserRegarderAnimes()->map(function ($anime){
            return $anime->getAnime()->getIdApi();
        })->toArray();

        return $this->render('user_anime_list/animeList.html.twig', [
            'user' => $user,
            'animes' => $animeCallApiService->getAnimesFromList($animes),
        ]);
    }
}
