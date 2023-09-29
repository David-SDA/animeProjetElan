<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EvenementController extends AbstractController
{
    #[Route('/evenement', name: 'app_evenement')]
    public function index(): Response
    {
        return $this->render('evenement/index.html.twig', [
            'controller_name' => 'EvenementController',
        ]);
    }

    #[Route('/evenement/add', name: 'add_evenement')]
    public function add(EntityManagerInterface $entityManagerInterface, Request $request): Response
    {
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();
        /* Si l'utilisateur n'est pas connecté, il n'a pas accès à son calendrier */
        if(!$currentUser){
            /* On l'indique */
            $this->addFlash(
                'error',
                'Access denied'
            );

            return $this->redirectToRoute('app_home');
        }

        /* On crée un nouvel évènement */
        $evenement = new Evenement();
        
        /* Création du formulaire */
        $form = $this->createForm(EvenementFormType::class, $evenement);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On définie l'utilisateur de l'évènement comme celui actuel */
            $evenement->setUser($currentUser);

            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($evenement);
            $entityManagerInterface->flush();

            /* On indique le succès de la création */
            $this->addFlash(
                'success',
                'Your event has been added to the calendar successfully'
            );

            return $this->redirectToRoute('calendar_user');
        }

        return $this->render('evenement/add.html.twig', [
            'form' => $form,
            'eventExist' => false,
        ]);
    }
}
