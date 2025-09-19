<?php

namespace App\Controller\Admin;

use App\Entity\Config;
use App\Entity\ConfigTranslation;
use App\Form\Admin\ConfigType;
use App\Repository\ConfigRepository;
use App\Repository\LanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/config')]
class AdminConfigController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ConfigRepository $configRepository,
        private LanguageRepository $languageRepository
    ) {}

    #[Route('', name: 'admin_config_index')]
    public function index(): Response
    {
        $configs = $this->configRepository->findAllActive();
        
        return $this->render('admin/config/index.html.twig', [
            'configs' => $configs,
        ]);
    }

    #[Route('/new', name: 'admin_config_new')]
    public function new(Request $request): Response
    {
        $config = new Config();
        $form = $this->createForm(ConfigType::class, $config);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($config);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Configuration créée avec succès.');
            
            return $this->redirectToRoute('admin_config_edit', ['id' => $config->getId()]);
        }
        
        return $this->render('admin/config/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_config_edit')]
    public function edit(Request $request, Config $config): Response
    {
        $languages = $this->languageRepository->findAllActive();
        
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // Update config properties
            if (isset($data['config_key'])) {
                $config->setConfigKey($data['config_key']);
            }
            if (isset($data['type'])) {
                $config->setType($data['type']);
            }
            if (isset($data['sort_order'])) {
                $config->setSortOrder((int)$data['sort_order']);
            }
            if (isset($data['is_active'])) {
                $config->setIsActive($data['is_active'] === '1');
            }
            
            // Update translations
            foreach ($languages as $language) {
                $languageId = $language->getId();
                $translation = $config->getTranslationForLanguage($language);
                
                if (!$translation) {
                    $translation = new ConfigTranslation();
                    $translation->setConfig($config);
                    $translation->setLanguage($language);
                    $config->addTranslation($translation);
                }
                
                if (isset($data['label_' . $languageId])) {
                    $translation->setLabel($data['label_' . $languageId]);
                }
                if (isset($data['value_' . $languageId])) {
                    $translation->setValue($data['value_' . $languageId]);
                }
                if (isset($data['description_' . $languageId])) {
                    $translation->setDescription($data['description_' . $languageId]);
                }
                
                $this->entityManager->persist($translation);
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Configuration mise à jour avec succès.');
            
            return $this->redirectToRoute('admin_config_index');
        }
        
        return $this->render('admin/config/edit.html.twig', [
            'config' => $config,
            'languages' => $languages,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_config_delete', methods: ['POST'])]
    public function delete(Config $config): Response
    {
        $this->entityManager->remove($config);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Configuration supprimée avec succès.');
        
        return $this->redirectToRoute('admin_config_index');
    }

    #[Route('/init-default', name: 'admin_config_init_default')]
    public function initDefault(): Response
    {
        $defaultConfigs = [
            ['key' => 'site_name', 'type' => 'text', 'sort' => 1],
            ['key' => 'site_description', 'type' => 'text', 'sort' => 2],
            ['key' => 'contact_email', 'type' => 'text', 'sort' => 3],
            ['key' => 'contact_phone', 'type' => 'text', 'sort' => 4],
            ['key' => 'contact_address', 'type' => 'text', 'sort' => 5],
            ['key' => 'min_loan_amount', 'type' => 'number', 'sort' => 10],
            ['key' => 'max_loan_amount', 'type' => 'number', 'sort' => 11],
            ['key' => 'min_loan_duration', 'type' => 'number', 'sort' => 12],
            ['key' => 'max_loan_duration', 'type' => 'number', 'sort' => 13],
            ['key' => 'default_interest_rate', 'type' => 'number', 'sort' => 14],
            ['key' => 'company_logo', 'type' => 'file', 'sort' => 20],
            ['key' => 'primary_color', 'type' => 'color', 'sort' => 21],
            ['key' => 'secondary_color', 'type' => 'color', 'sort' => 22],
        ];

        foreach ($defaultConfigs as $configData) {
            $existing = $this->configRepository->findByKey($configData['key']);
            if (!$existing) {
                $config = new Config();
                $config->setConfigKey($configData['key']);
                $config->setType($configData['type']);
                $config->setSortOrder($configData['sort']);
                
                $this->entityManager->persist($config);
            }
        }
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Configurations par défaut initialisées avec succès.');
        
        return $this->redirectToRoute('admin_config_index');
    }
}
