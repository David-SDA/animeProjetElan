<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserRegarderAnime;
use App\Form\ModifyAnimeListFormType;
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

        /* Tableaux d'animes dans la liste de l'utilisateur en fonction de leur état */
        $animesWatching = [];
        $animesCompleted = [];
        $animesPlanned = [];

        /* On ajoute les animé dans les différents tableaux en fonction de leur état */
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

        /* Pour chaque tableau d'animés, on récupère l'id des animés (servira à l'appel de l'API) */
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

    #[Route('user/{id}/animeList/modify/{userRegarderAnime_id}')]
    public function modifyAnimeList(User $id, UserRegarderAnime $userRegarderAnime_id, AnimeCallApiService $animeCallApiService){
        if($id !== $this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        $animeApiId = $userRegarderAnime_id->getAnime()->getIdApi();
        $animeData = $animeCallApiService->getAnimeDetails($animeApiId);

        /* Création du formulaire */
        $form = $this->createForm(ModifyAnimeListFormType::class, null, [
            'maxEpisodes' => $animeData['data']['Media']['episodes'],
            'startDate' => $userRegarderAnime_id->getDateDebutVisionnage(),
            'endDate' => $userRegarderAnime_id->getDateFinVisionnage(),
            'status' => $userRegarderAnime_id->getEtat(),
            'numberEpisodes' => $userRegarderAnime_id->getNombreEpisodeVu(),
        ]);

        return $this->render('user_anime_list/modifyAnimeList.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
