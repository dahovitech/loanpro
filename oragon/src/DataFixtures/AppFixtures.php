<?php

namespace App\DataFixtures;

use App\Entity\Language;
use App\Entity\Service;
use App\Entity\ServiceTranslation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create languages
        $french = new Language();
        $french->setCode('fr')
            ->setName('Français')
            ->setNativeName('Français')
            ->setIsActive(true)
            ->setIsDefault(true)
            ->setSortOrder(1);
        $manager->persist($french);

        $english = new Language();
        $english->setCode('en')
            ->setName('Anglais')
            ->setNativeName('English')
            ->setIsActive(true)
            ->setIsDefault(false)
            ->setSortOrder(2);
        $manager->persist($english);

        $spanish = new Language();
        $spanish->setCode('es')
            ->setName('Espagnol')
            ->setNativeName('Español')
            ->setIsActive(true)
            ->setIsDefault(false)
            ->setSortOrder(3);
        $manager->persist($spanish);

        $german = new Language();
        $german->setCode('de')
            ->setName('Allemand')
            ->setNativeName('Deutsch')
            ->setIsActive(true)
            ->setIsDefault(false)
            ->setSortOrder(4);
        $manager->persist($german);

        // Flush languages first
        $manager->flush();

        // Create services
        $services = [
            [
                'slug' => 'consultation-web',
                'translations' => [
                    'fr' => [
                        'title' => 'Consultation Web',
                        'description' => 'Services de consultation pour le développement web moderne.',
                        'detail' => 'Nous proposons des services de consultation pour vous aider à développer des applications web modernes. Notre équipe d\'experts vous accompagne dans la conception, le développement et la mise en production de vos projets web.'
                    ],
                    'en' => [
                        'title' => 'Web Consulting',
                        'description' => 'Consulting services for modern web development.',
                        'detail' => 'We offer consulting services to help you develop modern web applications. Our team of experts supports you in the design, development and deployment of your web projects.'
                    ],
                    'es' => [
                        'title' => 'Consultoría Web',
                        'description' => 'Servicios de consultoría para el desarrollo web moderno.',
                        'detail' => 'Ofrecemos servicios de consultoría para ayudarte a desarrollar aplicaciones web modernas. Nuestro equipo de expertos te acompaña en el diseño, desarrollo y despliegue de tus proyectos web.'
                    ]
                ]
            ],
            [
                'slug' => 'formation-symfony',
                'translations' => [
                    'fr' => [
                        'title' => 'Formation Symfony',
                        'description' => 'Formation complète au framework Symfony pour développeurs.',
                        'detail' => 'Apprenez Symfony avec nos formations personnalisées. Du niveau débutant au niveau avancé, nous couvrons tous les aspects du framework : composants, architecture, bonnes pratiques, tests, déploiement.'
                    ],
                    'en' => [
                        'title' => 'Symfony Training',
                        'description' => 'Complete Symfony framework training for developers.',
                        'detail' => 'Learn Symfony with our personalized training courses. From beginner to advanced level, we cover all aspects of the framework: components, architecture, best practices, testing, deployment.'
                    ],
                    'de' => [
                        'title' => 'Symfony Schulung',
                        'description' => 'Vollständige Symfony Framework Schulung für Entwickler.',
                        'detail' => 'Lernen Sie Symfony mit unseren personalisierten Schulungen. Vom Anfänger bis zum fortgeschrittenen Niveau decken wir alle Aspekte des Frameworks ab: Komponenten, Architektur, Best Practices, Tests, Bereitstellung.'
                    ]
                ]
            ],
            [
                'slug' => 'support-technique',
                'translations' => [
                    'fr' => [
                        'title' => 'Support Technique',
                        'description' => 'Support et maintenance pour vos applications web.',
                        'detail' => 'Notre équipe technique assure la maintenance et le support de vos applications. Surveillance, corrections de bugs, mises à jour de sécurité, optimisation des performances.'
                    ],
                    'en' => [
                        'title' => 'Technical Support',
                        'description' => 'Support and maintenance for your web applications.',
                        'detail' => 'Our technical team ensures maintenance and support for your applications. Monitoring, bug fixes, security updates, performance optimization.'
                    ],
                    'es' => [
                        'title' => 'Soporte Técnico',
                        'description' => 'Soporte y mantenimiento para tus aplicaciones web.',
                        'detail' => 'Nuestro equipo técnico asegura el mantenimiento y soporte de tus aplicaciones. Monitoreo, corrección de errores, actualizaciones de seguridad, optimización del rendimiento.'
                    ],
                    'de' => [
                        'title' => 'Technischer Support',
                        'description' => 'Support und Wartung für Ihre Webanwendungen.',
                        'detail' => 'Unser technisches Team gewährleistet Wartung und Support für Ihre Anwendungen. Überwachung, Fehlerbehebung, Sicherheitsupdates, Leistungsoptimierung.'
                    ]
                ]
            ],
            [
                'slug' => 'integration-api',
                'translations' => [
                    'fr' => [
                        'title' => 'Intégration API',
                        'description' => 'Services d\'intégration d\'APIs tierces et développement d\'APIs REST.',
                        'detail' => 'Nous intégrons vos systèmes avec des APIs tierces et développons des APIs REST robustes et sécurisées. Documentation complète, versioning, authentification, tests automatisés.'
                    ],
                    'en' => [
                        'title' => 'API Integration',
                        'description' => 'Third-party API integration services and REST API development.',
                        'detail' => 'We integrate your systems with third-party APIs and develop robust and secure REST APIs. Complete documentation, versioning, authentication, automated testing.'
                    ]
                ]
            ],
            [
                'slug' => 'audit-securite',
                'translations' => [
                    'fr' => [
                        'title' => 'Audit de Sécurité',
                        'description' => 'Audit complet de la sécurité de vos applications web.',
                        'detail' => 'Nous effectuons des audits de sécurité approfondis pour identifier les vulnérabilités de vos applications. Tests d\'intrusion, analyse de code, recommandations de sécurité.'
                    ],
                    'en' => [
                        'title' => 'Security Audit',
                        'description' => 'Complete security audit of your web applications.',
                        'detail' => 'We perform in-depth security audits to identify vulnerabilities in your applications. Penetration testing, code analysis, security recommendations.'
                    ],
                    'es' => [
                        'title' => 'Auditoría de Seguridad',
                        'description' => 'Auditoría completa de la seguridad de tus aplicaciones web.',
                        'detail' => 'Realizamos auditorías de seguridad en profundidad para identificar vulnerabilidades en tus aplicaciones. Pruebas de penetración, análisis de código, recomendaciones de seguridad.'
                    ]
                ]
            ]
        ];

        foreach ($services as $i => $serviceData) {
            $service = new Service();
            $service->setSlug($serviceData['slug'])
                ->setIsActive(true)
                ->setSortOrder($i + 1);
            
            foreach ($serviceData['translations'] as $langCode => $translationData) {
                $language = null;
                switch ($langCode) {
                    case 'fr':
                        $language = $french;
                        break;
                    case 'en':
                        $language = $english;
                        break;
                    case 'es':
                        $language = $spanish;
                        break;
                    case 'de':
                        $language = $german;
                        break;
                }
                
                if ($language) {
                    $translation = new ServiceTranslation();
                    $translation->setLanguage($language)
                        ->setTitle($translationData['title'])
                        ->setDescription($translationData['description'])
                        ->setDetail($translationData['detail']);
                    
                    $service->addTranslation($translation);
                }
            }
            
            $manager->persist($service);
        }

        $manager->flush();
    }
}
