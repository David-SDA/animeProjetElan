<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Anime;
use App\Entity\UserRegarderAnime;
use App\Service\AnimeCallApiService;
use App\Form\ModifyAnimeListFormType;
use App\Repository\AnimeRepository;
use App\Repository\UserRegarderAnimeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/user')]
class UserAnimeListController extends AbstractController
{
    #[Route('/{id}/animeList', name: 'anime_list_user')]
    public function animeList(User $user, AnimeCallApiService $animeCallApiService): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* On crée un tableau avec les 3 états possible d'un animé dans une liste */
        $animesByState = [
            'Watching' => [],
            'Completed' => [],
            'Plan to watch' => [],
        ];
    
        /* Pour chaque instance dans la liste d'un utilisateur, on intégre au tableau l'anime de la base de données concerné en fonction de son état  */
        foreach($user->getUserRegarderAnimes() as $userAnime){
            $state = $userAnime->getEtat();
            $animesByState[$state][] = $userAnime;
        }
        
        /* Création de la variable pour les données des animés */
        $animesDataByState = [];
        
        /* Pour chaque état dans le tableau ($state => $userAnimes : permet d'avoir accès aux clés, ici l'état) */
        foreach($animesByState as $state => $userAnimes){
            /* On crée un tableau d'ids (ids pour l'API) */
            $animeIds = [];
            
            /* Pour chaque animé d'un état */
            foreach($userAnimes as $userAnime){
                /* On stock l'id de l'API dans le tableau créer précedement */
                $animeIds[] = $userAnime->getAnime()->getIdApi();
            }

            /* On récupère les données de l'API grâce au tableau d'id */
            $animeData = $animeCallApiService->getAnimesFromList($animeIds);
            
            /* On crée un tableau associatifs avec l'état et données de l'API */
            $animesDataByState[$state] = [];
    
            /* Pour chaque animé dans la liste d'un utilisateur */
            foreach($userAnimes as $userAnime){
                /* Pour chaque animé dans un certain état */
                foreach($animeData['data']['Page']['media'] as $anime){
                    /* Si l'id de l'anime dans l'API est le même que l'idApi de l'animé dans l'instance de la liste, 
                       on intègre les données de cette animé dans le tableau associé (ajout du nombre d'episodes vu aussi) */
                    if($userAnime->getAnime()->getIdApi() === $anime['id']){
                        $animesDataByState[$state][$userAnime->getId()] = [
                            'id' => $anime['id'],
                            'title' => $anime['title'],
                            'coverImage' => $anime['coverImage'],
                            'episodes' => $anime['episodes'],
                            'episodesWatched' => $userAnime->getNombreEpisodeVu(),
                        ];
                    }
                }
            }
        }
        
