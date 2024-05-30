<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Post;
use App\Form\DiscussionFilterFormType;
use App\Form\DiscussionFormType;
use App\Repository\DiscussionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/discussion')]
class DiscussionController extends AbstractController
{
    #[Route('', name: 'app_discussion')]
    public function index(DiscussionRepository $discussionRepository, Request $request): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Création du formulaire pour le filtre */
        $form = $this->createForm(DiscussionFilterFormType::class);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);
        
        /* On cherche toutes les discussions */
        $talks = $discussionRepository->discussionsWithoutAnime('creationDate', 'DESC');

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On traite les différents choix */
            switch($form->get('filter')->getData()){
                /* Cas des titres de A à Z */
                case 'titreAsc':
                    $talks = $discussionRepository->discussionsWithoutAnime('title', 'ASC');
                    break;
                
                /* Cas des titres de Z à A */
                case 'titreDesc':
                    $talks = $discussionRepository->discussionsWithoutAnime('title', 'DESC');
                    break;

                /* Cas des date de creation des plus ancien aux plus récent */
                case 'dateCreationAsc':
                    $talks = $discussionRepository->discussionsWithoutAnime('creationDate', 'ASC');
                    break;

                /* Cas des date de creation des plus récent aux plus ancien */
                case 'dateCreationDesc':
                    $talks = $discussionRepository->discussionsWithoutAnime('creationDate', 'DESC');
                    break;

                /* Cas du nombre de posts du plus petit au plus grand */
                case 'postsAsc':
                    $talks = $discussionRepository->talksByPostsNumber('ASC');
                    break;
                
                /* Cas du nombre de posts du plus grand au plus petit */
                case 'postsDesc':
                    $talks = $discussionRepository->talksByPostsNumber('DESC');
                    break;

                /* Cas par défaut : cas dateCreationDesc */
                default:
                    $talks = $discussionRepository->discussionsWithoutAnime('creationDate', 'DESC');
                    break;
            }
        }

        return $this->render('discussion/index.html.twig', [
            'form' => $form->createView(),
            'talks' => $talks,
        ]);
    }

    #[Route('/add', name: 'add_discussion')]
    public function add(EntityManagerInterface $entityManagerInterface, Request $request, CacheInterface $cache): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté, on l'empeche de créer des discussions */
        if(!$user){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are allowed to create a new talk'
            );

            return $this->redirectToRoute('app_discussion');
        }

        /* On crée une discussion et un premier post */
        $discussion = new Discussion();
        $firstPost = new Post();
        
        /* Création du formulaire */
        $form = $this->createForm(DiscussionFormType::class);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* Ajout des données à l'entité discussion */
            $discussion->setTitle($form->get('title')->getData());
            $discussion->setCreationDate(new \DateTime());
            $discussion->setLocked(false);
            $discussion->setUser($user);

            /* Ajout des données à l'entité post */
            $firstPost->setCreationDate(new \DateTime());
            $firstPost->setLastModifiedDate(new \DateTime());
            $firstPost->setContent($form->get('firstPost')->getData());
            $firstPost->setUser($user);
            $firstPost->setDiscussion($discussion);

            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($discussion);
            $entityManagerInterface->persist($firstPost);
            $entityManagerInterface->flush();

            $cache->delete('users_most_talks_created');

            /* On indique le succès de la création */
            $this->addFlash(
                'success',
                'A new talk has been created successfully'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
        }

        return $this->render('discussion/add.html.twig', [
            'form' => $form->createView(),
            'discussionExist' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit_discussion')]
    public function edit(EntityManagerInterface $entityManagerInterface, Discussion $discussion, Request $request): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté ou que la discussion n'a pas été crée par lui ou que la discussion est verrouiller, on l'empeche de modifier la discussion */
        if(!$user || $user !== $discussion->getUser() || $discussion->isLocked() || !$discussion->getUser()){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are not allowed to edit this talk'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
        }

        /* Création du formulaire */
        $form = $this->createForm(DiscussionFormType::class, null, [
            'title' => $discussion->getTitle(),
            'firstPostContent' => $discussion->getPosts()->first()->getContent(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère les différentes données du formulaire */
            $talkTitle = $form->get('title')->getData();
            $talkFirstPost = $form->get('firstPost')->getData();

            /* On vérifie si les données indiquées sont tous les même ou pas que ceux actuelles */
            if($talkTitle === $discussion->getTitle() && $talkFirstPost === $discussion->getPosts()->first()->getContent()){
                $this->addFlash(
                    'error',
                    'You need to change something to edit the talk'
                );
            }
            else{
                /* On modifie le titre de la discussion */
                $discussion->setTitle($talkTitle);
    
                /* On modifie le contenu et la date de dernière modification du premier post de la discussion */
                $discussion->getPosts()->first()->setContent($talkFirstPost);
                $discussion->getPosts()->first()->setLastModifiedDate(new \DateTime());
    
                /* On sauvegarde ces changements dans la base de données */
                $entityManagerInterface->persist($discussion);
                $entityManagerInterface->flush();
    
                /* On indique le succès de l'édition */
                $this->addFlash(
                    'success',
                    'This talk has been edited successfully'
                );
    
                return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
            }
        }

        return $this->render('discussion/add.html.twig', [
            'form' => $form->createView(),
            'discussionExist' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete_discussion')]
    public function delete(EntityManagerInterface $entityManagerInterface, Discussion $discussion, CacheInterface $cache): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté
           ou que la discussion n'a pas été crée par lui et que ce n'est pas un admin
           ou que la discussion est verrouillé et que l'utilisateur n'est pas un admin,
           on l'empeche de supprimer la discussion */
        if(!$user
            || $user !== $discussion->getUser() && !$this->isGranted('ROLE_ADMIN')
            || $discussion->isLocked() && !$this->isGranted('ROLE_ADMIN')){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are not allowed to delete this talk'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
        }

        /* On récupère le titre de la discussion qui va être supprimer */
        $talkTitle = $discussion->getTitle();

        /* On supprime la discussion et on sauvegarde ces changements dans la base de données */
        $entityManagerInterface->remove($discussion);
        $entityManagerInterface->flush();

        $cache->delete('users_most_talks_created');

        /* On indique la réussite de la suppression */
        $this->addFlash(
            'success',
            'The talk "' . $talkTitle . '" has been deleted successfully'
        );

        return $this->redirectToRoute('app_discussion');
    }

    #[Route('/{id}/lock', name: 'lock_discussion')]
    public function lock(EntityManagerInterface $entityManagerInterface, Discussion $discussion): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas un admin et que la discussion est déjà verrouillé, il n'est pas autorisé à verrouiller la discussion */
        if(!$this->isGranted('ROLE_ADMIN') && $discussion->isLocked()){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are not allowed to lock this talk'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
        }

        /* On verrouille la discussion */
        $discussion->setLocked(true);

        /* On sauvegarde ces changements dans la base de données */
        $entityManagerInterface->persist($discussion);
        $entityManagerInterface->flush();

        /* On indique la réussite du verrouillage */
        $this->addFlash(
            'success',
            'The talk has been locked successfully'
        );

        return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
    }

    #[Route('/{id}/unlock', name: 'unlock_discussion')]
    public function unlock(EntityManagerInterface $entityManagerInterface, Discussion $discussion): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas un admin et que la discussion n'est déjà pas verrouiller , il n'est pas autorisé à déverrouiller la discussion */
        if(!$this->isGranted('ROLE_ADMIN') && !$discussion->isLocked()){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are not allowed to unlock this talk'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
        }

        /* On déverrouille la discussion */
        $discussion->setLocked(false);

        /* On sauvegarde ces changements dans la base de données */
        $entityManagerInterface->persist($discussion);
        $entityManagerInterface->flush();

        /* On indique la réussite du déverrouillage */
        $this->addFlash(
            'success',
            'The talk has been unlocked successfully'
        );

        return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
    }    

    #[Route('/{id}', name: 'show_discussion')]
    public function show(Request $request, Discussion $discussion): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si la discussion est liée à un anime, on le redirige vers la page de l'anime en question */
        if($discussion->getAnime()){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'Action failed'
            );
            
            return $this->redirectToRoute('show_anime', ['id' => $discussion->getAnime()->getIdApi()]);
        }
        
        // Séléction de tri et ordre par défaut
        $sort = $request->query->get('sort', 'date');
        $order = $request->query->get('order', 'desc');
    
        // Obtention des discussion sous forme de tableau
        $posts = $discussion->getPosts()->toArray();
    
        // Tri des post avec le option de base
        usort($posts, function($a, $b) use ($sort, $order){
            if($sort === 'date'){
                $aValue = $a->getCreationDate()->getTimestamp();
                $bValue = $b->getCreationDate()->getTimestamp();
            } elseif($sort === 'likes'){
                $aValue = $a->getUsers()->count();
                $bValue = $b->getUsers()->count();
            }
            
            return ($order === 'asc') ? $aValue <=> $bValue : $bValue <=> $aValue;
        });
    
        return $this->render('discussion/show.html.twig', [
            'talk' => $discussion,
            'posts' => $posts,
            'currentSort' => $sort,
            'currentOrder' => $order,
        ]);
    }
}
