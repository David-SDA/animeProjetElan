<?php

namespace App\Controller;

use App\Repository\PersonnageRepository;
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
    public function show(int $id, CharacterCallApiService $characterCallApiService, PersonnageRepository $personnageRepository): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* On crée une variable pour déterminer si le personnage est dans les personnages favoris de l'utilisateur, on définit que de base, il n'y est pas */
        $characterIsInFavorites = false;

        /* Si l'utiisateur est connecté */
        if($currentUser){
            /* Pour chaque personnage dans la collection de personnages de l'utilisateur (qui représente les personnages favoris de l'utilisateur) */
            foreach($currentUser->getPersonnages() as $character){
                /* On vérifie si l'id de l'API correspond à l'id du personnage de la page actuelle */
                if($character->getIdApi() === $id){
                    /* Dès que l'on trouve une correspondance, cela veut dire que le personnage est bien dans les personnages favoris de l'utilisateur et on stop la boucle dès que cela arrive */
                    $characterIsInFavorites = true;
                    break;
                }
            }
        }

        $characterInDatabase = $personnageRepository->findOneBy(['idApi' =>  $id]);
        
        return $this->render('character/show.html.twig', [
            'dataOneCharacter' => $characterCallApiService->getCharacterDetails($id),
            'characterIsInFavorites' => $characterIsInFavorites,
            'characterInDatabase' => $characterInDatabase,
        ]);
    }
}
