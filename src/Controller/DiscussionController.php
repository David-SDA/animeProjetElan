<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Repository\DiscussionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

    #[Route('/discussion/{id}', name: 'show_discussion')]
    public function show(Discussion $discussion){
        return $this->render('discussion/show.html.twig', [
            'talk' => $discussion,
        ]);
    }
}
