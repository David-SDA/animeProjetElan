<?php

namespace App\Controller;

use App\Entity\Discussion;
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
    #[Route('/discussion/{id}/edit', name:'edit_discussion')]
    public function add(EntityManagerInterface $entityManagerInterface, Discussion $discussion = null, Request $request): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();
        /* Si l'utilisateur n'est pas connecté, on l'empeche de créer des discussions */
        if(!$user){
            return $this->redirectToRoute('app_home');
        }

        /* Si la discussion n'existe pas, on la crée */
        if(!$discussion){
            $discussion = new Discussion();
        }

        /* Création du formulaire */
        $form = $this->createForm(DiscussionFormType::class);

        return $this->render('discussion/add.html.twig', [
            'form' => $form->createView(),
            'discussion' => $discussion->getId(),
        ]);
    }

    #[Route('/discussion/{id}', name: 'show_discussion')]
    public function show(Discussion $discussion){
        return $this->render('discussion/show.html.twig', [
            'talk' => $discussion,
        ]);
    }
}
