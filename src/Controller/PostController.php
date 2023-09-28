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

class PostController extends AbstractController
{
    #[Route('/post', name: 'app_post')]
    public function index(): Response
    {
        return $this->render('post/index.html.twig', [
            'controller_name' => 'PostController',
        ]);
    }

    #[Route('/discussion/{id}/post/add', name: 'add_post')]
    public function add(EntityManagerInterface $entityManagerInterface, Discussion $discussion, Request $request): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté, on l'empeche de créer des discussions */
        if(!$user || $discussion->isEstVerrouiller()){
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
            $post->setDateCreation(new \DateTime());
            $post->setDateDerniereModification(new \DateTime());
            $post->setContenu($form->get('content')->getData());
            $post->setUser($user);
            $post->setDiscussion($discussion);

            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($post);
            $entityManagerInterface->flush();

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

    #[Route('/discussion/{discussion_id}/post/{id}/edit', name: 'edit_post')]
    public function edit(EntityManagerInterface $entityManagerInterface, Discussion $discussion_id, Post $post, Request $request): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté
           ou que le post n'a pas été crée par lui
           ou que la discussion est verrouiller
           ou que le post est le premier de la discussion,
           on l'empeche de modifier le post */
        if(!$user || $user !== $post->getUser() || $discussion_id->isEstVerrouiller() || $post === $discussion_id->getPosts()->first()){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are not allowed to edit this post'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion_id->getId()]);
        }

        /* Création du formulaire */
        $form = $this->createForm(PostFormType::class, null, [
            'content' => $post->getContenu(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère les différentes données du formulaire */
            $postContent = $form->get('content')->getData();

            /* On vérifie si les données indiquées sont tous les même ou pas que ceux actuelles */
            if($postContent === $post->getContenu()){
                $this->addFlash(
                    'error',
                    'You need to change the content of the post'
                );
            }
            else{
                /* On modifie le contenu du post et sa date de dernière modification */
                $post->setContenu($postContent);
                $post->setDateDerniereModification(new \DateTime());
    
                /* On sauvegarde ces changements dans la base de données */
                $entityManagerInterface->persist($post);
                $entityManagerInterface->flush();
    
                /* On indique le succès de l'édition */
                $this->addFlash(
                    'success',
                    'The post has been edited successfully'
                );
    
                return $this->redirectToRoute('show_discussion', ['id' => $discussion_id->getId()]);
            }
        }

        return $this->render('post/add.html.twig', [
            'form' => $form->createView(),
            'postExist' => true,
        ]);
    }

    #[Route('/discussion/{discussion_id}/post/{id}/delete', name: 'delete_post')]
    public function delete(EntityManagerInterface $entityManagerInterface, Discussion $discussion_id, Post $post): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté
           ou que le post n'a pas été crée par lui et que ce n'est pas un admin
           ou que le post est le premier de la discussion
           ou que la discussion est verrouillé et que l'utilisateur n'est pas un admin,
           on l'empeche de supprimer le post */
        if(!$user || $user !== $post->getUser() && $this->isGranted('ROLE_ADMIN') === false || $post === $discussion_id->getPosts()->first() || $discussion_id->isEstVerrouiller() && $this->isGranted('ROLE_ADMIN') === false){
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

        /* On indique la réussite de la suppression */
        $this->addFlash(
            'success',
            'The post has been deleted successfully'
        );

        return $this->redirectToRoute('show_discussion', ['id' => $discussion_id->getId()]);
    }
}
