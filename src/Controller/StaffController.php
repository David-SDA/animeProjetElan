<?php

namespace App\Controller;

use App\Service\StaffCallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/staff')]
class StaffController extends AbstractController
{
    #[Route('/{id}', name: 'show_staff')]
    public function show(int $id, StaffCallApiService $staffCallApiService): Response{
        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($this->getUser() && $this->getUser()->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        try{
            /* Récupération des données de l'API */
            $staffDetails = $staffCallApiService->getStaffDetails($id);
        }catch(\Exception $e){
            return $this->render('home/errorAPI.html.twig');
        }

        /* Regex qui match les liens que l'on veut remplacer */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : [Fern](https://anilist.co/character/183965*) avec * représentant n'importe quel caractères après l'id de l'API */
        $regexLinks = '/\[([^[\]]+)\]\(https:\/\/anilist\.co\/character\/(\d+)(?:\/[^)]*)?\)/';
        
        /* Modification des liens de la description pour qu'ils redirigent vers les pages de notre site */
        $staffDetails['data']['Staff']['description'] = preg_replace_callback(
            $regexLinks,
            function($matches){
                $characterId = $matches[2];
                $characterName = $matches[1];
                return '<a href="' . $this->generateUrl('show_character', ['id' => $characterId]) . '">' . $characterName . '</a>';
            },
            $staffDetails['data']['Staff']['description']
        );

        /* Regex qui match les liens que l'on veut remplacer */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : [Name](https://anilist.co/staff/1234*) avec * représentant n'importe quel caractères après l'id de l'API */
        $regexLinksStaff = '/\[([^[\]]+)\]\(https:\/\/anilist\.co\/staff\/(\d+)(?:\/[^)]*)?\)/';
        
        /* Modification des liens de la description pour qu'ils redirigent vers les pages de notre site */
        $staffDetails['data']['Staff']['description'] = preg_replace_callback(
            $regexLinksStaff,
            function($matches){
                $staffId = $matches[2];
                $staffName = $matches[1];
                return '<a href="' . $this->generateUrl('show_staff', ['id' => $staffId]) . '">' . $staffName . '</a>';
            },
            $staffDetails['data']['Staff']['description']
        );

        /* Regex qui match les liens que l'on veut remplacer */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : [Name](https://anilist.co/anime/1234*) avec * représentant n'importe quel caractères après l'id de l'API */
        $regexLinksAnime = '/\[([^[\]]+)\]\(https:\/\/anilist\.co\/anime\/(\d+)(?:\/[^)]*)?\)/';
        
        /* Modification des liens de la description pour qu'ils redirigent vers les pages de notre site */
        $staffDetails['data']['Staff']['description'] = preg_replace_callback(
            $regexLinksAnime,
            function($matches){
                $animeId = $matches[2];
                $animeName = $matches[1];
                return '<a href="' . $this->generateUrl('show_anime', ['id' => $animeId]) . '">' . $animeName . '</a>';
            },
            $staffDetails['data']['Staff']['description']
        );

        /* Regex qui match les liens que l'on veut remplacer */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : [Twitter](https://twitter.com/824aoi) */
        $regexExternalLinks = '/\[(.*?)\]\((http.*?)\)/';
        
        /* Modification des liens de la description pour qu'ils redirigent vers les pages de notre site */
        $staffDetails['data']['Staff']['description'] = preg_replace_callback(
            $regexExternalLinks,
            function($matches){
                $link = $matches[2];
                $site = $matches[1];
                return '<a href="' . $link . '">' . $site . '</a>';
            },
            $staffDetails['data']['Staff']['description']
        );

        /* Regex qui match les textes entouré de double underscore */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : __Age__ */
        $regexBoldUnderscore = '/__([^__]+)__/';

        /* Modification des textes entouré de double underscore pour l'entouré de balise b */
        $staffDetails['data']['Staff']['description'] = preg_replace(
            $regexBoldUnderscore,
            '<b>$1</b>',
            $staffDetails['data']['Staff']['description']
        );

        /* Regex qui match les textes entouré de double étoile */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : **Age** */
        $regexBoldStar = '/\*\*([^__]+?)\*\*/';

        /* Modification des textes entouré de double underscore pour l'entouré de balise b */
        $staffDetails['data']['Staff']['description'] = preg_replace(
            $regexBoldStar,
            '<b>$1</b>',
            $staffDetails['data']['Staff']['description']
        );

        /* Regex qui match les textes entouré d'un underscore avec rien avant et après */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : _Age_ */
        $regexItalic = '/(?<!\w)_([^_]+)_(?!\w)/';

        /* Modification des textes entouré d'un underscore pour l'entouré de balise i */
        $staffDetails['data']['Staff']['description'] = preg_replace(
            $regexItalic,
            '<i>$1</i>',
            $staffDetails['data']['Staff']['description']
        );

        /* Regex qui match les textes entouré d'une étoile */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : *Age* */
        $regexStar = '/\*([^_]+?)\*/';

        /* Modification des textes entouré d'une étoile pour l'entouré de balise i */
        $staffDetails['data']['Staff']['description'] = preg_replace(
            $regexStar,
            '<i>$1</i>',
            $staffDetails['data']['Staff']['description']
        );

        /* Regex qui match les textes entouré de ~! et !~ qui correspond à des spoils */
        /* Par exemple, doit matcher des chaîne de caractères de ce genre : ~!Age!~ */
        $regexSpoil = '/~!([\s\S]*?)!~/';

        /* Modification des textes spoil pour le remplacer par un le spoil caché */
        $staffDetails['data']['Staff']['description'] = preg_replace(
            $regexSpoil,
            '<span class="spoilerContent">$1</span>',
            $staffDetails['data']['Staff']['description']
        );

        return $this->render('staff/show.html.twig', [
            'dataOneStaff' => $staffDetails,
        ]);
    }
}
