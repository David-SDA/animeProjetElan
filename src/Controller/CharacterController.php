<?php

namespace App\Controller;

use App\Repository\PersonnageRepository;
use App\Service\CharacterCallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/character')]
class CharacterController extends AbstractController
{
    #[Route('/{id}', name:'show_character')]
    public function show(int $id, CharacterCallApiService $characterCallApiService, PersonnageRepository $personnageRepository, CacheInterface $cache): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isBanned()){
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

        try{
            /* Récupération des données de l'API avec une mise en cache */
            $characterDetails = $cache->get('character_' . $id . '_details', function(ItemInterface $item) use($characterCallApiService, $id){
                $item->expiresAt(new \DateTime('tomorrow'));
                return $characterCallApiService->getCharacterDetails($id);
            });
        }catch(\Exception $e){
            return $this->render('home/errorAPI.html.twig');
        }
        
        /* Regex qui match les liens que l'on veut remplacer */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : [Fern](https://anilist.co/character/183965*) avec * représentant n'importe quel caractères après l'id de l'API */
        $regexLinks = '/\[([^[\]]+)\]\(https:\/\/anilist\.co\/character\/(\d+)(?:\/[^)]*)?\)/';
        
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

        /* Regex qui match les liens que l'on veut remplacer */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : [Name](https://anilist.co/staff/1234*) avec * représentant n'importe quel caractères après l'id de l'API */
        $regexLinksStaff = '/\[([^[\]]+)\]\(https:\/\/anilist\.co\/staff\/(\d+)(?:\/[^)]*)?\)/';
        
        /* Modification des liens de la description pour qu'ils redirigent vers les pages de notre site */
        $characterDetails['data']['Character']['description'] = preg_replace_callback(
            $regexLinksStaff,
            function($matches){
                $staffId = $matches[2];
                $staffName = $matches[1];
                return '<a href="' . $this->generateUrl('show_staff', ['id' => $staffId]) . '">' . $staffName . '</a>';
            },
            $characterDetails['data']['Character']['description']
        );

        /* Regex qui match les liens que l'on veut remplacer */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : [Name](https://anilist.co/anime/1234*) avec * représentant n'importe quel caractères après l'id de l'API */
        $regexLinksAnime = '/\[([^[\]]+)\]\(https:\/\/anilist\.co\/anime\/(\d+)(?:\/[^)]*)?\)/';
        
        /* Modification des liens de la description pour qu'ils redirigent vers les pages de notre site */
        $characterDetails['data']['Character']['description'] = preg_replace_callback(
            $regexLinksAnime,
            function($matches){
                $animeId = $matches[2];
                $animeName = $matches[1];
                return '<a href="' . $this->generateUrl('show_anime', ['id' => $animeId]) . '">' . $animeName . '</a>';
            },
            $characterDetails['data']['Character']['description']
        );

        /* Regex qui match les liens que l'on veut remplacer */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : [Twitter](https://twitter.com/824aoi) */
        $regexExternalLinks = '/\[(.*?)\]\((http.*?)\)/';
        
        /* Modification des liens de la description pour qu'ils redirigent vers les pages de notre site */
        $characterDetails['data']['Character']['description'] = preg_replace_callback(
            $regexExternalLinks,
            function($matches){
                $link = $matches[2];
                $site = $matches[1];
                return '<a href="' . $link . '">' . $site . '</a>';
            },
            $characterDetails['data']['Character']['description']
        );

        /* Regex qui match les textes entouré de double underscore */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : __Age__ */
        $regexBoldUnderscore = '/__([^__]+)__/';

        /* Modification des textes entouré de double underscore pour l'entouré de balise b */
        $characterDetails['data']['Character']['description'] = preg_replace(
            $regexBoldUnderscore,
            '<b>$1</b>',
            $characterDetails['data']['Character']['description']
        );

        /* Regex qui match les textes entouré de double étoile */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : **Age** */
        $regexBoldStar = '/\*\*([^__]+?)\*\*/';

        /* Modification des textes entouré de double underscore pour l'entouré de balise b */
        $characterDetails['data']['Character']['description'] = preg_replace(
            $regexBoldStar,
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
        $regexStar = '/\*([^_]+?)\*/';

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
