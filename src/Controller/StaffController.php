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
        if($this->getUser() && $this->getUser()->isEstBanni()){
            return $this->redirectToRoute('app_banned');
        }

        /* Récupération des données de l'API */
        $staffDetails = $staffCallApiService->getStaffDetails($id);

        /* Regex qui match les liens que l'on veut remplacer */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : [Fern](https://anilist.co/character/183965*) avec * représentant n'importe quel caractères après l'id de l'API */
        $regexLinks = '/\[(.*?)\]\(https:\/\/anilist\.co\/character\/(\d+)\/*(?:(.*))?\)/';
        
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

        /* Regex qui match les textes entouré de double underscore */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : __Age__ */
        $regexBold = '/__([^__]+)__/';

        /* Modification des textes entouré de double underscore pour l'entouré de balise b */
        $staffDetails['data']['Staff']['description'] = preg_replace(
            $regexBold,
            '<b>$1</b>',
            $staffDetails['data']['Staff']['description']
        );

        /* Regex qui match les textes entouré d'un underscore */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : _Age_ */
        $regexItalic = '/_([^_]+)_/';

        /* Modification des textes entouré d'un underscore pour l'entouré de balise i */
        $staffDetails['data']['Staff']['description'] = preg_replace(
            $regexItalic,
            '<i>$1</i>',
            $staffDetails['data']['Staff']['description']
        );

        /* Regex qui match les textes entouré d'une étoile */
        /* Par exemple, doit matcher des chaînes de caractères de ce genre : *Age* */
        $regexStar = '/\*([^_]+)\*/';

        /* Modification des textes entouré d'une étoile pour l'entouré de balise i */
        $staffDetails['data']['Staff']['description'] = preg_replace(
            $regexStar,
            '<i>$1</i>',
            $staffDetails['data']['Staff']['description']
        );

        return $this->render('staff/show.html.twig', [
            'dataOneStaff' => $staffDetails,
        ]);
    }
}
