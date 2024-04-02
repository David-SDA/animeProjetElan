<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
    public function contact(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            // à faire : envoi de mail

            $this->addFlash(
                'success',
                'Your message has been send successfully'
            );
            return $this->redirectToRoute('app_contact');
        }
        return $this->render('footer/contact.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
