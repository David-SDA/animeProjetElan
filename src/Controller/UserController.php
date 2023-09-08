<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\EmailVerifier;
use App\Form\ChangeEmailFormType;
use App\Form\ChangeInfosFormType;
use App\Service\HomeCallApiService;
use Symfony\Component\Mime\Address;
use App\Form\ChangePasswordFormType;
use App\Form\ChangeUsernameFormType;
use App\Form\ChangeDescriptionFormType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ChangeProfilePictureFormType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier){
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('user/settings', name: 'settings_user')]
    public function settings(): Response{
        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres */
        if(!$this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        return $this->render('user/settings.html.twig');
    }

    /* Changement du pseudo de l'utilisateur connecté */
    #[Route(path: 'user/settings/changeUsername', name: 'change_username_user')]
    public function changeUsername(Request $request, EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement de pseudo */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
        }

        /* Création du formulaire */
        $form = $this->createForm(ChangeUsernameFormType::class, null, [
            'currentUsername' => $currentUser->getPseudo(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère le pseudo du formulaire */
            $newUsername = $form->get('pseudo')->getData();

            /* On vérifie si le pseudo indiqué est le même ou pas que celui actuelle */
            if($newUsername === $currentUser->getPseudo()){
                $this->addFlash(
                    'error',
                    'The new username cannot be the same as the current one'
                );
            }
            else{
                /* On chercher l'existance du pseudo indiqué */
                $existingUser = $entityManagerInterface->getRepository(User::class)->findOneBy(['pseudo' => $newUsername]);

                /* On vérifie que le pseudo existe ou pas */
                if($existingUser){
                    $this->addFlash(
                        'error',
                        'Username already exists. Please choose a different one'
                    );
                }
                else{
                    /* On change le pseudo actuel par le nouveau pseudo */
                    $currentUser->setPseudo($newUsername);
        
                    /* On sauvegarde ces changements dans la base de données */
                    $entityManagerInterface->persist($currentUser);
                    $entityManagerInterface->flush();
        
                    /* On crée un message de succès */
                    $this->addFlash(
                        'success',
                        'Username has been modified successfully'
                    );

                    return $this->redirectToRoute('settings_user');
                }
            }
        }

        /* On affiche la page de changement d'un pseudo avec son formulaire */
        return $this->render('user/changeUsername.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /* Changement de la description de l'utilisateur connecté */
    #[Route(path: 'user/settings/changeDescription', name: 'change_description_user')]
    public function changeDescription(Request $request, EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement de description */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
        }

        /* Création du formulaire */
        $form = $this->createForm(ChangeDescriptionFormType::class, null, [
            'currentDescription' => $currentUser->getDescription(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère la description du formulaire */
            $newDescription = $form->get('description')->getData();

            /* On vérifie si la description indiqué est le même ou pas que celui actuelle */
            if($newDescription === $currentUser->getDescription()){
                $this->addFlash(
                    'error',
                    'The new description cannot be the same as the current one'
                );
            }
            else{
                /* On change la description actuelle par la nouvelle */
                $currentUser->setDescription($newDescription);

                /* On sauvegarde ces changements dans la base de données */
                $entityManagerInterface->persist($currentUser);
                $entityManagerInterface->flush();
        
                /* On crée un message de succès */
                $this->addFlash(
                    'success',
                    'Description has been modified successfully'
                );

                return $this->redirectToRoute('settings_user');
            }

        }

        /* On affiche la page de changement de la description avec son formulaire */
        return $this->render('user/changeDescription.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/{id}', name: 'show_user')]
    public function show(User $user, HomeCallApiService $homeCallApiService): Response{
        return $this->render('user/show.html.twig',  [
            'user' => $user,
            'dataTopTenAnime' => $homeCallApiService->getTopTenAnime(), // Test visuel uniquement, à retirer
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
                return $this->redirectToRoute('settings_user', ['id' => $currentUser->getId()]);
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

    /* Changement de l'image de profil de l'utilisateur connecté */
    #[Route(path: 'user/{id}/settings/changeProfilePicture', name: 'change_profile_picture_user')]
    public function changeProfilePicture(Request $request, User $user, EntityManagerInterface $entityManagerInterface, SluggerInterface $slugger): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* On vérifie que l'utilisateur actuel est bien celui qui accéde à sa page de changement d'image de profil */
        if($currentUser !== $user){
            throw new AccessDeniedException();
        }
        /* Création du formulaire */
        $form = $this->createForm(ChangeProfilePictureFormType::class);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère l'image de profil actuelle */
            $currentProfilePicture = $user->getImageProfil();

            /* On supprime l'image actuel dans le stockage local */
            if($currentProfilePicture){
                unlink($this->getParameter('uploads_directory') . "/" . $currentProfilePicture);
            }

            /* On récupère le possible fichier */
            $submittedProfilePicture = $form->get('imageProfil')->getData();

            /* Et on le traite si il y a bien un fichier à upload */
            if($submittedProfilePicture){
                /* On récupère le chemein de fichier */
                $originalFileName = pathinfo($submittedProfilePicture->getClientOriginalName(), PATHINFO_FILENAME);
                /* Pour inclure le nom du fichier dans l'url de manière sécurisé */
                $safeFileName = $slugger->slug($originalFileName);
                /* On crée un nom unique de fichier */
                $newFilename = $safeFileName . '-' . uniqid() . '.' . $submittedProfilePicture->guessExtension();

                /* On deplace le fichier vers le dossier uploads */
                try{
                    $submittedProfilePicture->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                }catch(FileException $e){
                    error_log($e->getMessage());
                }
                /* On stocke le nom du fichier */
                $user->setImageProfil($newFilename);
            }
            else{
                /* On ne met pas d'image */
                $user->setImageProfil(null);
            }

            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();

            /* On crée un message de succès */
            $this->addFlash(
                'success',
                'Profile Picture has been modified successfully'
            );

            return $this->redirectToRoute('settings_user', ['id' => $currentUser->getId()]);
        }

        /* On affiche la page de changement de l'image de profil avec son formulaire */
        return $this->render('user/changeProfilePicture.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /* Changement des infos de l'utilisateur connecté */
    #[Route(path: 'user/{id}/settings/changeInfos', name: 'change_infos_user')]
    public function changeInfos(Request $request, User $user, EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* On vérifie que l'utilisateur actuel est bien celui qui accéde à sa page de changement des infos */
        if($currentUser !== $user){
            throw new AccessDeniedException();
        }

        /* Création du formulaire */
        $form = $this->createForm(ChangeInfosFormType::class, null, [
            'currentDateOfBirth' => $user->getDateNaissance(),
            'currentCountry' => $user->getPays(),
            'currentCity' => $user->getVille(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère les différentes données du formulaire */
            $newDateOfBirth = $form->get('dateNaissance')->getData();
            $newCountry = $form->get('pays')->getData();
            $newCity = $form->get('ville')->getData();

            /* On vérifie si les infos indiquées sont tous les même ou pas que ceux actuellement */
            if($newDateOfBirth === $user->getDateNaissance() && $newCountry === $user->getPays() && $newCity === $user->getVille()){
                $this->addFlash(
                    'error',
                    'All infos cannot be the same as the current ones'
                );
            }
            else{
                /* Si on a une date de naissance différente qu'actuellement */
                if($newDateOfBirth !== $user->getDateNaissance()){
                    /* On change la date de naissance actuelle par la nouvelle */
                    $user->setDateNaissance($newDateOfBirth);
                }

                /* Si on a un pays différent qu'actuellement */
                if($newCountry !== $user->getPays()){
                    /* On change le pays actuel par le nouveau */
                    $user->setPays($newCountry);
                }

                /* Si on a une ville différente qu'actuellement */
                if($newCity !== $user->getVille()){
                    /* On change la ville actuelle par la nouvelle */
                    $user->setVille($newCity);
                }

                /* On sauvegarde ces changements dans la base de données */
                $entityManagerInterface->persist($user);
                $entityManagerInterface->flush();
        
                /* On crée un message de succès */
                $this->addFlash(
                    'success',
                    'Infos have been modified successfully'
                );

                return $this->redirectToRoute('settings_user', ['id' => $currentUser->getId()]);
            }
        }

        /* On affiche la page de changement des infos avec son formulaire */
        return $this->render('user/changeInfos.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /* Changement de l'email de l'utilisateur connecté */
    #[Route(path: 'user/{id}/settings/changeEmail', name: 'change_email_user')]
    public function changeEmail(Request $request, User $user, EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* On vérifie que l'utilisateur actuel est bien celui qui accéde à sa page de changement d'email */
        if($currentUser !== $user){
            throw new AccessDeniedException();
        }

        /* Création du formulaire */
        $form = $this->createForm(ChangeEmailFormType::class, null, [
            'currentEmail' => $user->getEmail(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère l'email du formulaire */
            $newEmail = $form->get('newEmail')->getData();

            /* On vérifie si l'email indiqué est le même ou pas que celui actuelle */
            if($newEmail === $user->getEmail()){
                $this->addFlash(
                    'error',
                    'The new email cannot be the same as the current one'
                );
            }
            else{
                /* On chercher l'existance de l'email indiqué */
                $existingEmail = $entityManagerInterface->getRepository(User::class)->findOneBy(['email' => $newEmail]);

                /* On vérifie que l'email existe ou pas */
                if($existingEmail){
                    $this->addFlash(
                        'error',
                        'Email already exists. Please choose a different one'
                    );
                }
                else{
                    /* On change l'email actuel par le nouvelle email et l'utilisateur n'est plus vérifié */
                    $user->setEmail($newEmail);
                    $user->setIsVerified(false);
        
                    /* On sauvegarde ces changements dans la base de données */
                    $entityManagerInterface->persist($user);
                    $entityManagerInterface->flush();
        
                    /* On crée un message de succès */
                    $this->addFlash(
                        'success',
                        'Email has been modified successfully, please confirm that email'
                    );

                    $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                        (new TemplatedEmail())
                            ->from(new Address('admin@AnimeProjetElan.com', 'Admin Anime Projet Elan'))
                            ->to($user->getEmail())
                            ->subject('Please Confirm your New Email')
                            ->htmlTemplate('registration/confirmation_email.html.twig')
                    );

                    return $this->redirectToRoute('app_logout');
                }
            }
        }

        /* On affiche la page de changement d'email avec son formulaire */
        return $this->render('user/changeEmail.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
