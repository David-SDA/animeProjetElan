<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnimeRepository;
use App\Repository\DiscussionRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/admin/user/{id}/ban', name:'ban_user_admin')]
    public function ban(EntityManagerInterface $entityManagerInterface, User $user){
        /* Si l'utilisateur n'est pas banni */
        if(!$user->isEstBanni()){
            /* On le banni */
            $user->setEstBanni(true);

            /* On sauvegarde les changements en base de données */
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();

            /* On indique la réussite du bannissement */
            $this->addFlash(
                'success',
                'The user "' . $user->getPseudo() . '" has been banned successfully'
            );
        }
        else{
            /* On indique l'échec du bannissement */
            $this->addFlash(
                'error',
                'The user "' . $user->getPseudo() . '" is already banned'
            );
        }

        return $this->redirectToRoute('users_admin');
    }

    #[Route('/admin/user/{id}/unban', name:'unban_user_admin')]
    public function unban(EntityManagerInterface $entityManagerInterface, User $user){
        /* Si l'utilisateur est banni */
        if($user->isEstBanni()){
            /* On le débanni */
            $user->setEstBanni(false);

            /* On sauvegarde les changements en base de données */
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();

            /* On indique la réussite du débannissement */
            $this->addFlash(
                'success',
                'The user "' . $user->getPseudo() . '" has been unbanned successfully'
            );
        }
        else{
            /* On indique l'échec du débannissement */
            $this->addFlash(
                'error',
                'The user "' . $user->getPseudo() . '" is already unbanned'
            );
        }

        return $this->redirectToRoute('users_admin');
    }
}
