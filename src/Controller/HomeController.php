<?php

namespace App\Controller;

use App\Service\HomeCallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(HomeCallApiService $homeCallApiService): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }
        
        return $this->render('home/index.html.twig', [
            'dataBestAnimeThisSeason' => $homeCallApiService->getBestAnimeThisSeason(),
            'dataNextSeasonAnime' => $homeCallApiService->getNextSeasonAnime(),
            'dataPopularAnime' => $homeCallApiService->getPopularAnime(),
            'dataTopTenAnime' => $homeCallApiService->getTopTenAnime(),
        ]);
    }
}
