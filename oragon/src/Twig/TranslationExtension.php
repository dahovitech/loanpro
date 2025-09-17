<?php

namespace App\Twig;

use App\Repository\LanguageRepository;
use App\Service\TranslationManagerService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TranslationExtension extends AbstractExtension
{
    public function __construct(
        private LanguageRepository $languageRepository,
        private TranslationManagerService $translationManager
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_admin_languages', [$this, 'getAdminLanguages']),
            new TwigFunction('get_current_admin_language', [$this, 'getCurrentAdminLanguage']),
            new TwigFunction('translation_progress', [$this, 'getTranslationProgress']),
            new TwigFunction('is_translation_complete', [$this, 'isTranslationComplete']),
        ];
    }

    /**
     * Get all active languages for admin interface
     */
    public function getAdminLanguages(): array
    {
        return $this->languageRepository->findActiveLanguages();
    }

    /**
     * Get current admin language based on locale
     */
    public function getCurrentAdminLanguage(string $locale): ?\App\Entity\Language
    {
        return $this->languageRepository->findActiveByCode($locale);
    }

    /**
     * Get translation progress for a domain and locale
     */
    public function getTranslationProgress(string $domain, string $locale): array
    {
        $stats = $this->translationManager->getTranslationStats($domain);
        return $stats[$locale] ?? ['total' => 0, 'completed' => 0, 'missing' => 0, 'percentage' => 0];
    }

    /**
     * Check if translation is complete for a domain and locale
     */
    public function isTranslationComplete(string $domain, string $locale, int $threshold = 100): bool
    {
        $progress = $this->getTranslationProgress($domain, $locale);
        return $progress['percentage'] >= $threshold;
    }
}
