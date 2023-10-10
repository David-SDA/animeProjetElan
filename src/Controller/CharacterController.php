<?php

namespace App\Controller;

use App\Repository\PersonnageRepository;
use App\Service\CharacterCallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/character')]
class CharacterController extends AbstractController
{
    #[Route('/{id}', name:'show_character')]
    public function show(int $id, CharacterCallApiService $characterCallApiService, PersonnageRepository $personnageRepository): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

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

        /* On récupère l'animé depuis la base de données si il existe */
        $characterInDatabase = $personnageRepository->findOneBy(['idApi' =>  $id]);

        /* Récupération des données de l'API */
        $characterDetails = $characterCallApiService->getCharacterDetails($id);
        
        /* Regex qui match les liens que l'on veut remplacer */
        /* Par exemple doit des chaîne de caractères de ce genre : [Fern](https://anilist.co/character/183965) et [Fern](https://anilist.co/character/183965/Fern) */
        $regex = '/\[(.*?)\]\(https:\/\/anilist\.co\/character\/(\d+)(?:\/\S+)?\)/';

        /* Modification des lien de la description pour qu'il redirige vers les liens de notre site */
        $characterDetails['data']['Character']['description'] = preg_replace_callback(
            $regex,
            function($matches){
                $characterId = $matches[2];
                $characterName = $matches[1];
                return '<a href="' . $this->generateUrl('show_character', ['id' => $characterId]) . '">' . $characterName . '</a>';
            },
            $characterDetails['data']['Character']['description']
        );
        
        return $this->render('character/show.html.twig', [
            'dataOneCharacter' => $characterDetails,
            'characterIsInFavorites' => $characterIsInFavorites,
            'characterInDatabase' => $characterInDatabase,
        ]);
    }
}
