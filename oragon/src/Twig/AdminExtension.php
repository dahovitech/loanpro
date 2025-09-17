<?php

namespace App\Twig;

use App\Repository\LanguageRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AdminExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private LanguageRepository $languageRepository
    ) {}

    public function getGlobals(): array
    {
        return [
            'admin_languages' => $this->languageRepository->findActiveLanguages()
        ];
    }
}
