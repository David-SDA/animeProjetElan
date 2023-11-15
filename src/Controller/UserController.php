<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Anime;
use App\Entity\Personnage;
use App\Security\EmailVerifier;
use App\Form\ChangeEmailFormType;
use App\Form\ChangeInfosFormType;
use App\Repository\AnimeRepository;
use Symfony\Component\Mime\Address;
use App\Form\ChangeUsernameFormType;
use App\Form\ModifyPasswordFormType;
use App\Service\AnimeCallApiService;
use App\Form\ChangeDescriptionFormType;
use App\Repository\EvenementRepository;
use App\Repository\PersonnageRepository;
use App\Service\CharacterCallApiService;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ChangeProfilePictureFormType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRegarderAnimeRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier){
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/settings', name: 'settings_user')]
    public function settings(): Response{
        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres */
        if(!$this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        return $this->render('user/settings.html.twig');
    }

    #[Route('/calendar', name: 'calendar_user')]
    public function calendar(EvenementRepository $evenementRepository): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();
        
        /* Si l'utilisateur n'est pas connecté, il n'a pas accès à son calendrier */
        if(!$currentUser){
            /* On l'indique */
            $this->addFlash(
                'error',
                'Access denied'
            );

            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        /* On récupère tout les évènements d'un utilisateur */
        $events = $evenementRepository->findBy(['user' => $currentUser]);

        /* On crée un tableau pour fournir au calendrier les évènement sous un format qu'il reconnaît */
        $eventsData = [];

        /* On boucle sur les évènements récuperer */
        foreach($events as $event){
            if($event->isEstRecurrent()){
                $eventsData[] = [
                    'id' => $event->getId(),
                    'daysOfWeek' => $event->getDateDebut()->format('N'),
                    'startRecur' => $event->getDateDebut()->format('Y-m-d'),
                    'startTime' => $event->getDateDebut()->format('H:i'),
                    'endRecur' => $event->getDateFin()->format('Y-m-d'),
                    'endTime' => $event->getDateFin()->format('H:i'),
                    'title' => $event->getNomEvenement(),
                ];
            }
            else{
                /* On ajoute au tabeau les informations nécessaires */
                $eventsData[] = [
                    'id' => $event->getId(),
                    'start' => $event->getDateDebut()->format('Y-m-d H:i:s'),
                    'end' => $event->getDateFin()->format('Y-m-d H:i:s'),
                    'title' => $event->getNomEvenement(),
                ];
            }
        }

        /* Conversion en chaîne de caractère json */
        $dataEvents = json_encode($eventsData);

        return $this->render('user/calendar.html.twig', [
            'dataEvents' => $dataEvents,
        ]);
    }

    /* Changement du pseudo de l'utilisateur connecté */
    #[Route('/settings/changeUsername', name: 'change_username_user')]
    public function changeUsername(Request $request, EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement de pseudo */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
        }
        
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isEstBanni()){
            return $this->redirectToRoute('app_banned');
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
    #[Route('/settings/changeDescription', name: 'change_description_user')]
    public function changeDescription(Request $request, EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement de description */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isEstBanni()){
            return $this->redirectToRoute('app_banned');
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

    /* Changement de l'image de profil de l'utilisateur connecté */
    #[Route('/settings/changeProfilePicture', name: 'change_profile_picture_user')]
    public function changeProfilePicture(Request $request, EntityManagerInterface $entityManagerInterface, SluggerInterface $slugger): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement d'image de profil */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        /* Création du formulaire */
        $form = $this->createForm(ChangeProfilePictureFormType::class);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère l'image de profil actuelle */
            $currentProfilePicture = $currentUser->getImageProfil();

            /* On supprime l'image actuel dans le stockage local */
            if($currentProfilePicture){
                unlink($this->getParameter('uploads_directory') . "/" . $currentProfilePicture);
            }

            /* On récupère le possible fichier */
            $submittedProfilePicture = $form->get('imageProfil')->getData();

            /* Et on le traite si il y a bien un fichier à upload */
            if($submittedProfilePicture){
                /* On récupère le chemin de fichier */
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
                $currentUser->setImageProfil($newFilename);
            }
            else{
                /* On ne met pas d'image */
                $currentUser->setImageProfil(null);
            }

            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($currentUser);
            $entityManagerInterface->flush();

            /* On crée un message de succès */
            $this->addFlash(
                'success',
                'Profile Picture has been modified successfully'
            );

            return $this->redirectToRoute('settings_user');
        }

        /* On affiche la page de changement de l'image de profil avec son formulaire */
        return $this->render('user/changeProfilePicture.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /* Changement de l'email de l'utilisateur connecté */
    #[Route('/settings/changeEmail', name: 'change_email_user')]
    public function changeEmail(Request $request, EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        
        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement d'email */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        /* Création du formulaire */
        $form = $this->createForm(ChangeEmailFormType::class, null, [
            'currentEmail' => $currentUser->getEmail(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère l'email du formulaire */
            $newEmail = $form->get('newEmail')->getData();

            /* On vérifie si l'email indiqué est le même ou pas que celui actuel */
            if($newEmail === $currentUser->getEmail()){
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
                    /* On change l'email actuel par le nouvel email et l'utilisateur n'est plus vérifié */
                    $currentUser->setEmail($newEmail);
                    $currentUser->setIsVerified(false);
        
                    /* On sauvegarde ces changements dans la base de données */
                    $entityManagerInterface->persist($currentUser);
                    $entityManagerInterface->flush();
        
                    $this->emailVerifier->sendEmailConfirmation('app_verify_email', $currentUser,
                        (new TemplatedEmail())
                            ->from(new Address('admin@AnimeProjetElan.com', 'Admin Anime Projet Elan'))
                            ->to($currentUser->getEmail())
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

    #[Route('/settings/modifyPassword', name: 'modify_password_user')]
    public function modifyPassword(Request $request, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $hasher): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();
        
        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement de mot de passe */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
        }
        
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        /* Création du formulaire */
        $form = $this->createForm(ModifyPasswordFormType::class);

        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* Si le mot de passe actuel est bien celui indiqué dans le formulaire, on peut commencer la modification du mot de passe */
            if($hasher->isPasswordValid($currentUser, $form->get('password')->getData())){
                /* On change le mot de passe actuel par le nouveau mot de passe */
                $currentUser->setPassword(
                    /* On hash le mot de passe */
                    $hasher->hashPassword(
                        $currentUser,
                        $form->get('plainPassword')->getData()
                    )
                );

                /* On sauvegarde ces changements dans la base de données */
                $entityManagerInterface->persist($currentUser);
                $entityManagerInterface->flush();

                return $this->redirectToRoute('app_logout');
            }
            else{
                /* On crée un message d'echec */
                $this->addFlash(
                    'error',
                    'Wrong password'
                );
            }
        }

        return $this->render('user/modifyPassword.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/settings/deleteAccount', name:'delete_account_user')]
    public function deleteAccount(EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();
        
        /* Si l'utilisateur n'est pas connecté, il n'a pas accès à la suppression de compte */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
        }
        
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        foreach($currentUser->getDiscussions() as $discussion){
            $discussion->setUser(null);
        }

        foreach($currentUser->getPosts() as $post){
            $post->setUser(null);
        }

        $entityManagerInterface->remove($currentUser);
        $entityManagerInterface->flush();

        return $this->redirectToRoute('app_home');
    }

    #[Route('/{id}', name: 'show_user')]
    public function show(User $user, AnimeCallApiService $animeCallApiService, CharacterCallApiService $characterCallApiService, UserRegarderAnimeRepository $userRegarderAnimeRepository): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        /* Definition de statistiques sur la liste */
        $nbAnimesWatching = $userRegarderAnimeRepository->getNbAnimesStatus($user->getId(), 'Watching');
        $nbAnimesCompleted = $userRegarderAnimeRepository->getNbAnimesStatus($user->getId(), 'Completed');
        $nbAnimesPlanned = $userRegarderAnimeRepository->getNbAnimesStatus($user->getId(), 'Plan to watch');
        
        /* On récupère la collection d'animés d'un utilisateur (qui représente les animés favoris d'un utilisateur) */
        $favoriteAnimes = $user->getAnimes();
        /* On crée un tableau pour stocker les ids de l'API des animés favoris */
        $favoriteAnimesApiIds = [];
        /* Pour chaque animés dans la collection (pour chaque animés favoris) */
        foreach($favoriteAnimes as $anime){
            /* On insère l'id de l'API dans le tableau créé */
            $favoriteAnimesApiIds[] = $anime->getIdApi();
        }
        /* On appelle l'API pour obtenir les données des animés favoris */
        $favoriteAnimesData = $animeCallApiService->getMultipleAnimeDetails($favoriteAnimesApiIds);

        /* On récupère la collection de personnages d'un utilisateur (qui représente les personnages favoris d'un utilisateurs) */
        $favoriteCharacters = $user->getPersonnages();
        /* On crée un tableau pour stocker les ids de l'API des personnages favoris */
        $favoriteCharactersApiIds = [];
        /* Pour chaque personnages dans la collection (pour chaque personnages favoris) */
        foreach($favoriteCharacters as $character){
            /* On insère l'id de l'API dans le tableau crée */
            $favoriteCharactersApiIds[] = $character->getIdApi();
        }
        /* On appelle l'API pour obtenir les données des personnages favoris */
        $favoriteCharactersData = $characterCallApiService->getMultipleCharactersDetails($favoriteCharactersApiIds);

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'nbAnimesWatching' => $nbAnimesWatching,
            'nbAnimesCompleted' => $nbAnimesCompleted,
            'nbAnimesPlanned' => $nbAnimesPlanned,
            'favoriteAnimesData' => $favoriteAnimesData['data']['Page']['media'],
            'favoriteCharactersData' => $favoriteCharactersData['data']['Page']['characters'],
        ]);
    }

    #[Route('/addAnimeToFavorites/{idApi}', name: 'add_anime_to_favorites_user')]
    public function addAnimeToFavorites(int $idApi, AnimeRepository $animeRepository, EntityManagerInterface $entityManagerInterface, AnimeCallApiService $animeCallApiService): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté, on l'empeche d'ajouter à sa liste */
        if(!$user){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        /* On cherche si l'anime est déjà dans la base de données */
        $animeInDatabase = $animeRepository->findOneBy(['idApi' => $idApi]);
        /* Si l'animé n'est pas dans la base de données */
        if(!$animeInDatabase){
            /* Et si l'animé existe dans l'API */
            if($animeCallApiService->getApiResponse($idApi) === Response::HTTP_OK){
                /* On crée une instance d'animé dans lequel on y définit l'id de l'API */
                $animeInDatabase = new Anime();
                $animeInDatabase->setIdApi($idApi);
                $entityManagerInterface->persist($animeInDatabase);
            }
            else{
                /* Sinon on indique que l'animé n'existe pas (dans l'API) */
                $this->addFlash(
                    'error',
                    'This anime does not exist'
                );

                return $this->redirectToRoute('show_anime', ['id' => $idApi]);
            }
        }
        else{
            /* On crée une variable pour déterminer si l'animé est dans les animés favoris de l'utilisateur, on définit que de base, il n'y est pas */
            $animeIsInFavorites = false;
            /* Pour chaque animés dans la collection d'animés de l'utilisateur (qui représente les animés favoris de l'utilisateur) */
            foreach($user->getAnimes() as $anime){
                /* On vérifie si l'id de l'API correspond à l'id de l'animé de la page actuelle */
                if($anime->getIdApi() === $idApi){
                    /* Dès que l'on trouve une correspondance, cela veut dire que l'animé est bien dans les animés favoris de l'utilisateur et on stop la boucle dès que cela arrive */
                    $animeIsInFavorites = true;
                    break;
                }
            }
            /* Si l'animé est déjà dans les favoris de l'utilisateur */
            if($animeIsInFavorites){
                /* On lui indique */
                $this->addFlash(
                    'error',
                    'You cannot add an anime to your favorites if it already is'
                );

                return $this->redirectToRoute('show_anime', ['id' => $idApi]);
            }
        }

        /* On ajoute l'animé à la collection d'animé d'un utilisateur (et donc à ses favoris) */
        $user->addAnime($animeInDatabase);

        /* On sauvegarde ces changements dans la base de données */
        $entityManagerInterface->persist($user);
        $entityManagerInterface->flush();

        /* On indique la réussite du changement */
        $this->addFlash(
            'success',
            'This anime has been added to your favorites'
        );

        return $this->redirectToRoute('show_anime', ['id' => $idApi]);
    }

    #[Route('/addCharacterToFavorites/{idApi}', name: 'add_character_to_favorites_user')]
    public function addCharacterToFavorites(int $idApi, PersonnageRepository $personnageRepository, EntityManagerInterface $entityManagerInterface, CharacterCallApiService $characterCallApiService){
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté, on l'empeche d'ajouter à sa liste */
        if(!$user){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        /* On cherche si le personnage est déjà dans la base de données */
        $characterInDatabase = $personnageRepository->findOneBy(['idApi' => $idApi]);
        /* Si le personnage n'est pas dans la base de données */
        if(!$characterInDatabase){
            /* Et si le personnage existe dans l'API */
            if($characterCallApiService->getApiResponse($idApi) === Response::HTTP_OK){
                /* On crée une instance de personnage dans lequel on y définit l'id de l'API */
                $characterInDatabase = new Personnage();
                $characterInDatabase->setIdApi($idApi);
                $entityManagerInterface->persist($characterInDatabase);
            }
            else{
                /* Sinon on indique que le personnage n'existe pas (dans l'API) */
                $this->addFlash(
                    'error',
                    'This character does not exist'
                );

                return $this->redirectToRoute('show_character', ['id' => $idApi]);
            }
        }
        else{
            /* On crée une variable pour déterminer si le personnage est dans les personnages favoris de l'utilisateur, on définit que de base, il n'y est pas */
            $characterIsInFavorites = false;
            /* Pour chaque personnage dans la collection de personnages de l'utilisateur (qui représente les personnages favoris de l'utilisateur) */
            foreach($user->getPersonnages() as $character){
                /* On vérifie si l'id de l'API correspond à l'id du personnage de la page actuelle */
                if($character->getIdApi() === $idApi){
                    /* Dès que l'on trouve une correspondance, cela veut dire que le personnage est bien dans les personnages favoris de l'utilisateur et on stop la boucle dès que cela arrive */
                    $characterIsInFavorites = true;
                    break;
                }
            }
            /* Si le personnage est déjà dans les favoris de l'utilisateur */
            if($characterIsInFavorites){
                /* On lui indique */
                $this->addFlash(
                    'error',
                    'You cannot add a character to your favorites if it already is'
                );

                return $this->redirectToRoute('show_character', ['id' => $idApi]);
            }
        }

        /* On ajoute le personnage à la collection de personnage d'un utilisateur (et donc à ses favoris) */
        $user->addPersonnage($characterInDatabase);

        /* On sauvegarde ces changements dans la base de données */
        $entityManagerInterface->persist($user);
        $entityManagerInterface->flush();

        /* On indique la réussite du changement */
        $this->addFlash(
            'success',
            'This character has been added to your favorites'
        );

        return $this->redirectToRoute('show_character', ['id' => $idApi]);
    }

    #[Route('/removeAnimeFromFavorites/{id}', name: 'remove_anime_from_favorites_user')]
    public function removeAnimeFromFavorites(Anime $anime, AnimeRepository $animeRepository, EntityManagerInterface $entityManagerInterface): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté, on l'empeche d'ajouter à sa liste */
        if(!$user){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        /* On cherche si l'anime est déjà dans la base de données */
        $animeInDatabase = $animeRepository->find($anime);
        /* Si l'animé n'existe pas */
        if(!$animeInDatabase){
            /* Il ne peut pas être dans les favoris d'un utilisateur, alors on l'indique */
            $this->addFlash(
                'error',
                'This anime does not exist'
            );

            return $this->redirectToRoute('app_home');
        }
        /* Si l'animé est dans les favoris de l'utilisateur */
        elseif($user->getAnimes()->contains($animeInDatabase)){
            /* On enlève l'animé à la collection d'animé d'un utilisateur (et donc à ses favoris) */
            $user->removeAnime($animeInDatabase);
    
            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();
    
            /* On indique la réussite du changement */
            $this->addFlash(
                'success',
                'This anime has been removed from your favorites'
            );
        }
        else{
            /* On indique que l'animé n'est déjà pas dans les favoris */
            $this->addFlash(
                'error',
                'This anime is already not in your favorites'
            );
        }


        return $this->redirectToRoute('show_anime', ['id' => $animeInDatabase->getIdApi()]);
    }

    #[Route('/removeCharacterFromFavorites/{id}', name: 'remove_character_from_favorites_user')]
    public function removeCharacterFromFavorites(Personnage $personnage, PersonnageRepository $personnageRepository, EntityManagerInterface $entityManagerInterface): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté, on l'empeche d'ajouter à sa liste */
        if(!$user){
            return $this->redirectToRoute('app_home');
        }

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        /* On cherche si le personnage est déjà dans la base de données */
        $characterInDatabase = $personnageRepository->find($personnage);
        /* Si le personnage n'existe pas */
        if(!$characterInDatabase){
            /* Il ne peut pas être dans les favoris d'un utilisateur, alors on l'indique */
            $this->addFlash(
                'error',
                'This character does not exist'
            );

            return $this->redirectToRoute('app_home');
        }
        /* Si le personnage est dans les favoris de l'utilisateur */
        elseif($user->getPersonnages()->contains($characterInDatabase)){
            /* On enlève le personnage à la collection de personnage d'un utilisateur (et donc à ses favoris) */
            $user->removePersonnage($characterInDatabase);
    
            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();
    
            /* On indique la réussite du changement */
            $this->addFlash(
                'success',
                'This character has been removed from your favorites'
            );
        }
        else{
            /* On indique que le personnage n'est déjà pas dans les favoris */
            $this->addFlash(
                'error',
                'This character is already not in your favorites'
            );
        }


        return $this->redirectToRoute('show_character', ['id' => $characterInDatabase->getIdApi()]);
    }
}
