<?php

namespace App\Controller;

use App\Repository\DiscussionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(UserRepository $userRepository, DiscussionRepository $discussionRepository): Response
    {
        return $this->render('admin/index.html.twig', [
            'totalUsers' => $userRepository->countUsers(),
            'totalTalks' => $discussionRepository->countDiscussions(),
        ]);
    }
}
