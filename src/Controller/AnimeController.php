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
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/anime')]
class AnimeController extends AbstractController
{   
    #[Route('/search', name: 'search_anime')]
    public function search(AnimeCallApiService $animeCallApiService, Request $request, CacheInterface $cache): Response{
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
            /* Récupération des données de l'API avec une mise en cache */
            $dataAnimes = $cache->get('data_anime_search', function(ItemInterface $item) use($animeCallApiService){
                $item->expiresAt(new \DateTime('tomorrow'));
                return $animeCallApiService->getMultipleAnimesSearch();
            });
        }

        return $this->render('anime/search.html.twig', [
            'form' => $form,
            'dataAnimes' => $dataAnimes,
        ]);
    }

    #[Route('/top', name: 'top_anime')]
    public function top(AnimeCallApiService $animeCallApiService, CacheInterface $cache): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }
        
        /* Récupération des données de l'API avec une mise en cache */
        $dataTopAnimes = $cache->get('data_top_anime', function(ItemInterface $item) use($animeCallApiService){
            $item->expiresAt(new \DateTime('tomorrow'));
            return $animeCallApiService->getTopAnimes();
        });

        return $this->render('anime/top.html.twig', [
            'dataTopAnimes' => $dataTopAnimes,
        ]);
    }

    #[Route('/seasonal', name: 'seasonal_anime')]
    public function seasonal(AnimeCallApiService $animeCallApiService, CacheInterface $cache): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Récupération des données de l'API avec une mise en cache */
        $dataSeasonalAnime = $cache->get('data_seasonal_anime', function(ItemInterface $item) use($animeCallApiService){
            $item->expiresAt(new \DateTime('tomorrow'));
            return $animeCallApiService->getSeasonalAnimes(1);
        });

        return $this->render('anime/seasonal.html.twig', [
            'dataSeasonalAnimes' => $dataSeasonalAnime,
        ]);
    }

    #[Route('/{id}', name: 'show_anime')]
    public function show(int $id, AnimeCallApiService $animeCallApiService, UserRegarderAnimeRepository $userRegarderAnimeRepository, AnimeRepository $animeRepository, CacheInterface $cache): Response{
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
        
        /* Recherche de l'animé en base de données */
        $animeInDatabase = $animeRepository->findOneBy(['idApi' =>  $id]);

        try{
            /* Récupération des données de l'API avec une mise en cache */
            $dataOneAnime = $cache->get('data_one_anime_' . $id, function(ItemInterface $item) use($animeCallApiService, $id){
                $item->expiresAt(new \DateTime('tomorrow'));
                return $animeCallApiService->getAnimeDetails($id);
            });

            return $this->render('anime/show.html.twig', [
                'dataOneAnime' => $dataOneAnime,
                'animeIsInList' => $animeIsInList,
                'animeIsInFavorites' => $animeIsInFavorites,
                'animeInDatabase' => $animeInDatabase,
            ]);
        }catch(\Exception $exception){
            return $this->render('home/errorAPI.html.twig');
        }
    }

    #[Route('/{id}/characters', name: 'characters_anime')]
    public function characters(int $id, AnimeCallApiService $animeCallApiService, CacheInterface $cache): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        try{
            /* Récupération des données de l'API avec une mise en cache */
            $animeCharacters = $cache->get('one_anime_' . $id . '_characters', function(ItemInterface $item) use($animeCallApiService, $id){
                $item->expiresAt(new \DateTime('tomorrow'));
                return $animeCallApiService->getAllCharactersAnime($id);
            });

            return $this->render('anime/characters.html.twig', [
                'dataAllCharactersOneAnime' => $animeCharacters,
            ]);
        }catch(\Exception $exception){
            return $this->render('home/errorAPI.html.twig');
        }
    }

    #[Route('/{id}/staff', name: 'staff_anime')]
    public function staff(int $id, AnimeCallApiService $animeCallApiService, CacheInterface $cache): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        try{
            /* Récupération des données de l'API avec une mise en cache */
            $animeStaff = $cache->get('one_anime_' . $id . '_staff', function(ItemInterface $item) use($animeCallApiService, $id){
                $item->expiresAt(new \DateTime('tomorrow'));
                return $animeCallApiService->getAllStaffAnime($id);
            });
            
            return $this->render('anime/staff.html.twig', [
                'dataAllStaffOneAnime' => $animeStaff,
            ]);
        }catch(\Exception $exception){
            return $this->render('home/errorAPI.html.twig');
        }
    }

}
