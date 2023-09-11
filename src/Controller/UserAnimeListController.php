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
        /* On crée un tableau avec les 3 états possible d'un animé dans une liste */
        $animeByState = [
            'Watching' => [],
            'Completed' => [],
            'Plan to watch' => [],
        ];
    
        /* Pour chaque instance dans la liste d'un utilisateur, on intégre au tableau l'anime de la base de données concerné en fonction de son état  */
        foreach($user->getUserRegarderAnimes() as $userAnime){
            $state = $userAnime->getEtat();
            $animeByState[$state][] = $userAnime;
        }
        
        /* Création de la variable pour les données des animés */
        $animeDataByState = [];
    
        /* Pour chaque état dans le tableau ($state => $userAnimes : permet d'avoir accès aux clés, ici l'état) */
        foreach($animeByState as $state => $userAnimes){
            /* On crée un tableau d'ids (ids pour l'API) */
            $animeIds = [];
            
            /* Pour chaque animé d'un état */
            foreach ($userAnimes as $userAnime){
                /* On stock l'id de l'API dans le tableau créer précedement */
                $animeIds[] = $userAnime->getAnime()->getIdApi();
            }
            
            /* On récupère les données de l'API grâce au tableau d'id */
            $animeData = $animeCallApiService->getAnimesFromList($animeIds);
    
            /* On crée un tableau associatifs entre état et donnée de l'API */
            $animeDataByState[$state] = [];
    
            /* Pour chaque animé dans la liste d'un utilisateur, on ajoute au tableau tout juste créé l'id de l'instance de la liste et les données de l'API */
            foreach ($userAnimes as $userAnime){
                $animeDataByState[$state][$userAnime->getId()] = $animeData;
            }
        }
        
        return $this->render('user_anime_list/animeList.html.twig', [
            'user' => $user,
            'animesDataByState' => $animeDataByState,
        ]);
    }

    #[Route('user/{id}/animeList/modify/{userRegarderAnime_id}', name: 'change_anime_list_user')]
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
