<?php

namespace App\Controller\Admin;

use App\Entity\Loan;
use App\Entity\User;
use App\Entity\Media;
use App\Entity\Notification;
use App\Security\Voter\AdminVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
class AdminReportsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/reports', name: 'admin_reports')]
    #[IsGranted(AdminVoter::ADMIN_REPORTS)]
    public function reports(): Response
    {
        // Statistiques générales
        $loanStats = $this->getLoanStatistics();
        $userStats = $this->getUserStatistics();
        $mediaStats = $this->getMediaStatistics();
        $notificationStats = $this->getNotificationStatistics();

        return $this->render('admin/reports/index.html.twig', [
            'loan_stats' => $loanStats,
            'user_stats' => $userStats,
            'media_stats' => $mediaStats,
            'notification_stats' => $notificationStats,
        ]);
    }

    #[Route('/metrics', name: 'admin_metrics')]
    #[IsGranted(AdminVoter::ADMIN_REPORTS)]
    public function metrics(): Response
    {
        return $this->render('admin/metrics/index.html.twig');
    }

    #[Route('/metrics/data', name: 'admin_metrics_data')]
    #[IsGranted(AdminVoter::ADMIN_REPORTS)]
    public function metricsData(): JsonResponse
    {
        // Données pour les graphiques en temps réel
        $data = [
            'loans_by_status' => $this->getLoansByStatus(),
            'loans_by_month' => $this->getLoansByMonth(),
            'users_by_month' => $this->getUsersByMonth(),
            'media_by_type' => $this->getMediaByType(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/settings', name: 'admin_settings')]
    #[IsGranted(AdminVoter::ADMIN_SETTINGS)]
    public function settings(): Response
    {
        return $this->render('admin/settings/index.html.twig');
    }

    #[Route('/profile', name: 'admin_profile')]
    #[IsGranted(AdminVoter::ADMIN_ACCESS)]
    public function profile(): Response
    {
        return $this->render('admin/profile/index.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    private function getLoanStatistics(): array
    {
        $loanRepo = $this->entityManager->getRepository(Loan::class);
        
        $total = $loanRepo->count([]);
        $pending = $loanRepo->count(['status' => 'pending']);
        $approved = $loanRepo->count(['status' => 'approved']);
        $rejected = $loanRepo->count(['status' => 'rejected']);
        $inProgress = $loanRepo->count(['status' => 'in_progress']);
        $completed = $loanRepo->count(['status' => 'completed']);

        // Calcul du montant total des prêts
        $totalAmountQuery = $loanRepo->createQueryBuilder('l')
            ->select('SUM(l.amount)')
            ->where('l.status IN (:statuses)')
            ->setParameter('statuses', ['approved', 'in_progress', 'completed'])
            ->getQuery();
        $totalAmount = $totalAmountQuery->getSingleScalarResult() ?: 0;

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'total_amount' => $totalAmount,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
        ];
    }

    private function getUserStatistics(): array
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        
        $total = $userRepo->count([]);
        $verified = $userRepo->count(['isVerified' => true]);
        $admins = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'verified' => $verified,
            'unverified' => $total - $verified,
            'admins' => $admins,
            'verification_rate' => $total > 0 ? round(($verified / $total) * 100, 2) : 0,
        ];
    }

    private function getMediaStatistics(): array
    {
        $mediaRepo = $this->entityManager->getRepository(Media::class);
        
        $total = $mediaRepo->count([]);
        $byType = $mediaRepo->createQueryBuilder('m')
            ->select('m.type, COUNT(m.id) as count')
            ->groupBy('m.type')
            ->getQuery()
            ->getArrayResult();

        $totalSize = $mediaRepo->createQueryBuilder('m')
            ->select('SUM(m.size)')
            ->getQuery()
            ->getSingleScalarResult() ?: 0;

        return [
            'total' => $total,
            'by_type' => $byType,
            'total_size' => $totalSize,
            'avg_size' => $total > 0 ? round($totalSize / $total, 2) : 0,
        ];
    }

    private function getNotificationStatistics(): array
    {
        $notificationRepo = $this->entityManager->getRepository(Notification::class);
        
        $total = $notificationRepo->count([]);
        $sent = $notificationRepo->count(['status' => 'sent']);
        $pending = $notificationRepo->count(['status' => 'pending']);
        $failed = $notificationRepo->count(['status' => 'failed']);

        return [
            'total' => $total,
            'sent' => $sent,
            'pending' => $pending,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
        ];
    }

    private function getLoansByStatus(): array
    {
        $loanRepo = $this->entityManager->getRepository(Loan::class);
        
        return $loanRepo->createQueryBuilder('l')
            ->select('l.status, COUNT(l.id) as count')
            ->groupBy('l.status')
            ->getQuery()
            ->getArrayResult();
    }

    private function getLoansByMonth(): array
    {
        $loanRepo = $this->entityManager->getRepository(Loan::class);
        
        return $loanRepo->createQueryBuilder('l')
            ->select('YEAR(l.createdAt) as year, MONTH(l.createdAt) as month, COUNT(l.id) as count')
            ->where('l.createdAt >= :date')
            ->setParameter('date', new \DateTime('-12 months'))
            ->groupBy('year, month')
            ->orderBy('year, month')
            ->getQuery()
            ->getArrayResult();
    }

    private function getUsersByMonth(): array
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        
        return $userRepo->createQueryBuilder('u')
            ->select('YEAR(u.createdAt) as year, MONTH(u.createdAt) as month, COUNT(u.id) as count')
            ->where('u.createdAt >= :date')
            ->setParameter('date', new \DateTime('-12 months'))
            ->groupBy('year, month')
            ->orderBy('year, month')
            ->getQuery()
            ->getArrayResult();
    }

    private function getMediaByType(): array
    {
        $mediaRepo = $this->entityManager->getRepository(Media::class);
        
        return $mediaRepo->createQueryBuilder('m')
            ->select('m.type, COUNT(m.id) as count')
            ->groupBy('m.type')
            ->getQuery()
            ->getArrayResult();
    }
}
