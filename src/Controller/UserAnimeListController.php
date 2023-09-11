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
        /* Création d'une variable pour obtenir les animés dans la liste de l'utilisateur */
        $userAnimes = $user->getUserRegarderAnimes();

        $animes = $userAnimes->map(function ($anime){
            return $anime->getAnime()->getIdApi();
        })->toArray();

        /* Tableaux d'animes dans la liste de l'utilisateur en fonction de leur état */
        $animesWatching = [];
        $animesCompleted = [];
        $animesPlanned = [];

        foreach($userAnimes as $userAnime){
            $etat = $userAnime->getEtat();

            if($etat === 'WATCHING'){
                $animesWatching[] = $userAnime;
            }
            elseif($etat === 'COMPLETED'){
                $animesCompleted[] = $userAnime;
            }
            else{
                $animesPlanned[] = $userAnime;
            }
        }

        $animesWatchingApiIds = [];
        foreach($animesWatching as $anime){
            $animesWatchingApiIds[] = $anime->getAnime()->getIdApi();
        }

        $animesCompletedApiIds = [];
        foreach($animesCompleted as $anime){
            $animesCompletedApiIds[] = $anime->getAnime()->getIdApi();
        }

        $animesPlannedApiIds = [];
        foreach($animesPlanned as $anime){
            $animesPlannedApiIds[] = $anime->getAnime()->getIdApi();
        }

        return $this->render('user_anime_list/animeList.html.twig', [
            'user' => $user,
            'animesWatching' => $animeCallApiService->getAnimesFromList($animesWatchingApiIds),
            'animesCompleted' => $animeCallApiService->getAnimesFromList($animesCompletedApiIds),
            'animesPlanned' => $animeCallApiService->getAnimesFromList($animesPlannedApiIds),
        ]);
    }
}
