<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BannedController extends AbstractController
{
    #[Route('/banned', name: 'app_banned')]
    public function index(): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur n'est pas connecté ou si il n'est pas banni */
        if(!$user || !$user->isEstBanni()){
            return $this->redirectToRoute('app_home');
        }

        return $this->render('banned/index.html.twig');
    }
}
