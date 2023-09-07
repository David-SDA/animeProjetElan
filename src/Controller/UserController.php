<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangeUsernameFormType;
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
    public function changePassword(Request $request, User $user, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $hasher): Response{
        $currentUser = $this->getUser();
        if($currentUser !== $user){
            throw new AccessDeniedException();
        }
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
                    'Password has been modified successfully'
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

    /* Changement du pseudo de l'utilisateur connecté */
    #[Route(path: 'user/{id}/settings/changeUsername', name: 'change_username_user')]
    public function changeUsername(Request $request, User $user, EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* On vérifie que l'utilisateur actuel est bien celui qui accéde à sa page de changement de pseudo */
        if($currentUser !== $user){
            throw new AccessDeniedException();
        }

        /* Création du formulaire */
        $form = $this->createForm(ChangeUsernameFormType::class, null, [
            'currentUsername' => $user->getPseudo(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entré sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère le pseudo du formulaire */
            $newUsername = $form->get('pseudo')->getData();

            /* On change le pseudo actuel par le nouveau pseudo */
            $user->setPseudo($newUsername);

            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();

            /* On crée un message de succès */
            $this->addFlash(
                'success',
                'Username has been modified successfully'
            );
        }

        /* On affiche la page de changement d'un pseudo avec son formulaire */
        return $this->render('user/changeUsername.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
