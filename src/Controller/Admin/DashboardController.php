<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Loan;
use App\Entity\Media;
use App\Entity\Notification;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Voter\AdminVoter;
use App\Entity\AuditLog;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    #[IsGranted(AdminVoter::ADMIN_ACCESS)]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        
        return $this->redirect($adminUrlGenerator->setController(LoanCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('LoanPro - Administration')
            ->setFaviconPath('favicon.ico')
            ->setLocales(['fr' => '🇫🇷 Français'])
            ->setDefaultLocale('fr')
            ->renderContentMaximized()
            ->disableUrlSignatures();
    }

    public function configureMenuItems(): iterable
    {
        // Dashboard principal
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        
        // Section Prêts - accessible aux admins et super admins
        if ($this->isGranted(AdminVoter::ADMIN_LOANS)) {
            yield MenuItem::section('Gestion des prêts');
            yield MenuItem::linkToCrud('Demandes de prêt', 'fa fa-money-bill', Loan::class);
        }
        
        // Section Documents - accessible aux admins et super admins
        if ($this->isGranted(AdminVoter::ADMIN_MEDIA)) {
            yield MenuItem::linkToCrud('Documents', 'fa fa-file-upload', Media::class);
        }
        
        // Section Utilisateurs - réservé aux super admins
        if ($this->isGranted(AdminVoter::ADMIN_USERS)) {
            yield MenuItem::section('Gestion des utilisateurs');
            yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        }
        
        // Section Communication - accessible aux admins et super admins
        if ($this->isGranted(AdminVoter::ADMIN_NOTIFICATIONS)) {
            yield MenuItem::section('Communication');
            yield MenuItem::linkToCrud('Notifications', 'fa fa-bell', Notification::class);
        }
        
        // Section Statistiques et rapports - accessible aux admins et super admins
        if ($this->isGranted(AdminVoter::ADMIN_REPORTS)) {
            yield MenuItem::section('Statistiques');
            yield MenuItem::linkToRoute('Rapports', 'fa fa-chart-line', 'admin_reports');
            yield MenuItem::linkToRoute('Métriques', 'fa fa-tachometer-alt', 'admin_metrics');
            yield MenuItem::linkToCrud('Logs d\'audit', 'fa fa-history', AuditLog::class);
        }
        
        // Section Système - réservé aux super admins
        if ($this->isGranted(AdminVoter::ADMIN_SETTINGS)) {
            yield MenuItem::section('Système');
            yield MenuItem::linkToRoute('Configuration', 'fa fa-cog', 'admin_settings');
        }
        
        // Toujours accessible
        yield MenuItem::linkToUrl('Retour au site', 'fa fa-arrow-left', '/');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('build/admin.css')
            ->addJsFile('build/admin.js');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setName($user->getFirstName() . ' ' . $user->getLastName())
            ->setAvatarUrl(null)
            ->addMenuItems([
                MenuItem::linkToRoute('Mon profil', 'fa fa-user', 'admin_profile'),
                MenuItem::section(),
                MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out-alt'),
            ]);
    }
}
