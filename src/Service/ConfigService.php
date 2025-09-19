<?php

namespace App\Service;

use App\Repository\ConfigRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ConfigService
{
    public function __construct(
        private ConfigRepository $configRepository,
        #[Autowire('%kernel.cache_dir%')] private string $cacheDir
    ) {}

    /**
     * Get all configuration values for a specific language
     */
    public function getConfig(string $languageCode = 'fr'): array
    {
        // Try to get from cache first
        $cacheFile = $this->cacheDir . '/config_' . $languageCode . '.php';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) { // 5 minutes cache
            return include $cacheFile;
        }
        
        // Get fresh data
        $config = $this->configRepository->getConfigValues($languageCode);
        
        // Set default values if not configured
        $defaults = [
            'site_name' => 'LoanPro',
            'site_description' => 'Votre partenaire de confiance pour vos prêts',
            'contact_email' => 'contact@loanpro.com',
            'contact_phone' => '+33 1 23 45 67 89',
            'contact_address' => '123 Rue de la Paix, 75001 Paris, France',
            'min_loan_amount' => '1000',
            'max_loan_amount' => '100000',
            'min_loan_duration' => '6',
            'max_loan_duration' => '120',
            'default_interest_rate' => '5.5',
            'primary_color' => '#007bff',
            'secondary_color' => '#6c757d',
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($config[$key]) || empty($config[$key])) {
                $config[$key] = $value;
            }
        }
        
        // Cache the result
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        file_put_contents($cacheFile, '<?php return ' . var_export($config, true) . ';');
        
        return $config;
    }

    /**
     * Get a single configuration value
     */
    public function get(string $key, string $languageCode = 'fr', $default = null)
    {
        $config = $this->getConfig($languageCode);
        return $config[$key] ?? $default;
    }

    /**
     * Clear configuration cache
     */
    public function clearCache(): void
    {
        $pattern = $this->cacheDir . '/config_*.php';
        foreach (glob($pattern) as $file) {
            unlink($file);
        }
    }

    /**
     * Get loan calculation parameters
     */
    public function getLoanParameters(string $languageCode = 'fr'): array
    {
        $config = $this->getConfig($languageCode);
        
        return [
            'minAmount' => (float) $config['min_loan_amount'],
            'maxAmount' => (float) $config['max_loan_amount'],
            'minDuration' => (int) $config['min_loan_duration'],
            'maxDuration' => (int) $config['max_loan_duration'],
            'defaultInterestRate' => (float) $config['default_interest_rate'],
        ];
    }

    /**
     * Get site theme colors
     */
    public function getThemeColors(string $languageCode = 'fr'): array
    {
        $config = $this->getConfig($languageCode);
        
        return [
            'primary' => $config['primary_color'],
            'secondary' => $config['secondary_color'],
        ];
    }
}
