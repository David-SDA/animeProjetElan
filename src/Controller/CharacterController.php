<?php

namespace App\Controller;

use App\Service\CharacterCallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CharacterController extends AbstractController
{
    #[Route('/character', name: 'app_character')]
    public function index(): Response
    {
        return $this->render('character/index.html.twig', [
            'controller_name' => 'CharacterController',
        ]);
    }

    #[Route('/character/{id}', name:'show_character')]
    public function show(int $id, CharacterCallApiService $characterCallApiService): Response{
        return $this->render('character/show.html.twig', [
            'dataOneCharacter' => $characterCallApiService->getCharacterDetails($id),
        ]);
    }
}
