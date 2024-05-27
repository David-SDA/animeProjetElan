<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Anime;
use App\Entity\Discussion;
use App\Form\PostFormType;
use App\Form\SearchFormType;
use App\Repository\PostRepository;
use App\Repository\AnimeRepository;
use App\Service\AnimeCallApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRegarderAnimeRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
    public function top(Request $request, AnimeCallApiService $animeCallApiService, CacheInterface $cache): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }
        
        /* Recupération du numéro de la page via la requete GET (si vide, on met par défaut la page 1) */
        $pageNumber = $request->query->getInt('page', 1);

        /* Récupération des données de l'API avec une mise en cache */
        $dataTopAnimes = $cache->get('data_top_anime_' . $pageNumber, function(ItemInterface $item) use($animeCallApiService, $pageNumber){
            $item->expiresAt(new \DateTime('tomorrow'));
            return $animeCallApiService->getTopAnimes($pageNumber);
        });

        /* Vérification si ce qui a été mis en cache contient des animé */
        if(!($dataTopAnimes['data']['Page']['media'])) {
            /* On supprime le cache si il n'y a pas d'animés */
            $cache->delete('data_seasonal_anime_' . $pageNumber);
            throw $this->createNotFoundException();
        }

        return $this->render('anime/top.html.twig', [
            'dataTopAnimes' => $dataTopAnimes,
        ]);
    }

    #[Route('/seasonal', name: 'seasonal_anime')]
    public function seasonal(Request $request,AnimeCallApiService $animeCallApiService, CacheInterface $cache): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Recupération du numéro de la page via la requete GET (si vide, on met par défaut la page 1) */
        $pageNumber = $request->query->getInt('page', 1);

        /* Vérification pageNumber est bien un nombre */
        if(!is_int($pageNumber) || $pageNumber <= 0){
            throw $this->createNotFoundException();
        }

        /* Récupération des données de l'API avec une mise en cache */
        $dataSeasonalAnime = $cache->get('data_seasonal_anime_' . $pageNumber, function(ItemInterface $item) use($animeCallApiService, $pageNumber){
            $item->expiresAt(new \DateTime('tomorrow'));
            return $animeCallApiService->getSeasonalAnimes($pageNumber);
        });

        /* Vérification si ce qui a été mis en cache contient des animé */
        if(!($dataSeasonalAnime['data']['Page']['media'])) {
            /* On supprime le cache si il n'y a pas d'animés */
            $cache->delete('data_seasonal_anime_' . $pageNumber);
            throw $this->createNotFoundException();
        }

        return $this->render('anime/seasonal.html.twig', [
            'dataSeasonalAnimes' => $dataSeasonalAnime,
        ]);
    }

    #[Route('/{id}', name: 'show_anime')]
    public function show(int $id, AnimeCallApiService $animeCallApiService, UserRegarderAnimeRepository $userRegarderAnimeRepository, AnimeRepository $animeRepository, PostRepository $postRepository, CacheInterface $cache): Response{
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
        
        /* Recherche de la discussion de l'animé si jamais il en un */
        $discussion = $animeInDatabase ? $animeInDatabase->getDiscussion() : null;
        
        try{
            /* Récupération des données de l'API avec une mise en cache */
            $dataOneAnime = $cache->get('data_one_anime_' . $id, function(ItemInterface $item) use($animeCallApiService, $id){
                $item->expiresAt(new \DateTime('tomorrow'));
                return $animeCallApiService->getAnimeDetails($id);
            });

            /* Récupération du post de l'utilisateur si il existe */
            $userPost = null;
            if($currentUser && $discussion){
                $userPost = $postRepository->findUserPostInDiscussion($discussion->getId(), $currentUser->getId());
            }

            return $this->render('anime/show.html.twig', [
                'dataOneAnime' => $dataOneAnime,
                'animeIsInList' => $animeIsInList,
                'animeIsInFavorites' => $animeIsInFavorites,
                'animeInDatabase' => $animeInDatabase,
                'discussion' => $discussion,
                'userPost' => $userPost
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

    #[Route('/{id}/post', name: 'post_about_anime')]
    public function postAnime(int $id, AnimeRepository $animeRepository, EntityManagerInterface $entityManagerInterface, Request $request, CacheInterface $cache): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();
        
        /* Si l'utilisateur n'est pas connecté, on l'empeche de poster */
        if(!$currentUser){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are allowed to post'
            );

            return $this->redirectToRoute('show_anime', ['id' => $id]);
        }

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isBanned()){
            return $this->redirectToRoute('app_banned');
        }


        /* Recherche de l'animé en base de données */
        $animeInDatabase = $animeRepository->findOneBy(['idApi' =>  $id]);

        /* Si il y a l'animé dans la base de données, il y a peut être un discussion sur celui-ci */
        if($animeInDatabase){
            /* Recherche de la discussion de l'animé si jamais il en un */
            $discussion = $animeInDatabase ? $animeInDatabase->getDiscussion() : null;
        }
        /* Sinon il faut l'ajouter à la base de données et créer la discussion en rapport à l'anime ainsi que le post */
        else{
            /* On crée une instance d'animé dans lequel on y définit l'id de l'API */
            $animeInDatabase = new Anime();
            $animeInDatabase->setIdApi($id);
            $entityManagerInterface->persist($animeInDatabase);

            /* On crée une discussion */
            $discussion = new Discussion();
            $discussion->setTitle('Opinions of ');
            $discussion->setCreationDate(new \DateTime());
        }

        /* On crée un post */
        $post = new Post();

        /* Création du formulaire */
        $form = $this->createForm(PostFormType::class);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* Ajout des données à l'entité post */
            $post->setCreationDate(new \DateTime());
            $post->setLastModifiedDate(new \DateTime());
            $post->setContent($form->get('content')->getData());
            $post->setUser($currentUser);
            $post->setDiscussion($discussion);

            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($discussion);
            $entityManagerInterface->persist($post);
            $entityManagerInterface->flush();

            $cache->delete('users_most_talks_created');

            /* On indique le succès de la création */
            $this->addFlash(
                'success',
                'Your opinion has been posted'
            );

            return $this->redirectToRoute('show_anime', ['id' => $id]);
        }

        return $this->render('post/add.html.twig', [
            'form' => $form->createView(),
            'postExist' => false
        ]);
    }
}
