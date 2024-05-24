<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Post;
use App\Form\PostFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/discussion')]
class PostController extends AbstractController
{
    #[Route('/{id}/post/add', name: 'add_post')]
    public function add(EntityManagerInterface $entityManagerInterface, Discussion $discussion, Request $request, CacheInterface $cache): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté, on l'empeche de créer des discussions */
        if(!$user || $discussion->isLocked()){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'Your are not allowed to add a post to this talk'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
        }

        /* On crée un nouveau post */
        $post = new Post();

        /* Création du formulaire */
        $form = $this->createForm(PostFormType::class);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);
        
        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            $post->setCreationDate(new \DateTime());
            $post->setLastModifiedDate(new \DateTime());
            $post->setContent($form->get('content')->getData());
            $post->setUser($user);
            $post->setDiscussion($discussion);

            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($post);
            $entityManagerInterface->flush();

            $cache->delete('users_most_posts_created');

            /* On indique le succès de la création */
            $this->addFlash(
                'success',
                'Your post has been added to the talk successfully'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
        }

        return $this->render('post/add.html.twig', [
            'form' => $form->createView(),
            'postExist' => false
        ]);
    }

    #[Route('/{discussion_id}/post/{id}/edit', name: 'edit_post')]
    public function edit(EntityManagerInterface $entityManagerInterface, Discussion $discussion_id, Post $post, Request $request): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté
           ou que le post n'a pas été crée par lui
           ou que la discussion est verrouiller
           ou que le post est le premier de la discussion,
           on l'empeche de modifier le post */
        if(!$user || $user !== $post->getUser() || $discussion_id->isLocked() || ($post === $discussion_id->getPosts()->first() && $discussion_id->getAnime() === null) || !$post->getUser()){
            /* On indique l'interdicti on */
            $this->addFlash(
                'error',
                'You are not allowed to edit this post'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion_id->getId()]);
        }

        /* Création du formulaire */
        $form = $this->createForm(PostFormType::class, null, [
            'content' => $post->getContent(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère les différentes données du formulaire */
            $postContent = $form->get('content')->getData();

            /* On vérifie si les données indiquées sont tous les même ou pas que ceux actuelles */
            if($postContent === $post->getContent()){
                $this->addFlash(
                    'error',
                    'You need to change the content of the post'
                );
            }
            else{
                /* On modifie le contenu du post et sa date de dernière modification */
                $post->setContent($postContent);
                $post->setLastModifiedDate(new \DateTime());
    
                /* On sauvegarde ces changements dans la base de données */
                $entityManagerInterface->persist($post);
                $entityManagerInterface->flush();
    
                /* On indique le succès de l'édition */
                $this->addFlash(
                    'success',
                    'The post has been edited successfully'
                );
    
                /* Adaptation de la redirection en fonction de si la discusssion est liée à un anime ou pas */
                if($discussion_id->getAnime() !== null){
                    return $this->redirectToRoute('show_anime', ['id' => $discussion_id->getAnime()->getIdApi()]);
                }
                else{
                    return $this->redirectToRoute('show_discussion', ['id' => $discussion_id->getId()]);
                }

            }
        }

        return $this->render('post/add.html.twig', [
            'form' => $form->createView(),
            'postExist' => true,
        ]);
    }

    #[Route('/{discussion_id}/post/{id}/delete', name: 'delete_post')]
    public function delete(EntityManagerInterface $entityManagerInterface, Discussion $discussion_id, Post $post, CacheInterface $cache): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté
           ou que le post n'a pas été crée par lui et que ce n'est pas un admin
           ou que le post est le premier de la discussion
           ou que la discussion est verrouillé et que l'utilisateur n'est pas un admin,
           on l'empeche de supprimer le post */
        if(!$user || $user !== $post->getUser() && !$this->isGranted('ROLE_ADMIN') || ($post === $discussion_id->getPosts()->first() && $discussion_id->getAnime() === null) || $discussion_id->isLocked() && !$this->isGranted('ROLE_ADMIN')){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are not allowed to delete this post'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion_id->getId()]);
        }

        /* On supprime le post et on sauvegarde ces changements dans la base de données */
        $entityManagerInterface->remove($post);
        $entityManagerInterface->flush();

        $cache->delete('users_most_posts_created');

        /* On indique la réussite de la suppression */
        $this->addFlash(
            'success',
            'The post has been deleted successfully'
        );

        
        /* Adaptation de la redirection en fonction de si la discusssion est liée à un anime ou pas */
        if($discussion_id->getAnime() !== null){
            return $this->redirectToRoute('show_anime', ['id' => $discussion_id->getAnime()->getIdApi()]);
        }
        else{
            return $this->redirectToRoute('show_discussion', ['id' => $discussion_id->getId()]);
        }
    }

    #[Route('/{discussion_id}/post/{id}/like', name: 'like_post')]
    public function like(EntityManagerInterface $entityManagerInterface, Discussion $discussion_id, Post $post): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté */
        if(!$user || !$post->getUser()){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are not allowed to like'
            );
        }
        /* Si le post est déjà liké par l'utilisateur */
        elseif($post->getUsers()->contains($user)){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'This post from "' . $post->getUser()->getUsername() . '" is already liked'
            );
        }
        else{
            /* On ajoute l'utilisateur à la collection d'utilisateur du post (qui représente les utilisateur qui ont liké le post) */
            $post->addUser($user);
    
            /* On sauvegarde ces changements en base de données */
            $entityManagerInterface->flush();
    
            /* On indique le succès du like d'un post */
            $this->addFlash(
                'success',
                'This post from "' . $post->getUser()->getUsername() . '" has been liked successfully'
            );
        }
        
        /* Adaptation de la redirection en fonction de si la discusssion est liée à un anime ou pas */
        if($discussion_id->getAnime() !== null){
            return $this->redirectToRoute('show_anime', ['id' => $discussion_id->getAnime()->getIdApi()]);
        }
        else{
            return $this->redirectToRoute('show_discussion', ['id' => $discussion_id->getId()]);
        }
    }

    #[Route('/{discussion_id}/post/{id}/unlike', name: 'unlike_post')]
    public function unlike(EntityManagerInterface $entityManagerInterface, Discussion $discussion_id, Post $post): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté */
        if(!$user || !$post->getUser()){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are not allowed to unlike'
            );
        }
        /* Si le post est déjà non liké par l'utilisateur */
        elseif(!$post->getUsers()->contains($user)){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'This post from "' . $post->getUser()->getUsername() . '" is already not liked'
            );
        }
        else{
            /* On supprime l'utilisateur de la collection d'utilisateur du post (qui représente les utilisateur qui ont liké le post) */
            $post->removeUser($user);
    
            /* On sauvegarde ces changements en base de données */
            $entityManagerInterface->flush();
    
            /* On indique le succès du unlike d'un post */
            $this->addFlash(
                'success',
                'This post from "' . $post->getUser()->getUsername() . '" has been unliked successfully'
            );
        }

        /* Adaptation de la redirection en fonction de si la discusssion est liée à un anime ou pas */
        if($discussion_id->getAnime() !== null){
            return $this->redirectToRoute('show_anime', ['id' => $discussion_id->getAnime()->getIdApi()]);
        }
        else{
            return $this->redirectToRoute('show_discussion', ['id' => $discussion_id->getId()]);
        }
    }
}
