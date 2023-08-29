<?php

namespace App\Controller;

use App\Service\StaffCallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StaffController extends AbstractController
{
    #[Route('/staff', name: 'app_staff')]
    public function index(): Response
    {
        return $this->render('staff/index.html.twig', [
            'controller_name' => 'StaffController',
        ]);
    }

    #[Route('/staff/{id}', name: 'show_staff')]
    public function show(int $id, StaffCallApiService $staffCallApiService): Response{
        return $this->render('staff/show.html.twig', [
            'dataOneStaff' => $staffCallApiService->getStaffDetails($id),
        ]);
    }
}
