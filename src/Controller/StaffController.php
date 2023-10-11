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

        return $this->render('staff/show.html.twig', [
            'dataOneStaff' => $staffDetails,
        ]);
    }
}
