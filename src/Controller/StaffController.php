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

        return $this->render('staff/show.html.twig', [
            'dataOneStaff' => $staffCallApiService->getStaffDetails($id),
        ]);
    }
}
