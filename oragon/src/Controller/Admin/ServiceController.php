<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use App\Entity\ServiceTranslation;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use App\Repository\LanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/services', name: 'admin_service_')]
class ServiceController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ServiceRepository $serviceRepository, LanguageRepository $languageRepository): Response
    {
        $services = $serviceRepository->findAllWithTranslations();
        $languages = $languageRepository->findActiveLanguages();

        return $this->render('admin/service/index.html.twig', [
            'services' => $services,
            'languages' => $languages,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        LanguageRepository $languageRepository,
        SluggerInterface $slugger
    ): Response {
        $service = new Service();
        $languages = $languageRepository->findActiveLanguages();
        
        // Initialize translations for all active languages
        foreach ($languages as $language) {
            $translation = new ServiceTranslation();
            $translation->setLanguage($language);
            $service->addTranslation($translation);
        }

        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate slug from first non-empty title
            if (empty($service->getSlug())) {
                $title = '';
                foreach ($service->getTranslations() as $translation) {
                    if (!empty($translation->getTitle())) {
                        $title = $translation->getTitle();
                        break;
                    }
                }
                $service->setSlug($slugger->slug($title ?: 'service')->lower());
            }

            $entityManager->persist($service);
            $entityManager->flush();

            $this->addFlash('success', 'Service créé avec succès.');

            return $this->redirectToRoute('admin_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/service/new.html.twig', [
            'service' => $service,
            'form' => $form,
            'languages' => $languages,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Service $service, LanguageRepository $languageRepository): Response
    {
        $languages = $languageRepository->findActiveLanguages();

        return $this->render('admin/service/show.html.twig', [
            'service' => $service,
            'languages' => $languages,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Service $service, 
        EntityManagerInterface $entityManager,
        LanguageRepository $languageRepository
    ): Response {
        $languages = $languageRepository->findActiveLanguages();
        
        // Ensure we have translations for all active languages
        foreach ($languages as $language) {
            $existingTranslation = $service->getTranslationForLanguage($language);
            if (!$existingTranslation) {
                $translation = new ServiceTranslation();
                $translation->setLanguage($language);
                $service->addTranslation($translation);
            }
        }

        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Service modifié avec succès.');

            return $this->redirectToRoute('admin_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/service/edit.html.twig', [
            'service' => $service,
            'form' => $form,
            'languages' => $languages,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($service);
            $entityManager->flush();
            $this->addFlash('success', 'Service supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_service_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'])]
    public function toggleStatus(Service $service, EntityManagerInterface $entityManager): Response
    {
        $service->setIsActive(!$service->isActive());
        $entityManager->flush();
        
        $status = $service->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Service {$status} avec succès.");

        return $this->redirectToRoute('admin_service_index');
    }

    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'])]
    public function duplicate(Service $service, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $newService = new Service();
        $newService->setSlug($slugger->slug($service->getSlug() . '-copy')->lower());
        $newService->setIsActive(false); // Start as inactive
        $newService->setSortOrder($service->getSortOrder());

        // Copy all translations
        foreach ($service->getTranslations() as $originalTranslation) {
            $newTranslation = new ServiceTranslation();
            $newTranslation->setLanguage($originalTranslation->getLanguage());
            $newTranslation->setTitle($originalTranslation->getTitle() ? $originalTranslation->getTitle() . ' (Copie)' : null);
            $newTranslation->setDescription($originalTranslation->getDescription());
            $newTranslation->setDetail($originalTranslation->getDetail());
            $newService->addTranslation($newTranslation);
        }

        $entityManager->persist($newService);
        $entityManager->flush();

        $this->addFlash('success', 'Service dupliqué avec succès.');

        return $this->redirectToRoute('admin_service_edit', ['id' => $newService->getId()]);
    }

    #[Route('/{id}/preview/{languageCode}', name: 'preview', methods: ['GET'])]
    public function preview(Service $service, string $languageCode, LanguageRepository $languageRepository): JsonResponse
    {
        $language = $languageRepository->findByCode($languageCode);
        if (!$language) {
            return new JsonResponse(['error' => 'Langue non trouvée'], 404);
        }

        $translation = $service->getTranslationForLanguage($language);
        
        return new JsonResponse([
            'title' => $translation?->getTitle() ?? '',
            'description' => $translation?->getDescription() ?? '',
            'detail' => $translation?->getDetail() ?? '',
        ]);
    }
}
