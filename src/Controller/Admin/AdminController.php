<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ServiceRepository;
use App\Repository\LanguageRepository;
use App\Repository\ServiceTranslationRepository;
use App\Repository\LoanRepository;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;

#[Route('', name: 'admin_')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoanRepository $loanRepository,
        private MediaRepository $mediaRepository
    ) {}

    #[Route('/', name: 'dashboard')]
    public function dashboard(
        ServiceRepository $serviceRepository,
        LanguageRepository $languageRepository,
        ServiceTranslationRepository $translationRepository
    ): Response {
        $services = $serviceRepository->findForAdministration();
        $languages = $languageRepository->getAllOrderedBySortOrder();
        $stats = $translationRepository->getTranslationStats();

        // Nouvelles métriques avancées
        $loanMetrics = $this->getLoanMetrics();
        $recentActivity = $this->getRecentActivity();
        $pendingActions = $this->getPendingActions();
        
        return $this->render('admin/dashboard.html.twig', [
            'services' => $services,
            'languages' => $languages,
            'stats' => $stats,
            'loan_metrics' => $loanMetrics,
            'recent_activity' => $recentActivity,
            'pending_actions' => $pendingActions,
        ]);
    }

    #[Route('/api/metrics', name: 'api_metrics')]
    public function apiMetrics(): JsonResponse
    {
        $metrics = $this->getLoanMetrics();
        $chartData = $this->getChartData();
        
        return $this->json([
            'metrics' => $metrics,
            'charts' => $chartData,
            'timestamp' => time()
        ]);
    }

    #[Route('/api/recent-loans', name: 'api_recent_loans')]
    public function apiRecentLoans(): JsonResponse
    {
        $recentLoans = $this->loanRepository->findBy(
            [], 
            ['createdAt' => 'DESC'], 
            10
        );

        $data = [];
        foreach ($recentLoans as $loan) {
            $data[] = [
                'id' => $loan->getId(),
                'firstName' => $loan->getFirstName(),
                'lastName' => $loan->getLastName(),
                'amount' => $loan->getAmount(),
                'status' => $loan->getStatus(),
                'createdAt' => $loan->getCreatedAt()->format('d/m/Y H:i'),
                'documentsCount' => $loan->getDocuments()->count()
            ];
        }

        return $this->json($data);
    }

    private function getLoanMetrics(): array
    {
        $conn = $this->entityManager->getConnection();
        
        // Métriques principales
        $totalLoans = $this->loanRepository->count([]);
        $pendingLoans = $this->loanRepository->count(['status' => 'pending']);
        $approvedLoans = $this->loanRepository->count(['status' => 'approved']);
        $rejectedLoans = $this->loanRepository->count(['status' => 'rejected']);
        
        // Montants
        $totalAmountQuery = $conn->executeQuery('SELECT SUM(amount) FROM loans WHERE status = ?', ['approved']);
        $totalAmount = $totalAmountQuery->fetchOne() ?: 0;
        
        $avgAmountQuery = $conn->executeQuery('SELECT AVG(amount) FROM loans WHERE status = ?', ['approved']);
        $avgAmount = $avgAmountQuery->fetchOne() ?: 0;
        
        // Métriques temporelles
        $todayLoans = $this->loanRepository->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.createdAt >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
            
        $thisWeekLoans = $this->loanRepository->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.createdAt >= :week')
            ->setParameter('week', new \DateTime('-7 days'))
            ->getQuery()
            ->getSingleScalarResult();
            
        $thisMonthLoans = $this->loanRepository->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.createdAt >= :month')
            ->setParameter('month', new \DateTime('-30 days'))
            ->getQuery()
            ->getSingleScalarResult();
            
        // Taux d'approbation
        $approvalRate = $totalLoans > 0 ? round(($approvedLoans / $totalLoans) * 100, 1) : 0;
        
        // Documents
        $totalDocuments = $this->mediaRepository->count([]);
        $pendingValidation = $this->mediaRepository->count(['status' => 'pending']);
        
        return [
            'total_loans' => $totalLoans,
            'pending_loans' => $pendingLoans,
            'approved_loans' => $approvedLoans,
            'rejected_loans' => $rejectedLoans,
            'total_amount' => $totalAmount,
            'avg_amount' => $avgAmount,
            'today_loans' => $todayLoans,
            'week_loans' => $thisWeekLoans,
            'month_loans' => $thisMonthLoans,
            'approval_rate' => $approvalRate,
            'total_documents' => $totalDocuments,
            'pending_validation' => $pendingValidation,
        ];
    }

    private function getRecentActivity(): array
    {
        // Activité récente des 7 derniers jours
        $recentLoans = $this->loanRepository->createQueryBuilder('l')
            ->where('l.createdAt >= :week')
            ->setParameter('week', new \DateTime('-7 days'))
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
            
        $activity = [];
        foreach ($recentLoans as $loan) {
            $activity[] = [
                'type' => 'loan_created',
                'message' => sprintf(
                    'Nouvelle demande de %s %s pour %s€',
                    $loan->getFirstName(),
                    $loan->getLastName(),
                    number_format($loan->getAmount())
                ),
                'timestamp' => $loan->getCreatedAt(),
                'loan_id' => $loan->getId(),
                'icon' => 'fas fa-plus-circle',
                'color' => 'text-info'
            ];
        }
        
        // Trier par date décroissante
        usort($activity, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        
        return array_slice($activity, 0, 10);
    }

    private function getPendingActions(): array
    {
        $actions = [];
        
        // Demandes en attente
        $pendingLoans = $this->loanRepository->findBy(
            ['status' => 'pending'], 
            ['createdAt' => 'ASC']
        );
        
        foreach ($pendingLoans as $loan) {
            $actions[] = [
                'type' => 'loan_review',
                'priority' => $this->calculatePriority($loan),
                'message' => sprintf(
                    'Examiner la demande de %s %s (%s€)',
                    $loan->getFirstName(),
                    $loan->getLastName(),
                    number_format($loan->getAmount())
                ),
                'url' => $this->generateUrl('admin_loan_show', ['id' => $loan->getId()]),
                'timestamp' => $loan->getCreatedAt(),
                'icon' => 'fas fa-eye',
                'color' => 'text-warning'
            ];
        }
        
        // Documents en attente de validation
        $pendingDocuments = $this->mediaRepository->findBy(
            ['status' => 'pending'],
            ['createdAt' => 'ASC'],
            5
        );
        
        foreach ($pendingDocuments as $document) {
            $actions[] = [
                'type' => 'document_validation',
                'priority' => 'medium',
                'message' => sprintf(
                    'Valider le document "%s"',
                    $document->getDescription() ?: $document->getFilename()
                ),
                'url' => $this->generateUrl('media_show', ['id' => $document->getId()]),
                'timestamp' => $document->getCreatedAt(),
                'icon' => 'fas fa-file-check',
                'color' => 'text-info'
            ];
        }
        
        // Trier par priorité et date
        usort($actions, function($a, $b) {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            $priorityA = $priorityOrder[$a['priority']] ?? 1;
            $priorityB = $priorityOrder[$b['priority']] ?? 1;
            
            if ($priorityA === $priorityB) {
                return $a['timestamp'] <=> $b['timestamp'];
            }
            
            return $priorityB <=> $priorityA;
        });
        
        return array_slice($actions, 0, 15);
    }

    private function calculatePriority($loan): string
    {
        $now = new \DateTime();
        $daysSinceCreation = $now->diff($loan->getCreatedAt())->days;
        
        // Priorité basée sur l'ancienneté et le montant
        if ($daysSinceCreation >= 3 || $loan->getAmount() >= 50000) {
            return 'high';
        } elseif ($daysSinceCreation >= 1 || $loan->getAmount() >= 25000) {
            return 'medium';
        }
        
        return 'low';
    }

    private function getChartData(): array
    {
        $conn = $this->entityManager->getConnection();
        
        // Évolution des demandes sur 30 jours
        $loanEvolution = $conn->executeQuery('
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM loans 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ')->fetchAllAssociative();
        
        // Répartition par statut
        $statusDistribution = $conn->executeQuery('
            SELECT status, COUNT(*) as count
            FROM loans 
            GROUP BY status
        ')->fetchAllAssociative();
        
        // Répartition par montant
        $amountDistribution = $conn->executeQuery('
            SELECT 
                CASE 
                    WHEN amount < 10000 THEN "< 10k€"
                    WHEN amount < 25000 THEN "10k-25k€"
                    WHEN amount < 50000 THEN "25k-50k€"
                    ELSE "> 50k€"
                END as range,
                COUNT(*) as count
            FROM loans 
            GROUP BY range
            ORDER BY MIN(amount)
        ')->fetchAllAssociative();
        
        return [
            'loan_evolution' => $loanEvolution,
            'status_distribution' => $statusDistribution,
            'amount_distribution' => $amountDistribution
        ];
    }
}
