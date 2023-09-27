<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Post;
use App\Form\DiscussionFormType;
use App\Repository\DiscussionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DiscussionController extends AbstractController
{
    #[Route('/discussion', name: 'app_discussion')]
    public function index(DiscussionRepository $discussionRepository): Response
    {
        /* On cherche toutes les discussions */
        $talks = $discussionRepository->findBy([], ["dateCreation" => "ASC"]);

        return $this->render('discussion/index.html.twig', [
            'talks' => $talks,
        ]);
    }

    #[Route('/discussion/add', name: 'add_discussion')]
    public function add(EntityManagerInterface $entityManagerInterface, Request $request): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté, on l'empeche de créer des discussions */
        if(!$user){
            return $this->redirectToRoute('app_home');
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
            $discussion->setTitre($form->get('title')->getData());
            $discussion->setDateCreation(new \DateTime());
            $discussion->setEstVerrouiller(false);
            $discussion->setUser($user);

            /* Ajout des données à l'entité post */
            $firstPost->setDateCreation(new \DateTime());
            $firstPost->setDateDerniereModification(new \DateTime());
            $firstPost->setContenu($form->get('firstPost')->getData());
            $firstPost->setUser($user);
            $firstPost->setDiscussion($discussion);

            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($discussion);
            $entityManagerInterface->persist($firstPost);
            $entityManagerInterface->flush();

            /* On indique le succès de la création */
            $this->addFlash(
                'success',
                'Talk has been created successfully'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
        }

        return $this->render('discussion/add.html.twig', [
            'form' => $form->createView(),
            'discussionExist' => false,
        ]);
    }

    #[Route('/discussion/{id}/edit', name: 'edit_discussion')]
    public function edit(EntityManagerInterface $entityManagerInterface, Discussion $discussion, Request $request): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté ou que la discussion n'a pas été crée par lui, on l'empeche de modifier la discussion */
        if(!$user || $user !== $discussion->getUser()){
            return $this->redirectToRoute('app_home');
        }

        /* Création du formulaire */
        $form = $this->createForm(DiscussionFormType::class, null, [
            'title' => $discussion->getTitre(),
            'firstPostContent' => $discussion->getPosts()->first()->getContenu(),
        ]);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On récupère les différentes données du formulaire */
            $talkTitle = $form->get('title')->getData();
            $talkFirstPost = $form->get('firstPost')->getData();

            /* On vérifie si les données indiquées sont tous les même ou pas que ceux actuelles */
            if($talkTitle === $discussion->getTitre() && $talkFirstPost === $discussion->getPosts()->first()->getContenu()){
                $this->addFlash(
                    'error',
                    'You need to change something to edit the talk'
                );
            }
            else{
                /* On modifie le titre de la discussion */
                $discussion->setTitre($talkTitle);
    
                /* On modifie le contenu et la date de dernière modification du premier post de la discussion */
                $discussion->getPosts()->first()->setContenu($talkFirstPost);
                $discussion->getPosts()->first()->setDateDerniereModification(new \DateTime());
    
                /* On sauvegarde ces changements dans la base de données */
                $entityManagerInterface->persist($discussion);
                $entityManagerInterface->flush();
    
                /* On indique le succès de la création */
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

    #[Route('/discussion/{id}/delete', name: 'delete_discussion')]
    public function delete(EntityManagerInterface $entityManagerInterface, Discussion $discussion): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté ou que la discussion n'a pas été crée par lui et que ce n'est pas un admin, on l'empeche de supprimer la discussion */
        if(!$user || $user !== $discussion->getUser() && $this->isGranted('ROLE_ADMIN') === false){
            return $this->redirectToRoute('app_home');
        }

        /* On récupère le titre de la discussion qui va être supprimer */
        $talkTitle = $discussion->getTitre();

        /* On supprime la discussion et on sauvegarde ces changements dans la base de données */
        $entityManagerInterface->remove($discussion);
        $entityManagerInterface->flush();

        /* On indique la réussite de la suppression */
        $this->addFlash(
            'success',
            'The talk "' . $talkTitle . '" has been deleted'
        );

        return $this->redirectToRoute('app_discussion');
    }

    #[Route('/discussion/{id}/lock', name: 'lock_discussion')]
    public function lock(EntityManagerInterface $entityManagerInterface, Discussion $discussion): Response{
        /* Si l'utilisateur n'est pas un admin, il n'est pas autorisé à vérrouiller la discussion */
        if($this->isGranted('ROLE_ADMIN') === false){
            return $this->redirectToRoute('app_home');
        }

        /* Si la discussion est déjà vérrouillé */
        if($discussion->isEstVerrouiller()){
            /* On indique qu'il ne peut pas vérrouiller la discussion */
            $this->addFlash(
                'error',
                'This talk is already locked'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
        }

        /* On vérrouille la discussion */
        $discussion->setEstVerrouiller(true);

        /* On sauvegarde ces changements dans la base de données */
        $entityManagerInterface->persist($discussion);
        $entityManagerInterface->flush();

        /* On indique la réussite du vérrouillage */
        $this->addFlash(
            'success',
            'The talk has been locked'
        );

        return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
    }

    #[Route('/discussion/{id}/unlock', name: 'unlock_discussion')]
    public function unlock(EntityManagerInterface $entityManagerInterface, Discussion $discussion): Response{
        /* Si l'utilisateur n'est pas un admin, il n'est pas autorisé à devérrouiller la discussion */
        if($this->isGranted('ROLE_ADMIN') === false){
            return $this->redirectToRoute('app_home');
        }

        /* Si la discussion n'est pas vérrouillé */
        if(!$discussion->isEstVerrouiller()){
            /* On indique qu'il ne peut pas devérrouiller la discussion */
            $this->addFlash(
                'error',
                'This talk is already unlocked'
            );

            return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
        }

        /* On devérrouille la discussion */
        $discussion->setEstVerrouiller(false);

        /* On sauvegarde ces changements dans la base de données */
        $entityManagerInterface->persist($discussion);
        $entityManagerInterface->flush();

        /* On indique la réussite du devérrouillage */
        $this->addFlash(
            'success',
            'The talk has been unlocked'
        );

        return $this->redirectToRoute('show_discussion', ['id' => $discussion->getId()]);
    }    

    #[Route('/discussion/{id}', name: 'show_discussion')]
    public function show(Discussion $discussion){
        return $this->render('discussion/show.html.twig', [
            'talk' => $discussion,
        ]);
    }
}
