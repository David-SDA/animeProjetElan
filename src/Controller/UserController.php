<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\HomeCallApiService;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

    #[Route(path: 'user/{id}/settings/changePassword', name: 'change_password_user')]
    public function changePassword(Request $request, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $hasher): Response{
        $currentUser = $this->getUser();
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            if($hasher->isPasswordValid($currentUser, $form->get('password')->getData())){
                $currentUser->setPassword(
                    $hasher->hashPassword(
                        $currentUser,
                        $form->get('plainPassword')->getData()
                    )
                );

                $entityManagerInterface->persist($currentUser);
                $entityManagerInterface->flush();

                $this->addFlash(
                    'success',
                    'Password has been modified'
                );
            }
            else{
                $this->addFlash(
                    'warning',
                    'Wrong password'
                );
            }
        }

        return $this->render('user/changePassword.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
