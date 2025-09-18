<?php

namespace App\Service;

use App\Repository\LoanRepository;
use App\Repository\UserRepository;
use App\Repository\AuditLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsService
{
    public function __construct(
        private LoanRepository $loanRepository,
        private UserRepository $userRepository,
        private AuditLogRepository $auditLogRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function getKPIs(): array
    {
        $totalLoans = $this->loanRepository->count([]);
        $activeLoans = $this->loanRepository->count(['status' => ['pending', 'approved']]);
        $totalUsers = $this->userRepository->count([]);
        $thisMonthLoans = $this->loanRepository->countByPeriod('this_month');
        $lastMonthLoans = $this->loanRepository->countByPeriod('last_month');
        
        $growthRate = $lastMonthLoans > 0 ? 
            (($thisMonthLoans - $lastMonthLoans) / $lastMonthLoans) * 100 : 0;

        $totalAmount = $this->loanRepository->getTotalLoanAmount();
        $averageAmount = $totalLoans > 0 ? $totalAmount / $totalLoans : 0;
        $approvalRate = $this->loanRepository->getApprovalRate();
        $defaultRate = $this->loanRepository->getDefaultRate();

        return [
            'total_loans' => $totalLoans,
            'active_loans' => $activeLoans,
            'total_users' => $totalUsers,
            'growth_rate' => round($growthRate, 2),
            'total_amount' => $totalAmount,
            'average_amount' => round($averageAmount, 2),
            'approval_rate' => round($approvalRate, 2),
            'default_rate' => round($defaultRate, 2),
            'conversion_rate' => $this->calculateConversionRate(),
            'customer_satisfaction' => $this->getCustomerSatisfactionScore(),
            'revenue_this_month' => $this->getMonthlyRevenue(),
            'active_sessions' => $this->getActiveSessions()
        ];
    }

    public function getTrends(): array
    {
        return [
            'loans_trend' => $this->getLoansTrend(),
            'users_trend' => $this->getUsersTrend(),
            'revenue_trend' => $this->getRevenueTrend(),
            'approval_trend' => $this->getApprovalTrend()
        ];
    }

    public function getRecentActivity(): array
    {
        return $this->auditLogRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            20
        );
    }

    public function getLoanStatistics(): array
    {
        return [
            'by_status' => $this->loanRepository->getCountByStatus(),
            'by_amount_range' => $this->loanRepository->getCountByAmountRange(),
            'by_duration' => $this->loanRepository->getCountByDuration(),
            'monthly_evolution' => $this->loanRepository->getMonthlyEvolution(12),
            'average_processing_time' => $this->loanRepository->getAverageProcessingTime(),
            'peak_hours' => $this->loanRepository->getPeakHours(),
            'seasonal_patterns' => $this->loanRepository->getSeasonalPatterns()
        ];
    }

    public function getLoanTrends(): array
    {
        return [
            'volume_trend' => $this->loanRepository->getVolumeTrend(30),
            'amount_trend' => $this->loanRepository->getAmountTrend(30),
            'approval_trend' => $this->loanRepository->getApprovalTrend(30),
            'duration_trend' => $this->loanRepository->getDurationTrend(30)
        ];
    }

    public function getDefaultRates(): array
    {
        return [
            'overall' => $this->loanRepository->getDefaultRate(),
            'by_amount_range' => $this->loanRepository->getDefaultRateByAmountRange(),
            'by_duration' => $this->loanRepository->getDefaultRateByDuration(),
            'by_user_profile' => $this->loanRepository->getDefaultRateByUserProfile(),
            'trend' => $this->loanRepository->getDefaultRateTrend(12)
        ];
    }

    public function getUserStatistics(): array
    {
        return [
            'total_users' => $this->userRepository->count([]),
            'active_users' => $this->userRepository->countActiveUsers(),
            'new_users_this_month' => $this->userRepository->countNewUsersThisMonth(),
            'user_growth_rate' => $this->userRepository->getUserGrowthRate(),
            'demographics' => $this->userRepository->getDemographics(),
            'activity_levels' => $this->userRepository->getActivityLevels(),
            'retention_rates' => $this->userRepository->getRetentionRates()
        ];
    }

    public function getUserSegments(): array
    {
        return [
            'by_loan_count' => $this->userRepository->getUsersByLoanCount(),
            'by_total_amount' => $this->userRepository->getUsersByTotalAmount(),
            'by_activity' => $this->userRepository->getUsersByActivity(),
            'by_registration_date' => $this->userRepository->getUsersByRegistrationDate(),
            'high_value_customers' => $this->userRepository->getHighValueCustomers(),
            'at_risk_customers' => $this->userRepository->getAtRiskCustomers()
        ];
    }

    public function getAcquisitionTrends(): array
    {
        return [
            'daily_registrations' => $this->userRepository->getDailyRegistrations(30),
            'acquisition_channels' => $this->userRepository->getAcquisitionChannels(),
            'conversion_by_source' => $this->userRepository->getConversionBySource(),
            'cost_per_acquisition' => $this->calculateCostPerAcquisition()
        ];
    }

    public function getFinancialMetrics(): array
    {
        return [
            'total_portfolio_value' => $this->loanRepository->getTotalPortfolioValue(),
            'outstanding_balance' => $this->loanRepository->getOutstandingBalance(),
            'monthly_revenue' => $this->getMonthlyRevenue(),
            'profit_margins' => $this->getProfitMargins(),
            'cost_structure' => $this->getCostStructure(),
            'roi_metrics' => $this->getROIMetrics(),
            'cash_flow' => $this->getCashFlowAnalysis()
        ];
    }

    public function getRevenueAnalysis(): array
    {
        return [
            'monthly_revenue' => $this->getMonthlyRevenueBreakdown(),
            'revenue_by_product' => $this->getRevenueByProduct(),
            'revenue_per_customer' => $this->getRevenuePerCustomer(),
            'recurring_revenue' => $this->getRecurringRevenue(),
            'revenue_forecast' => $this->getRevenueForecast()
        ];
    }

    public function getRiskAnalysis(): array
    {
        return [
            'credit_risk_distribution' => $this->getCreditRiskDistribution(),
            'portfolio_concentration' => $this->getPortfolioConcentration(),
            'risk_adjusted_returns' => $this->getRiskAdjustedReturns(),
            'stress_test_results' => $this->getStressTestResults(),
            'early_warning_indicators' => $this->getEarlyWarningIndicators()
        ];
    }

    public function getLoansTrendData(string $period, array $filters): array
    {
        $days = match ($period) {
            '7' => 7,
            '30' => 30,
            '90' => 90,
            '365' => 365,
            default => 30
        };

        return $this->loanRepository->getTrendData($days, $filters);
    }

    public function getUsersGrowthData(string $period, array $filters): array
    {
        $days = match ($period) {
            '7' => 7,
            '30' => 30,
            '90' => 90,
            '365' => 365,
            default => 30
        };

        return $this->userRepository->getGrowthData($days, $filters);
    }

    public function getRevenueData(string $period, array $filters): array
    {
        // Calculate revenue data based on loans and interest
        return $this->loanRepository->getRevenueData($period, $filters);
    }

    public function getRiskDistributionData(array $filters): array
    {
        return $this->loanRepository->getRiskDistribution($filters);
    }

    public function getConversionFunnelData(string $period, array $filters): array
    {
        return [
            'visitors' => $this->getVisitorCount($period),
            'registrations' => $this->userRepository->countNewUsersByPeriod($period),
            'loan_applications' => $this->loanRepository->countApplicationsByPeriod($period),
            'approvals' => $this->loanRepository->countApprovalsByPeriod($period),
            'active_loans' => $this->loanRepository->countActiveByPeriod($period)
        ];
    }

    public function getGeographicData(array $filters): array
    {
        return $this->userRepository->getGeographicDistribution($filters);
    }

    public function getRealTimeMetrics(): array
    {
        return [
            'active_users_now' => $this->getActiveUsersNow(),
            'loans_today' => $this->loanRepository->countToday(),
            'revenue_today' => $this->getRevenueToday(),
            'conversion_rate_today' => $this->getConversionRateToday(),
            'system_health' => $this->getSystemHealth(),
            'last_updated' => new \DateTime()
        ];
    }

    public function generateExport(string $reportType, string $dateRange, array $filters): array
    {
        return match ($reportType) {
            'loans' => $this->loanRepository->getExportData($dateRange, $filters),
            'users' => $this->userRepository->getExportData($dateRange, $filters),
            'revenue' => $this->getRevenueExportData($dateRange, $filters),
            'analytics' => $this->getAnalyticsExportData($dateRange, $filters),
            default => []
        };
    }

    public function exportToPDF(array $data): Response
    {
        // Implementation would use a PDF library like TCPDF or DomPDF
        // For now, return a placeholder response
        return new Response('PDF Export - Implementation needed', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="report.pdf"'
        ]);
    }

    public function exportToExcel(array $data): Response
    {
        // Implementation would use PhpSpreadsheet
        return new Response('Excel Export - Implementation needed', 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="report.xlsx"'
        ]);
    }

    public function exportToCSV(array $data): Response
    {
        $csv = "Date,Metric,Value\n";
        foreach ($data as $row) {
            $csv .= implode(',', $row) . "\n";
        }

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="report.csv"'
        ]);
    }

    public function getBenchmarkData(): array
    {
        return [
            'industry_averages' => [
                'approval_rate' => 75.5,
                'default_rate' => 3.2,
                'average_loan_amount' => 15000,
                'processing_time' => 72 // hours
            ],
            'our_performance' => [
                'approval_rate' => $this->loanRepository->getApprovalRate(),
                'default_rate' => $this->loanRepository->getDefaultRate(),
                'average_loan_amount' => $this->loanRepository->getAverageLoanAmount(),
                'processing_time' => $this->loanRepository->getAverageProcessingTime()
            ]
        ];
    }

    public function getPredictions(string $metric, string $horizon): array
    {
        // Simple linear regression prediction
        $historicalData = $this->getHistoricalData($metric, 90);
        return $this->calculatePrediction($historicalData, (int)$horizon);
    }

    public function getCohortAnalysis(string $cohortType, string $metric): array
    {
        return $this->userRepository->getCohortAnalysis($cohortType, $metric);
    }

    public function getSystemMetrics(): array
    {
        return [
            'cpu_usage' => $this->getCPUUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'database_connections' => $this->getDatabaseConnections(),
            'response_time' => $this->getAverageResponseTime(),
            'error_rate' => $this->getErrorRate()
        ];
    }

    public function getPerformanceMetrics(): array
    {
        return [
            'page_load_times' => $this->getPageLoadTimes(),
            'api_response_times' => $this->getAPIResponseTimes(),
            'database_query_times' => $this->getDatabaseQueryTimes(),
            'cache_hit_ratio' => $this->getCacheHitRatio(),
            'throughput' => $this->getThroughput()
        ];
    }

    public function getAlertsConfiguration(): array
    {
        return [
            'thresholds' => [
                'high_cpu' => 80,
                'high_memory' => 85,
                'slow_response' => 5000, // ms
                'high_error_rate' => 5 // %
            ],
            'notification_channels' => ['email', 'slack', 'sms'],
            'escalation_rules' => $this->getEscalationRules()
        ];
    }

    public function getActiveAlerts(): array
    {
        // Check current metrics against thresholds
        $alerts = [];
        $metrics = $this->getSystemMetrics();
        $config = $this->getAlertsConfiguration();

        if ($metrics['cpu_usage'] > $config['thresholds']['high_cpu']) {
            $alerts[] = [
                'type' => 'high_cpu',
                'severity' => 'warning',
                'message' => "CPU usage is {$metrics['cpu_usage']}%",
                'created_at' => new \DateTime()
            ];
        }

        return $alerts;
    }

    public function getSystemHealth(): array
    {
        $metrics = $this->getSystemMetrics();
        $performance = $this->getPerformanceMetrics();

        $healthScore = $this->calculateHealthScore($metrics, $performance);

        return [
            'overall_health' => $healthScore,
            'status' => $healthScore >= 90 ? 'excellent' : 
                       ($healthScore >= 70 ? 'good' : 
                       ($healthScore >= 50 ? 'warning' : 'critical')),
            'components' => [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'storage' => $this->checkStorageHealth(),
                'external_apis' => $this->checkExternalAPIsHealth()
            ],
            'uptime' => $this->getUptime(),
            'last_check' => new \DateTime()
        ];
    }

    // Private helper methods
    private function calculateConversionRate(): float
    {
        $totalApplications = $this->loanRepository->count([]);
        $approvedApplications = $this->loanRepository->count(['status' => 'approved']);
        
        return $totalApplications > 0 ? ($approvedApplications / $totalApplications) * 100 : 0;
    }

    private function getCustomerSatisfactionScore(): float
    {
        // Placeholder - would integrate with survey data
        return 4.2;
    }

    private function getMonthlyRevenue(): float
    {
        return $this->loanRepository->getMonthlyRevenue();
    }

    private function getActiveSessions(): int
    {
        // Placeholder - would integrate with session tracking
        return rand(15, 45);
    }

    private function getLoansTrend(): array
    {
        return $this->loanRepository->getTrendData(30, []);
    }

    private function getUsersTrend(): array
    {
        return $this->userRepository->getGrowthData(30, []);
    }

    private function getRevenueTrend(): array
    {
        return $this->loanRepository->getRevenueData('30', []);
    }

    private function getApprovalTrend(): array
    {
        return $this->loanRepository->getApprovalTrend(30);
    }

    private function calculateCostPerAcquisition(): float
    {
        // Placeholder calculation
        return 125.50;
    }

    private function getMonthlyRevenueBreakdown(): array
    {
        return $this->loanRepository->getMonthlyRevenueBreakdown();
    }

    private function getRevenueByProduct(): array
    {
        return $this->loanRepository->getRevenueByProduct();
    }

    private function getRevenuePerCustomer(): float
    {
        $totalRevenue = $this->getMonthlyRevenue();
        $totalCustomers = $this->userRepository->count([]);
        
        return $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;
    }

    private function getRecurringRevenue(): array
    {
        return $this->loanRepository->getRecurringRevenue();
    }

    private function getRevenueForecast(): array
    {
        $historicalRevenue = $this->loanRepository->getRevenueHistory(12);
        return $this->calculatePrediction($historicalRevenue, 90);
    }

    private function getCreditRiskDistribution(): array
    {
        return $this->loanRepository->getCreditRiskDistribution();
    }

    private function getPortfolioConcentration(): array
    {
        return $this->loanRepository->getPortfolioConcentration();
    }

    private function getRiskAdjustedReturns(): array
    {
        return $this->loanRepository->getRiskAdjustedReturns();
    }

    private function getStressTestResults(): array
    {
        // Placeholder for stress testing
        return [
            'scenario_1' => ['loss_rate' => 2.5, 'impact' => 'low'],
            'scenario_2' => ['loss_rate' => 8.2, 'impact' => 'medium'],
            'scenario_3' => ['loss_rate' => 15.7, 'impact' => 'high']
        ];
    }

    private function getEarlyWarningIndicators(): array
    {
        return $this->loanRepository->getEarlyWarningIndicators();
    }

    private function getVisitorCount(string $period): int
    {
        // Placeholder - would integrate with analytics
        return rand(1000, 5000);
    }

    private function getActiveUsersNow(): int
    {
        // Placeholder - would track real-time sessions
        return rand(10, 50);
    }

    private function getRevenueToday(): float
    {
        return $this->loanRepository->getRevenueToday();
    }

    private function getConversionRateToday(): float
    {
        return $this->loanRepository->getConversionRateToday();
    }

    private function getRevenueExportData(string $dateRange, array $filters): array
    {
        return $this->loanRepository->getRevenueExportData($dateRange, $filters);
    }

    private function getAnalyticsExportData(string $dateRange, array $filters): array
    {
        return [
            'kpis' => $this->getKPIs(),
            'trends' => $this->getTrends(),
            'period' => $dateRange,
            'filters' => $filters,
            'generated_at' => new \DateTime()
        ];
    }

    private function getHistoricalData(string $metric, int $days): array
    {
        return match ($metric) {
            'loans' => $this->loanRepository->getHistoricalData($days),
            'users' => $this->userRepository->getHistoricalData($days),
            'revenue' => $this->loanRepository->getRevenueHistory($days),
            default => []
        };
    }

    private function calculatePrediction(array $historicalData, int $horizon): array
    {
        // Simple linear regression prediction
        if (count($historicalData) < 2) {
            return [];
        }

        $n = count($historicalData);
        $sumX = $sumY = $sumXY = $sumX2 = 0;

        foreach ($historicalData as $i => $value) {
            $sumX += $i;
            $sumY += $value;
            $sumXY += $i * $value;
            $sumX2 += $i * $i;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        $predictions = [];
        for ($i = $n; $i < $n + $horizon; $i++) {
            $predictions[] = [
                'date' => (new \DateTime())->modify("+{$i} days")->format('Y-m-d'),
                'predicted_value' => round($slope * $i + $intercept, 2)
            ];
        }

        return $predictions;
    }

    private function getCPUUsage(): float
    {
        // Placeholder - would use system monitoring
        return round(rand(20, 80) + rand(0, 100) / 100, 2);
    }

    private function getMemoryUsage(): float
    {
        return round(memory_get_usage(true) / memory_get_peak_usage(true) * 100, 2);
    }

    private function getDiskUsage(): float
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        return round(($total - $free) / $total * 100, 2);
    }

    private function getDatabaseConnections(): int
    {
        // Placeholder - would query database
        return rand(5, 20);
    }

    private function getAverageResponseTime(): float
    {
        // Placeholder - would use APM
        return round(rand(100, 500) + rand(0, 100) / 100, 2);
    }

    private function getErrorRate(): float
    {
        // Placeholder - would use error tracking
        return round(rand(0, 5) + rand(0, 100) / 100, 2);
    }

    private function getPageLoadTimes(): array
    {
        return [
            'homepage' => rand(800, 1200),
            'dashboard' => rand(1000, 1500),
            'loan_form' => rand(1200, 1800)
        ];
    }

    private function getAPIResponseTimes(): array
    {
        return [
            'auth' => rand(50, 150),
            'loans' => rand(100, 300),
            'users' => rand(80, 200)
        ];
    }

    private function getDatabaseQueryTimes(): array
    {
        return [
            'select' => rand(10, 50),
            'insert' => rand(20, 80),
            'update' => rand(15, 60)
        ];
    }

    private function getCacheHitRatio(): float
    {
        return round(rand(85, 98) + rand(0, 100) / 100, 2);
    }

    private function getThroughput(): int
    {
        return rand(100, 500); // requests per minute
    }

    private function getEscalationRules(): array
    {
        return [
            'level_1' => ['after' => 5, 'notify' => ['support_team']],
            'level_2' => ['after' => 15, 'notify' => ['managers']],
            'level_3' => ['after' => 30, 'notify' => ['executives']]
        ];
    }

    private function calculateHealthScore(array $metrics, array $performance): int
    {
        $score = 100;
        
        if ($metrics['cpu_usage'] > 80) $score -= 20;
        if ($metrics['memory_usage'] > 85) $score -= 15;
        if ($metrics['response_time'] > 2000) $score -= 10;
        if ($metrics['error_rate'] > 5) $score -= 25;
        
        return max(0, $score);
    }

    private function checkDatabaseHealth(): string
    {
        try {
            $this->entityManager->getConnection()->connect();
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    private function checkCacheHealth(): string
    {
        // Placeholder - would check cache connectivity
        return 'healthy';
    }

    private function checkStorageHealth(): string
    {
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usagePercent = ($totalSpace - $freeSpace) / $totalSpace * 100;
        
        return $usagePercent < 90 ? 'healthy' : 'warning';
    }

    private function checkExternalAPIsHealth(): string
    {
        // Placeholder - would check external API connectivity
        return 'healthy';
    }

    private function getUptime(): string
    {
        // Placeholder - would track application uptime
        return '99.95%';
    }

    private function getProfitMargins(): array
    {
        return [
            'gross_margin' => 45.2,
            'operating_margin' => 23.8,
            'net_margin' => 18.5
        ];
    }

    private function getCostStructure(): array
    {
        return [
            'operational_costs' => 150000,
            'marketing_costs' => 45000,
            'technology_costs' => 35000,
            'personnel_costs' => 200000
        ];
    }

    private function getROIMetrics(): array
    {
        return [
            'roa' => 12.5, // Return on Assets
            'roe' => 18.3, // Return on Equity
            'roi' => 15.7  // Return on Investment
        ];
    }

    private function getCashFlowAnalysis(): array
    {
        return [
            'operating_cash_flow' => 85000,
            'investing_cash_flow' => -25000,
            'financing_cash_flow' => -15000,
            'net_cash_flow' => 45000
        ];
    }
}