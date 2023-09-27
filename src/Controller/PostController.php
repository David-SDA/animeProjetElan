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
            return $this->redirectToRoute('app_home');
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
                'Post has been created successfully'
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
        /* Si l'utilisateur n'est pas connecté ou que le post n'a pas été crée par lui ou que la discussion est verrouiller, on l'empeche de modifier le post */
        if(!$user || $user !== $post->getUser() || $discussion_id->isEstVerrouiller()){
            return $this->redirectToRoute('app_home');
        }

        /* Si on veut modifier le premier post de la discussion */
        if($post === $discussion_id->getPosts()->first()){
            /* On indique qu'il faut passer par la modification de la discussion */
            $this->addFlash(
                'error',
                'The first post of this talk can be edited only through the edition of this talk'
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
}
