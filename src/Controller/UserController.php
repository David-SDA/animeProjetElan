<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\HomeCallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/user/{id}', name: 'show_user')]
    public function show(User $user, HomeCallApiService $homeCallApiService): Response{
        return $this->render('user/show.html.twig',  [
            'user' => $user,
            'dataTopTenAnime' => $homeCallApiService->getTopTenAnime(), // Test visuel uniquement, à retirer
        ]);
    }

    #[Route('user/{id}/settings', name: 'settings_user')]
    public function settings(User $user): Response{
        if($this->getUser() !== $user){
            throw new AccessDeniedException();
        }
        return $this->render('user/settings.html.twig', [
            'user' => $user,
        ]);
    }
}
