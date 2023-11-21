<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\EmailVerifier;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Repository\AnimeRepository;
use Symfony\Component\Mime\Address;
use App\Repository\DiscussionRepository;
use App\Repository\PersonnageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier){
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(UserRepository $userRepository, DiscussionRepository $discussionRepository, PostRepository $postRepository, AnimeRepository $animeRepository, PersonnageRepository $personnageRepository): Response{
        /* Si l'admin est banni, on le redirige vers la page d'un banni */
        if($this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        return $this->render('admin/index.html.twig', [
            'totalUsers' => $userRepository->countUsers(),
            'totalTalks' => $discussionRepository->countDiscussions(),
            'totalAnimes' => $animeRepository->countAnimes(),
            'totalCharacters' => $personnageRepository->countCharacters(),
            'usersMostTalksCreated' => $discussionRepository->usersMostTalksCreated(),
            'usersMostPostsCreated' => $postRepository->usersMostPostsCreated(),
        ]);
    }

    #[Route('/admin/users', name: 'users_admin')]
    public function users(UserRepository $userRepository): Response{
        /* Si l'admin est banni, on le redirige vers la page d'un banni */
        if($this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        return $this->render('admin/users.html.twig', [
            'nonBannedUsers' => $userRepository->getUsersByStatus(false),
            'bannedUsers' => $userRepository->getUsersByStatus(true),
            'unverifiedUsers' => $userRepository->getUnverifiedUsers(),
        ]);
    }

    #[Route('/admin/user/{id}/ban', name: 'ban_user_admin')]
    public function ban(EntityManagerInterface $entityManagerInterface, User $user): Response{
        /* Si l'admin est banni, on le redirige vers la page d'un banni */
        if($this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas banni */
        if(!$user->isBanned()){
            /* On le banni */
            $user->setBanned(true);

            /* On sauvegarde les changements en base de données */
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();

            /* On indique la réussite du bannissement */
            $this->addFlash(
                'success',
                'The user "' . $user->getUsername() . '" has been banned successfully'
            );
        }
        else{
            /* On indique l'échec du bannissement */
            $this->addFlash(
                'error',
                'The user "' . $user->getUsername() . '" is already banned'
            );
        }

        return $this->redirectToRoute('users_admin');
    }

    #[Route('/admin/user/{id}/unban', name: 'unban_user_admin')]
    public function unban(EntityManagerInterface $entityManagerInterface, User $user): Response{
        /* Si l'admin est banni, on le redirige vers la page d'un banni */
        if($this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur est banni */
        if($user->isBanned()){
            /* On le débanni */
            $user->setBanned(false);

            /* On sauvegarde les changements en base de données */
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();

            /* On indique la réussite du débannissement */
            $this->addFlash(
                'success',
                'The user "' . $user->getUsername() . '" has been unbanned successfully'
            );
        }
        else{
            /* On indique l'échec du débannissement */
            $this->addFlash(
                'error',
                'The user "' . $user->getUsername() . '" is already unbanned'
            );
        }

        return $this->redirectToRoute('users_admin');
    }

    #[Route('/admin/user/{id}/resendConfirmation', name: 'resend_confirmation_user_admin')]
    public function resendConfirmation(User $user): Response{
        /* Si l'admin est banni, on le redirige vers la page d'un banni */
        if($this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'a pas vérifier son email */
        if(!$user->isVerified()){
            /* On lui renvoie un email de confirmation */
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                    (new TemplatedEmail())
                        ->from(new Address('admin@AnimeProjetElan.com', 'Admin Anime Projet Elan'))
                        ->to($user->getEmail())
                        ->subject('Please Confirm your Email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            /* On indique le succès de l'envoie d'email */
            $this->addFlash(
                'success',
                'The confirmation of the email of the user "' . $user->getUsername() . '" has been sended successfully'
            );
        }
        else{
            /* On indique l'échec de l'envoie d'email */
            $this->addFlash(
                'error',
                'The user "' . $user->getUsername() . '" is already verified'
            );
        }

        return $this->redirectToRoute('users_admin');
    }
}
