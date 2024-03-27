<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FooterController extends AbstractController
{
    #[Route('/terms-of-use', name: 'app_terms_of_use')]
    public function termsOfUse(): Response
    {
        return $this->render('footer/terms-of-use.html.twig');
    }

    #[Route('/privacy-policy', name: 'app_privacy_policy')]
    public function privacyPolicy(): Response
    {
        return $this->render('footer/privacy-policy.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('footer/contact.html.twig');
    }
}
