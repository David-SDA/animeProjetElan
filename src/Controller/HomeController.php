<?php

namespace App\Controller;

use App\Service\HomeCallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(HomeCallApiService $homeCallApiService, CacheInterface $cache): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Récupération des données de l'API avec une mise en cache */
        /* Meilleurs animés de cette saison */
        $bestAnimeThisSeason = $cache->get('best_anime_this_season', function(ItemInterface $item) use($homeCallApiService){
            $item->expiresAt(new \DateTime('tomorrow'));
            return $homeCallApiService->getBestAnimeThisSeason();
        });

        /* Animés de la prochaine saison */
        $nextSeasonAnime = $cache->get('next_season_anime', function(ItemInterface $item) use($homeCallApiService){
            $item->expiresAt(new \DateTime('tomorrow'));
            return $homeCallApiService->getNextSeasonAnime();
        });

        /* Animés les plus populaires */
        $mostPopularAnime = $cache->get('most_popular_anime', function(ItemInterface $item) use($homeCallApiService){
            $item->expiresAt(new \DateTime('tomorrow'));
            return $homeCallApiService->getPopularAnime();
        });

        /* Le top 10 des meilleurs animés */
        $topTenAnime = $cache->get('top_ten_anime', function(ItemInterface $item) use($homeCallApiService){
            $item->expiresAt(new \DateTime('tomorrow'));
            return $homeCallApiService->getTopTenAnime();
        });
        
        return $this->render('home/index.html.twig', [
            'dataBestAnimeThisSeason' => $bestAnimeThisSeason,
            'dataNextSeasonAnime' => $nextSeasonAnime,
            'dataPopularAnime' => $mostPopularAnime,
            'dataTopTenAnime' => $topTenAnime,
        ]);
    }
}
