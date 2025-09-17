<?php

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use App\Repository\LanguageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ServiceController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(
        ServiceRepository $serviceRepository,
        LanguageRepository $languageRepository,
        Request $request
    ): Response {
        $currentLanguage = $this->getCurrentLanguage($request, $languageRepository);
        $services = $serviceRepository->findActiveServices();
        $languages = $languageRepository->findActiveLanguages();

        return $this->render('service/index.html.twig', [
            'services' => $services,
            'languages' => $languages,
            'currentLanguage' => $currentLanguage,
        ]);
    }

    #[Route('/service/{slug}', name: 'service_show')]
    public function show(
        Service $service,
        Request $request,
        LanguageRepository $languageRepository
    ): Response {
        if (!$service->isActive()) {
            throw $this->createNotFoundException('Service non trouvé');
        }

        $currentLanguage = $this->getCurrentLanguage($request, $languageRepository);
        $languages = $languageRepository->findActiveLanguages();

        return $this->render('service/show.html.twig', [
            'service' => $service,
            'languages' => $languages,
            'currentLanguage' => $currentLanguage,
        ]);
    }

    #[Route('/api/switch-language/{languageCode}', name: 'api_switch_language', methods: ['POST'])]
    public function switchLanguage(
        string $languageCode,
        LanguageRepository $languageRepository,
        Request $request
    ): JsonResponse {
        $language = $languageRepository->findActiveByCode($languageCode);
        
        if (!$language) {
            return new JsonResponse(['error' => 'Langue non trouvée'], 404);
        }

        // Store language preference in session
        $request->getSession()->set('_locale', $languageCode);

        return new JsonResponse([
            'success' => true,
            'language' => [
                'code' => $language->getCode(),
                'name' => $language->getName(),
                'nativeName' => $language->getNativeName(),
            ]
        ]);
    }

    #[Route('/api/services', name: 'api_services', methods: ['GET'])]
    public function apiServices(
        ServiceRepository $serviceRepository,
        LanguageRepository $languageRepository,
        Request $request
    ): JsonResponse {
        $currentLanguage = $this->getCurrentLanguage($request, $languageRepository);
        $services = $serviceRepository->findActiveServices();
        $defaultLanguage = $languageRepository->findDefaultLanguage();

        $servicesData = [];
        foreach ($services as $service) {
            $translation = $service->getTranslationForLanguage($currentLanguage);
            if (!$translation || !$translation->getTitle()) {
                // Fallback to default language
                $translation = $service->getTranslationForLanguage($defaultLanguage);
            }

            $servicesData[] = [
                'id' => $service->getId(),
                'slug' => $service->getSlug(),
                'title' => $translation?->getTitle() ?? $service->getSlug(),
                'description' => $translation?->getDescription(),
                'detail' => $translation?->getDetail(),
                'isActive' => $service->isActive(),
            ];
        }

        return new JsonResponse([
            'services' => $servicesData,
            'currentLanguage' => [
                'code' => $currentLanguage->getCode(),
                'name' => $currentLanguage->getName(),
            ]
        ]);
    }

    #[Route('/search', name: 'service_search')]
    public function search(
        Request $request,
        ServiceRepository $serviceRepository,
        LanguageRepository $languageRepository
    ): Response {
        $query = $request->query->get('q', '');
        $currentLanguage = $this->getCurrentLanguage($request, $languageRepository);
        $languages = $languageRepository->findActiveLanguages();

        $services = [];
        if (!empty($query)) {
            $services = $serviceRepository->search($query, $currentLanguage->getCode());
        }

        return $this->render('service/search.html.twig', [
            'services' => $services,
            'languages' => $languages,
            'currentLanguage' => $currentLanguage,
            'query' => $query,
        ]);
    }

    private function getCurrentLanguage(Request $request, LanguageRepository $languageRepository)
    {
        // Try to get language from session
        $languageCode = $request->getSession()->get('_locale');
        
        if ($languageCode) {
            $language = $languageRepository->findActiveByCode($languageCode);
            if ($language) {
                return $language;
            }
        }

        // Try to get language from request locale
        $language = $languageRepository->findActiveByCode($request->getLocale());
        if ($language) {
            return $language;
        }

        // Fallback to default language
        return $languageRepository->findDefaultLanguage() ?? $languageRepository->findActiveLanguages()[0] ?? null;
    }
}
