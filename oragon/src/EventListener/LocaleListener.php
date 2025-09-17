<?php

namespace App\EventListener;

use App\Repository\LanguageRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
class LocaleListener
{
    public function __construct(
        private LanguageRepository $languageRepository
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Skip if not main request
        if (!$event->isMainRequest()) {
            return;
        }

        // Skip for non-admin routes 
        $route = $request->attributes->get('_route');
        if (!$route || !str_starts_with($route, 'admin_')) {
            return;
        }

        $session = $request->getSession();
        
        // Get locale from session first, then from request, then default
        $locale = $session->get('_locale');
        
        if (!$locale) {
            $locale = $request->getLocale();
        }
        
        // If still no locale, get the default language from database
        if (!$locale || $locale === 'en') {
            $defaultLanguage = $this->languageRepository->findDefaultLanguage();
            
            if ($defaultLanguage) {
                $locale = $defaultLanguage->getCode();
            } else {
                // Fallback to French if no default language is found
                $locale = 'fr';
            }
        }

        // Validate that the current locale exists in our languages
        $activeLanguage = $this->languageRepository->findActiveByCode($locale);
        if (!$activeLanguage) {
            // If current locale is not active, fall back to default
            $defaultLanguage = $this->languageRepository->findDefaultLanguage();
            if ($defaultLanguage) {
                $locale = $defaultLanguage->getCode();
            }
        }
        
        // Set the locale for the request and store in session
        $request->setLocale($locale);
        $session->set('_locale', $locale);
    }
}