        return $this->render('user_anime_list/animeList.html.twig', [
            'user' => $user,
            'animesDataByState' => $animesDataByState,
        ]);
    }

    #[Route('/animeList/modify/{id}', name: 'change_anime_list_user')]
    public function modifyAnimeList(Request $request, UserRegarderAnime $userRegarderAnime, EntityManagerInterface $entityManagerInterface, AnimeCallApiService $animeCallApiService): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur actuel n'est pas celui à qui appartient l'instance de la liste, il n'a pas accès au changement de celui-ci */
        if($userRegarderAnime->getUser() !== $this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        /* On récupère l'id de l'API de l'animé puis ses données de l'API */
        $animeApiId = $userRegarderAnime->getAnime()->getIdApi();
        $animeData = $animeCallApiService->getAnimeDetailsForList($animeApiId);

        /* Création du formulaire */
        $form = $this->createForm(ModifyAnimeListFormType::class, null, [
            'maxEpisodes' => $animeData['data']['Media']['episodes'],
            'startDate' => $userRegarderAnime->getDateDebutVisionnage(),
            'endDate' => $userRegarderAnime->getDateFinVisionnage(),
            'status' => $userRegarderAnime->getEtat(),
            'numberEpisodes' => $userRegarderAnime->getNombreEpisodeVu(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            $newStartDate = $form->get('dateDebutVisionnage')->getData();
            $newEndDate = $form->get('dateFinVisionnage')->getData();
            $newStatus = $form->get('etat')->getData();
            $newEpisodesWatched = intval($form->get('nombreEpisodeVu')->getData());

            /* Si toute les données sont le même qu'en base de données, alors on ne soumet pas le formulaire */
            if($newStartDate == $userRegarderAnime->getDateDebutVisionnage() && $newEndDate == $userRegarderAnime->getDateFinVisionnage() && $newStatus === $userRegarderAnime->getEtat() && $newEpisodesWatched === $userRegarderAnime->getNombreEpisodeVu()){
                $this->addFlash(
                    'error',
                    'The infos cannot be the same as the current ones'
                );
            }
            /* Si la date de début et fin de visionnage existe et si la date de début de visionnage est après celle de fin, on indique l'erreur */
            elseif($newStartDate && $newEndDate && $newStartDate > $newEndDate){
                $this->addFlash(
                    'error',
                    'Start date cannot be after end date'
                );
            }
            else{
                /* Si la date de début de visionnage est différente que celle actuelle, on la modifie */
                if($newStartDate !== $userRegarderAnime->getDateDebutVisionnage()){
                    $userRegarderAnime->setDateDebutVisionnage($newStartDate);
                }
                /* Si la date de fin de visionnage est différente que celle actuelle, on la modifie */
                if($newEndDate !== $userRegarderAnime->getDateFinVisionnage()){
                    $userRegarderAnime->setDateFinVisionnage($newEndDate);
                }
                /* Si l'état est différent que celui actuel, on le modifie */
                if($newStatus !== $userRegarderAnime->getEtat()){
                    $userRegarderAnime->setEtat($newStatus);
                }
                /* Si le nombre d'épisodes vu est différent que celui actuel, on le modifie */
                if($newEpisodesWatched !== $userRegarderAnime->getNombreEpisodeVu()){
                    $userRegarderAnime->setNombreEpisodeVu($newEpisodesWatched);
                }

                /* On sauvegarde ces changements dans la base de données */
                $entityManagerInterface->persist($userRegarderAnime);
                $entityManagerInterface->flush();

                $this->addFlash(
                    'success',
                    'Changes have been saved'
                );
                
                return $this->redirectToRoute('anime_list_user', ['id' => $this->getUser()->getId()]);
            }
        }

        return $this->render('user_anime_list/modifyAnimeList.html.twig', [
            'form' => $form->createView(),
            'animeData' => $animeData,
            'userRegarderAnime' => $userRegarderAnime,
        ]);
    }

    #[Route('/animeList/addAnime/{idApi}', name: 'add_anime_to_list_user')]
    public function addAnimeToList(int $idApi, AnimeRepository $animeRepository, UserRegarderAnimeRepository $userRegarderAnimeRepository, AnimeCallApiService $animeCallApiService, EntityManagerInterface $entityManagerInterface): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }
        
        /* Si l'utilisateur n'est pas connecté, on l'empêche d'ajouter à sa liste */
        if(!$user){
            return $this->redirectToRoute('app_home');
        }

        /* On cherche si l'anime est déjà dans la base de données */
        $animeInDatabase = $animeRepository->findOneBy(['idApi' => $idApi]);
        /* Si l'animé n'est pas dans la base de données */
        if(!$animeInDatabase){
            /* Et si l'animé existe dans l'API */
            if($animeCallApiService->getApiResponse($idApi) === Response::HTTP_OK){
                /* On crée une instance d'animé dans lequel on y définit l'id de l'API */
                $animeInDatabase = new Anime();
                $animeInDatabase->setIdApi($idApi);
                $entityManagerInterface->persist($animeInDatabase);
            }
            else{
                /* Sinon on indique que l'animé n'existe pas (dans l'API) */
                $this->addFlash(
                    'error',
                    'This anime does not exist'
                );

                return $this->redirectToRoute('show_anime', ['id' => $idApi]);
            }
        }
        else{
            /* Sinon on vérifie qu'il n'existe pas d'instance de UserRegarderAnime avec l'anime (qui donc existe déjà en base de donnée) et l'utilisateur actuel */
            if($userRegarderAnimeRepository->findOneBy(['user' => $this->getUser(), 'anime' => $animeInDatabase])){
                /* Si il existe, on indique que l'animé est bien dans la liste de l'utilisateur */
                $this->addFlash(
                    'error',
                    'You cannot add an anime that is already in your list'
                );
                return $this->redirectToRoute('show_anime', ['id' => $idApi]);
            }
        }
        
        /* On crée une instance de UserRegarderAnime */
        $animeInList = new UserRegarderAnime();
        /* Et on y définit les informations nécessaires */
        $animeInList->setUser($this->getUser());
        $animeInList->setAnime($animeInDatabase);
        $animeInList->setEtat("Watching");
        $animeInList->setNombreEpisodeVu(0);

        /* On sauvegarde ces changements dans la base de données */
        $entityManagerInterface->persist($animeInList);
        $entityManagerInterface->flush();

        /* On indique la réussite de l'ajout à la liste */
        $this->addFlash(
            'success',
            'This anime has been added to your list'
        );

        return $this->redirectToRoute('show_anime', ['id' => $idApi]);
    }

    #[Route('/animeList/addEpisode/{id}', name: 'add_episode_anime_list_user')]
    public function addEpisodeToAnimeInList(Request $request, UserRegarderAnime $userRegarderAnime, AnimeCallApiService $animeCallApiService, EntityManagerInterface $entityManagerInterface): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté, on l'empêche d'ajouter un episode */
        if(!$user){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur n'est pas celui à qui appartient l'instance de la liste, on l'empêche d'ajouter un episode */
        if($userRegarderAnime->getUser() != $user){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'animé auquel on veut ajouter un épisode n'est pas un animé qui est en cours de visionnage */
        if($userRegarderAnime->getEtat() !== 'Watching'){
            /* On l'en empêche, on l'indique et on revient à la liste */
            $this->addFlash(
                'error',
                'This anime is not currently watched'
            );
            return $this->redirectToRoute('anime_list_user', ['id' => $user->getId()]);
        }

        /* On récupère le nombre d'épisodes déjà vu par l'utilisateur */
        $currentEpisodesWatched = $userRegarderAnime->getNombreEpisodeVu();

        /* On récupère les données de l'API de l'animé en question */
        $animeData = $animeCallApiService->getAnimeDetailsForList($userRegarderAnime->getAnime()->getIdApi());
        
        /* On récupère le nombre d'épisodes max et le titre de l'animé */
        $maxEpisodes = $animeData['data']['Media']['episodes'];
        $title = $animeData['data']['Media']['title']['romaji'];

        /* Si il y a un maximum d'épisodes et qu'on est déjà à ce maximum (ou dépassé dans un cas où un animé n'avait pas de max d'épisode) */
        if($maxEpisodes && $currentEpisodesWatched >= $maxEpisodes){
            /* On l'en empêche l'ajout d'épisode, on l'indique et on revient à la liste */
            $this->addFlash(
                'error',
                'You cannot go over the maximum number of episode of "' . $title . '"'
            );
            return $this->redirectToRoute('anime_list_user', ['id' => $user->getId()]);
        }

        /* On rajoute un épisode à l'animé */
        $userRegarderAnime->setNombreEpisodeVu($currentEpisodesWatched + 1);

        /* On sauvegarde ces changements dans la base de données */
        $entityManagerInterface->persist($userRegarderAnime);
        $entityManagerInterface->flush();

        /* On indique la réussite de l'ajout d'épisode */
        $this->addFlash(
            'success',
            'An episode of "' . $title . '" has been added'
        );

        /* On revient sur la liste d'animé de l'utilisateur */
        return $this->redirectToRoute('anime_list_user', ['id' => $user->getId()]);
    }

    #[Route('/animeList/removeEpisode/{id}', name: 'remove_episode_anime_list_user')]
    public function removeEpisodeToAnimeInList(Request $request, UserRegarderAnime $userRegarderAnime, AnimeCallApiService $animeCallApiService, EntityManagerInterface $entityManagerInterface): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté, on l'empêche de supprimer un episode */
        if(!$user){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur n'est pas celui à qui appartient l'instance de la liste, on l'empêche de supprimer un episode */
        if($userRegarderAnime->getUser() != $user){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'animé auquel on veut supprimer un épisode n'est pas un animé qui est en cours de visionnage */
        if($userRegarderAnime->getEtat() !== 'Watching'){
            /* On l'en empêche, on l'indique et on revient à la liste */
            $this->addFlash(
                'error',
                'This anime is not currently watched'
            );
            return $this->redirectToRoute('anime_list_user', ['id' => $user->getId()]);
        }

        /* On récupère le nombre d'épisodes déjà vu par l'utilisateur */
        $currentEpisodesWatched = $userRegarderAnime->getNombreEpisodeVu();

        /* On récupère les données de l'API de l'animé en question */
        $animeData = $animeCallApiService->getAnimeDetailsForList($userRegarderAnime->getAnime()->getIdApi());
        $title = $animeData['data']['Media']['title']['romaji'];

        /* Si le nombre d'épisode vu est déjà de 0, on l'empêche de supprimer un épisode */
        if($currentEpisodesWatched === 0){
            /* On l'en empêche la suppression d'épisode, on l'indique et on revient à la liste */
            $this->addFlash(
                'error',
                'You cannot go under 0 episodes'
            );
            return $this->redirectToRoute('anime_list_user', ['id' => $user->getId()]);
        }

        /* On enlève un épisode à l'animé */
        $userRegarderAnime->setNombreEpisodeVu($currentEpisodesWatched - 1);

        /* On sauvegarde ces changements dans la base de données */
        $entityManagerInterface->persist($userRegarderAnime);
        $entityManagerInterface->flush();

        /* On indique la réussite de la suppression d'un épisode */
        $this->addFlash(
            'success',
            'An episode of "' . $title . '" has been removed'
        );

        /* On revient sur la liste d'animé de l'utilisateur */
        return $this->redirectToRoute('anime_list_user', ['id' => $user->getId()]);
    }

    #[Route('/animeList/removeAnime/{id}', name: 'remove_anime_from_list_user')]
    public function removeAnimeFromList(UserRegarderAnime $userRegarderAnime, EntityManagerInterface $entityManagerInterface): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur actuel n'est pas celui à qui appartient l'instance de la liste, il n'a pas accès à la suppression de celui-ci */
        if($userRegarderAnime->getUser() !== $user){
            return $this->redirectToRoute('app_home');
        }

        $idApi = $userRegarderAnime->getAnime()->getIdApi();

        /* On supprime l'instance de la liste et on sauvegarde ce changement dans la base de données */
        $entityManagerInterface->remove($userRegarderAnime);
        $entityManagerInterface->flush();

        /* On indique que la suppression a été effectuer */
        $this->addFlash(
            'success',
            'This anime has been removed from your list'
        );

        return $this->redirectToRoute('show_anime', ['id' => $idApi]);
    }
}
