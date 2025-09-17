<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ServiceRepository;
use App\Repository\LanguageRepository;
use App\Repository\ServiceTranslationRepository;

#[Route('', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function dashboard(
        ServiceRepository $serviceRepository,
        LanguageRepository $languageRepository,
        ServiceTranslationRepository $translationRepository
    ): Response {
        $services = $serviceRepository->findForAdministration();
        $languages = $languageRepository->getAllOrderedBySortOrder();
        $stats = $translationRepository->getTranslationStats();

        return $this->render('admin/dashboard.html.twig', [
            'services' => $services,
            'languages' => $languages,
            'stats' => $stats,
        ]);
    }
}
