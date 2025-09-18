<?php

namespace App\Controller\Analytics;

use App\Repository\LoanRepository;
use App\Repository\UserRepository;
use App\Service\AnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/analytics')]
#[IsGranted('ROLE_ADMIN')]
class AnalyticsController extends AbstractController
{
    public function __construct(
        private AnalyticsService $analyticsService,
        private LoanRepository $loanRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'analytics_dashboard')]
    public function dashboard(): Response
    {
        $kpis = $this->analyticsService->getKPIs();
        $trends = $this->analyticsService->getTrends();
        $recentActivity = $this->analyticsService->getRecentActivity();

        return $this->render('analytics/dashboard.html.twig', [
            'kpis' => $kpis,
            'trends' => $trends,
            'recent_activity' => $recentActivity
        ]);
    }

    #[Route('/reports', name: 'analytics_reports')]
    public function reports(): Response
    {
        $reports = $this->analyticsService->getAvailableReports();

        return $this->render('analytics/reports.html.twig', [
            'reports' => $reports
        ]);
    }

    #[Route('/loans-analytics', name: 'analytics_loans')]
    public function loansAnalytics(): Response
    {
        $loanStats = $this->analyticsService->getLoanStatistics();
        $loanTrends = $this->analyticsService->getLoanTrends();
        $defaultRates = $this->analyticsService->getDefaultRates();

        return $this->render('analytics/loans.html.twig', [
            'loan_stats' => $loanStats,
            'loan_trends' => $loanTrends,
            'default_rates' => $defaultRates
        ]);
    }

    #[Route('/users-analytics', name: 'analytics_users')]
    public function usersAnalytics(): Response
    {
        $userStats = $this->analyticsService->getUserStatistics();
        $userSegments = $this->analyticsService->getUserSegments();
        $acquisitionTrends = $this->analyticsService->getAcquisitionTrends();

        return $this->render('analytics/users.html.twig', [
            'user_stats' => $userStats,
            'user_segments' => $userSegments,
            'acquisition_trends' => $acquisitionTrends
        ]);
    }

    #[Route('/financial-analytics', name: 'analytics_financial')]
    public function financialAnalytics(): Response
    {
        $financialMetrics = $this->analyticsService->getFinancialMetrics();
        $revenueAnalysis = $this->analyticsService->getRevenueAnalysis();
        $riskAnalysis = $this->analyticsService->getRiskAnalysis();

        return $this->render('analytics/financial.html.twig', [
            'financial_metrics' => $financialMetrics,
            'revenue_analysis' => $revenueAnalysis,
            'risk_analysis' => $riskAnalysis
        ]);
    }

    #[Route('/api/kpis', name: 'api_analytics_kpis', methods: ['GET'])]
    public function getKPIs(): JsonResponse
    {
        $kpis = $this->analyticsService->getKPIs();
        return new JsonResponse($kpis);
    }

    #[Route('/api/chart-data/{type}', name: 'api_analytics_chart_data', methods: ['GET'])]
    public function getChartData(string $type, Request $request): JsonResponse
    {
        $period = $request->query->get('period', '30');
        $filters = $request->query->all();

        $data = match ($type) {
            'loans-trend' => $this->analyticsService->getLoansTrendData($period, $filters),
            'users-growth' => $this->analyticsService->getUsersGrowthData($period, $filters),
            'revenue-analysis' => $this->analyticsService->getRevenueData($period, $filters),
            'risk-distribution' => $this->analyticsService->getRiskDistributionData($filters),
            'conversion-funnel' => $this->analyticsService->getConversionFunnelData($period, $filters),
            'geographic-distribution' => $this->analyticsService->getGeographicData($filters),
            default => ['error' => 'Unknown chart type']
        };

        return new JsonResponse($data);
    }

    #[Route('/api/real-time-metrics', name: 'api_analytics_realtime', methods: ['GET'])]
    public function getRealTimeMetrics(): JsonResponse
    {
        $metrics = $this->analyticsService->getRealTimeMetrics();
        return new JsonResponse($metrics);
    }

    #[Route('/export/{format}', name: 'analytics_export', methods: ['POST'])]
    public function exportReport(string $format, Request $request): Response
    {
        $reportType = $request->request->get('report_type');
        $dateRange = $request->request->get('date_range');
        $filters = $request->request->all();

        $exportData = $this->analyticsService->generateExport($reportType, $dateRange, $filters);

        switch ($format) {
            case 'pdf':
                return $this->analyticsService->exportToPDF($exportData);
            case 'excel':
                return $this->analyticsService->exportToExcel($exportData);
            case 'csv':
                return $this->analyticsService->exportToCSV($exportData);
            default:
                throw $this->createNotFoundException('Format d\'export non supporté');
        }
    }

    #[Route('/api/benchmarks', name: 'api_analytics_benchmarks', methods: ['GET'])]
    public function getBenchmarks(): JsonResponse
    {
        $benchmarks = $this->analyticsService->getBenchmarkData();
        return new JsonResponse($benchmarks);
    }

    #[Route('/api/predictions', name: 'api_analytics_predictions', methods: ['GET'])]
    public function getPredictions(Request $request): JsonResponse
    {
        $metric = $request->query->get('metric');
        $horizon = $request->query->get('horizon', '90'); // days

        $predictions = $this->analyticsService->getPredictions($metric, $horizon);
        return new JsonResponse($predictions);
    }

    #[Route('/api/cohort-analysis', name: 'api_analytics_cohort', methods: ['GET'])]
    public function getCohortAnalysis(Request $request): JsonResponse
    {
        $cohortType = $request->query->get('type', 'monthly');
        $metric = $request->query->get('metric', 'retention');

        $cohortData = $this->analyticsService->getCohortAnalysis($cohortType, $metric);
        return new JsonResponse($cohortData);
    }

    #[Route('/monitoring', name: 'analytics_monitoring')]
    public function monitoring(): Response
    {
        $systemMetrics = $this->analyticsService->getSystemMetrics();
        $performanceMetrics = $this->analyticsService->getPerformanceMetrics();
        $alertsConfig = $this->analyticsService->getAlertsConfiguration();

        return $this->render('analytics/monitoring.html.twig', [
            'system_metrics' => $systemMetrics,
            'performance_metrics' => $performanceMetrics,
            'alerts_config' => $alertsConfig
        ]);
    }

    #[Route('/api/alerts', name: 'api_analytics_alerts', methods: ['GET'])]
    public function getAlerts(): JsonResponse
    {
        $alerts = $this->analyticsService->getActiveAlerts();
        return new JsonResponse($alerts);
    }

    #[Route('/api/health-check', name: 'api_analytics_health', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        $health = $this->analyticsService->getSystemHealth();
        return new JsonResponse($health);
    }
}