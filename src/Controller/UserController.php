<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Anime;
use App\Security\EmailVerifier;
use App\Form\ChangeEmailFormType;
use App\Form\ChangeInfosFormType;
use App\Repository\AnimeRepository;
use App\Service\CharacterCallApiService;
use Symfony\Component\Mime\Address;
use App\Form\ChangePasswordFormType;
use App\Form\ChangeUsernameFormType;
use App\Service\AnimeCallApiService;
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

    /* Changement de l'image de profil de l'utilisateur connecté */
    #[Route(path: 'user/settings/changeProfilePicture', name: 'change_profile_picture_user')]
    public function changeProfilePicture(Request $request, EntityManagerInterface $entityManagerInterface, SluggerInterface $slugger): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement d'image de profil */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
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
    #[Route(path: 'user/settings/changeEmail', name: 'change_email_user')]
    public function changeEmail(Request $request, EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement d'email */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
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
        
                    /* On crée un message de succès */
                    $this->addFlash(
                        'success',
                        'Email has been modified successfully, please confirm that email'
                    );

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

    #[Route(path: 'user/settings/changePassword', name: 'change_password_user')]
    public function changePassword(Request $request, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $hasher): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();
        
        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement de mot de passe */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
        }

        /* Création du formulaire */
        $form = $this->createForm(ChangePasswordFormType::class);

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

                /* On crée un message de succès */
                $this->addFlash(
                    'success',
                    'Password has been modified successfully'
                );
                return $this->redirectToRoute('app_logout');
            }
            else{
                /* On crée un message d'echec */
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

    /* Changement des infos de l'utilisateur connecté */
    #[Route(path: 'user/settings/changeInfos', name: 'change_infos_user')]
    public function changeInfos(Request $request, EntityManagerInterface $entityManagerInterface): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur n'est pas connecté, il n'a pas accès aux paramètres de changement des infos */
        if(!$currentUser){
            return $this->redirectToRoute('app_home');
        }

        /* Création du formulaire */
        $form = $this->createForm(ChangeInfosFormType::class, null, [
            'currentDateOfBirth' => $currentUser->getDateNaissance(),
            'currentCountry' => $currentUser->getPays(),
            'currentCity' => $currentUser->getVille(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère les différentes données du formulaire */
            $newDateOfBirth = $form->get('dateNaissance')->getData();
            $newCountry = $form->get('pays')->getData();
            $newCity = $form->get('ville')->getData();

            /* On vérifie si les infos indiquées sont tous les même ou pas que ceux actuelles */
            if($newDateOfBirth === $currentUser->getDateNaissance() && $newCountry === $currentUser->getPays() && $newCity === $currentUser->getVille()){
                $this->addFlash(
                    'error',
                    'All infos cannot be the same as the current ones'
                );
            }
            else{
                /* Si on a une date de naissance différente qu'actuellement */
                if($newDateOfBirth !== $currentUser->getDateNaissance()){
                    /* On change la date de naissance actuelle par la nouvelle */
                    $currentUser->setDateNaissance($newDateOfBirth);
                }

                /* Si on a un pays différent qu'actuellement */
                if($newCountry !== $currentUser->getPays()){
                    /* On change le pays actuel par le nouveau */
                    $currentUser->setPays($newCountry);
                }

                /* Si on a une ville différente qu'actuellement */
                if($newCity !== $currentUser->getVille()){
                    /* On change la ville actuelle par la nouvelle */
                    $currentUser->setVille($newCity);
                }

                /* On sauvegarde ces changements dans la base de données */
                $entityManagerInterface->persist($currentUser);
                $entityManagerInterface->flush();
        
                /* On crée un message de succès */
                $this->addFlash(
                    'success',
                    'Infos have been modified successfully'
                );

                return $this->redirectToRoute('settings_user');
            }
        }

        /* On affiche la page de changement des infos avec son formulaire */
        return $this->render('user/changeInfos.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/{id}', name: 'show_user')]
    public function show(User $user, AnimeCallApiService $animeCallApiService, CharacterCallApiService $characterCallApiService): Response{
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
            'favoriteAnimesData' => $favoriteAnimesData['data']['Page']['media'],
            'favoriteCharactersData' => $favoriteCharactersData['data']['Page']['characters'],
        ]);
    }

    #[Route('user/addAnimeToFavorites/{idApi}', name: 'add_anime_to_favorites_user')]
    public function addAnimeToFavorites(int $idApi, AnimeRepository $animeRepository, EntityManagerInterface $entityManagerInterface, AnimeCallApiService $animeCallApiService): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté, on l'empeche d'ajouter à sa liste */
        if(!$user){
            return $this->redirectToRoute('app_home');
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

        return $this->redirectToRoute('show_user', ['id' => $user->getId()]);
    }

    #[Route('user/removeAnimeFromFavorites/{id}', name: 'remove_anime_from_favorites_user')]
    public function removeAnimeFromFavorites(Anime $anime, AnimeRepository $animeRepository, EntityManagerInterface $entityManagerInterface): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté, on l'empeche d'ajouter à sa liste */
        if(!$user){
            return $this->redirectToRoute('app_home');
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

        /* On enlève l'animé à la collection d'animé d'un utilisateur (et donc à ses favoris) */
        $user->removeAnime($animeInDatabase);

        /* On sauvegarde ces changements dans la base de données */
        $entityManagerInterface->persist($user);
        $entityManagerInterface->flush();

        return $this->redirectToRoute('show_user', ['id' => $user->getId()]);
    }

    // #[Route('user/addCharacterToFavorites/{idApi}', name: 'remove_anime_from_favorites_user')]
    // public function addCharacterToFavorites(int $idApi){}
}
