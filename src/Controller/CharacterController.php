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
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : [Fern](https://anilist.co/character/183965*) avec * représentant n'importe quel caractères après l'id de l'API */
        $regexLinks = '/\[(.*?)\]\(https:\/\/anilist\.co\/character\/(\d+)\/*(?:(.*))?\)/';

        /* Modification des liens de la description pour qu'ils redirigent vers les pages de notre site */
        $characterDetails['data']['Character']['description'] = preg_replace_callback(
            $regexLinks,
            function($matches){
                $characterId = $matches[2];
                $characterName = $matches[1];
                return '<a href="' . $this->generateUrl('show_character', ['id' => $characterId]) . '">' . $characterName . '</a>';
            },
            $characterDetails['data']['Character']['description']
        );

        /* Regex qui match les textes entouré de double underscore */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : __Age__ */
        $regexBold = '/__([^__]+)__/';

        /* Modification des textes entouré de double underscore pour l'entouré de balise b */
        $characterDetails['data']['Character']['description'] = preg_replace(
            $regexBold,
            '<b>$1</b>',
            $characterDetails['data']['Character']['description']
        );

        /* Regex qui match les textes entouré d'un underscore avec rien avant et après */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : _Age_ */
        $regexItalic = '/(?<!\w)_([^_]+)_(?!\w)/';

        /* Modification des textes entouré d'un underscore pour l'entouré de balise i */
        $characterDetails['data']['Character']['description'] = preg_replace(
            $regexItalic,
            '<i>$1</i>',
            $characterDetails['data']['Character']['description']
        );

        /* Regex qui match les textes entouré d'une étoile */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : *Age* */
        $regexStar = '/\*([^_]+)\*/';

        /* Modification des textes entouré d'une étoile pour l'entouré de balise i */
        $characterDetails['data']['Character']['description'] = preg_replace(
            $regexStar,
            '<i>$1</i>',
            $characterDetails['data']['Character']['description']
        );

        /* Regex qui match les textes entouré de ~! et !~ qui correspond à des spoils */
        /* Par exemple, doit matcher des chaîne de caractères de ce genre : ~!Age!~ */
        $regexSpoil = '/~!([\s\S]*?)!~/';

        /* Modification des textes spoil pour le remplacer par un le spoil caché */
        $characterDetails['data']['Character']['description'] = preg_replace(
            $regexSpoil,
            '<span class="spoilerContent">$1</span>',
            $characterDetails['data']['Character']['description']
        );

        return $this->render('character/show.html.twig', [
            'dataOneCharacter' => $characterDetails,
            'characterIsInFavorites' => $characterIsInFavorites,
            'characterInDatabase' => $characterInDatabase,
        ]);
    }
}
