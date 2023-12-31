<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('evenement')]
class EvenementController extends AbstractController
{
    #[Route('/add', name: 'add_evenement')]
    public function add(EntityManagerInterface $entityManagerInterface, Request $request): Response{
        /* On recupère l'utilisateur actuel */
        $currentUser = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($currentUser && $currentUser->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

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

    #[Route('/{id}/edit', name: 'edit_evenement')]
    public function edit(EntityManagerInterface $entityManagerInterface, Evenement $evenement, Request $request): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté ou si ce n'est pas son évènement, on l'empeche de modifier celui-ci */
        if(!$user || $user !== $evenement->getUser()){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are not allowed to edit this event'
            );

            return $this->redirectToRoute('app_home');
        }
        
        /* Création du formulaire */
        $form = $this->createForm(EvenementFormType::class, $evenement);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On sauvegarde ces changements dans la base de données */
            $entityManagerInterface->persist($evenement);
            $entityManagerInterface->flush();

            /* On indique le succès de la modification */
            $this->addFlash(
                'success',
                'Your event has been edited successfully'
            );

            return $this->redirectToRoute('calendar_user');
        }

        return $this->render('evenement/add.html.twig', [
            'form' => $form,
            'eventExist' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete_evenement')]
    public function delete(EntityManagerInterface $entityManagerInterface, Evenement $evenement, Request $request): Response{
        /* On récupère l'utilisateur actuel */
        $user = $this->getUser();

        /* Si l'utilisateur est banni, on le redirige vers la page d'un banni */
        if($user && $user->isBanned()){
            return $this->redirectToRoute('app_banned');
        }

        /* Si l'utilisateur n'est pas connecté ou si l'évènement ne lui appartient pas, on l'empeche de supprimer l'évènement */
        if(!$user || $user !== $evenement->getUser()){
            /* On indique l'interdiction */
            $this->addFlash(
                'error',
                'You are not allowed to delete this event'
            );

            return $this->redirectToRoute('app_home');
        }

        /* On récupère le nom de l'évènement */
        $eventTitle = $evenement->getTitle();

        /* On supprime l'évènement et on sauvegarde ces changements dans la base de données */
        $entityManagerInterface->remove($evenement);
        $entityManagerInterface->flush();

        /* On indique la réussite de la suppression */
        $this->addFlash(
            'success',
            'The event "' . $eventTitle . '" has been deleted successfully'
        );
        
        return $this->redirectToRoute('calendar_user');
    }
}
