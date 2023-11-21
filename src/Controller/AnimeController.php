<?php

namespace App\Controller;

use App\Form\SearchFormType;
use App\Repository\AnimeRepository;
use App\Service\AnimeCallApiService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRegarderAnimeRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/anime')]
class AnimeController extends AbstractController
{   
    #[Route('/search', name: 'search_anime')]
    public function search(AnimeCallApiService $animeCallApiService, Request $request): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }
        
        /* Création du formulaire pour les filtres et recherche */
        $form = $this->createForm(SearchFormType::class);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* Récupération des données du formulaire */
            $search = $form->get('search')->getData();
            $season = $form->get('season')->getData();
            $seasonYear = $form->get('seasonYear')->getData();
            $genre = $form->get('genre')->getData();
            $format = $form->get('format')->getData();
            
            /* Récupération des données de l'API */
            $dataAnimes = $animeCallApiService->getMultipleAnimesSearch($search, $season, $seasonYear, $genre, $format);
        }
        else{
            /* Récupération des données de l'API */
            $dataAnimes = $animeCallApiService->getMultipleAnimesSearch();
        }

        return $this->render('anime/search.html.twig', [
            'form' => $form,
            'dataAnimes' => $dataAnimes,
        ]);
    }

    #[Route('/top', name: 'top_anime')]
    public function top(AnimeCallApiService $animeCallApiService): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }
        
        return $this->render('anime/top.html.twig', [
            'dataTopAnimes' => $animeCallApiService->getTopAnimes(),
        ]);
    }

    #[Route('/seasonal', name: 'seasonal_anime')]
    public function seasonal(AnimeCallApiService $animeCallApiService): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        return $this->render('anime/seasonal.html.twig', [
            'dataSeasonalAnimes' => $animeCallApiService->getSeasonalAnimes(1),
        ]);
    }

    #[Route('/{id}', name: 'show_anime')]
    public function show(int $id, AnimeCallApiService $animeCallApiService, UserRegarderAnimeRepository $userRegarderAnimeRepository, AnimeRepository $animeRepository): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();
        
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isBanned()){
            return $this->redirectToRoute('app_banned');
        }
        
        /* On crée une variable pour déterminer si l'animé est dans la liste de l'utilisateur, on définit que de base, il n'y est pas */
        $animeIsInList = false;
        /* Si l'utilisateur est connecté et que l'on trouve une instance de userRegarderAnime avec le même user et le même anime (que l'on cherche aussi) */
        if($currentUser && $userRegarderAnimeRepository->findOneBy(['user' => $currentUser, 'anime' => $animeRepository->findOneBy(['idApi' => $id])])){
            /* Alors l'utilisateur a déjà l'anime dans sa liste */
            $animeIsInList = true;
        }

        /* On crée une variable pour déterminer si l'animé est dans les animés favoris de l'utilisateur, on définit que de base, il n'y est pas */
        $animeIsInFavorites = false;

        /* Si l'utilisateur est connecté */
        if($currentUser){
            /* Pour chaque animés dans la collection d'animés de l'utilisateur (qui représente les animés favoris de l'utilisateur) */
            foreach($currentUser->getAnimes() as $anime){
                /* On vérifie si l'id de l'API correspond à l'id de l'animé de la page actuelle */
                if($anime->getIdApi() === $id){
                    /* Dès que l'on trouve une correspondance, cela veut dire que l'animé est bien dans les animés favoris de l'utilisateur et on stop la boucle dès que cela arrive */
                    $animeIsInFavorites = true;
                    break;
                }
            }
        }

        $animeInDatabase = $animeRepository->findOneBy(['idApi' =>  $id]);
        
        return $this->render('anime/show.html.twig', [
            'dataOneAnime' => $animeCallApiService->getAnimeDetails($id),
            'animeIsInList' => $animeIsInList,
            'animeIsInFavorites' => $animeIsInFavorites,
            'animeInDatabase' => $animeInDatabase,
        ]);
    }

    #[Route('/{id}/characters', name: 'characters_anime')]
    public function characters(int $id, AnimeCallApiService $animeCallApiService): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        return $this->render('anime/characters.html.twig', [
            'dataAllCharactersOneAnime' => $animeCallApiService->getAllCharactersAnime($id),
        ]);
    }

    #[Route('/{id}/staff', name: 'staff_anime')]
    public function staff(int $id, AnimeCallApiService $animeCallApiService): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        return $this->render('anime/staff.html.twig', [
            'dataAllStaffOneAnime' => $animeCallApiService->getAllStaffAnime($id),
        ]);
    }

}
