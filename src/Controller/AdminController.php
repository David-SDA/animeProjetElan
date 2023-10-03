<?php

namespace App\Controller;

use App\Repository\AnimeRepository;
use App\Repository\DiscussionRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(UserRepository $userRepository, DiscussionRepository $discussionRepository, PostRepository $postRepository, AnimeRepository $animeRepository): Response
    {        
        return $this->render('admin/index.html.twig', [
            'totalUsers' => $userRepository->countUsers(),
            'totalTalks' => $discussionRepository->countDiscussions(),
            'totalAnimes' => $animeRepository->countAnimes(),
            'usersMostTalksCreated' => $discussionRepository->usersMostTalksCreated(),
            'usersMostPostsCreated' => $postRepository->usersMostPostsCreated(),
        ]);
    }

    #[Route('/admin/users', name: 'users_admin')]
    public function users(UserRepository $userRepository): Response{
        return $this->render('admin/users.html.twig', [
            'nonBannedUsers' => $userRepository->getUsersByStatus(false),
            'bannedUsers' => $userRepository->getUsersByStatus(true),
            'unverifiedUsers' => $userRepository->getUnverifiedUsers(),
        ]);
    }
}
