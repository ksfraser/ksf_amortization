<?php

declare(strict_types=1);

namespace Ksfraser\Localization;

/**
 * Currency converter with exchange rate management
 *
 * Handles currency conversion, exchange rate management, and formatting.
 */
class CurrencyConverter
{
    /**
     * @var array Exchange rates (base currency -> target currency -> rate)
     */
    private array $exchangeRates = [];

    /**
     * @var string Base currency
     */
    private string $baseCurrency = 'USD';

    /**
     * @var array Cached conversion results
     */
    private array $conversionCache = [];

    /**
     * @var array Supported currencies and their formats
     */
    private array $currencyFormats = [
        'USD' => ['symbol' => '$', 'decimals' => 2, 'format' => '$#'],
        'EUR' => ['symbol' => '€', 'decimals' => 2, 'format' => '#€'],
        'GBP' => ['symbol' => '£', 'decimals' => 2, 'format' => '£#'],
        'JPY' => ['symbol' => '¥', 'decimals' => 0, 'format' => '¥#'],
        'CAD' => ['symbol' => 'C$', 'decimals' => 2, 'format' => 'C$#'],
        'AUD' => ['symbol' => 'A$', 'decimals' => 2, 'format' => 'A$#'],
    ];

    public function __construct(string $baseCurrency = 'USD')
    {
        $this->baseCurrency = $baseCurrency;
        $this->initializeExchangeRates();
    }

    /**
     * Initialize default exchange rates
     */
    private function initializeExchangeRates(): void
    {
        // Sample rates relative to base currency
        $this->exchangeRates = [
            'USD' => [
                'USD' => 1.0,
                'EUR' => 0.92,
                'GBP' => 0.79,
                'JPY' => 149.50,
                'CAD' => 1.36,
                'AUD' => 1.53,
            ],
            'EUR' => [
                'USD' => 1.09,
                'EUR' => 1.0,
                'GBP' => 0.86,
                'JPY' => 162.50,
                'CAD' => 1.48,
                'AUD' => 1.66,
            ],
            'GBP' => [
                'USD' => 1.27,
                'EUR' => 1.16,
                'GBP' => 1.0,
                'JPY' => 189.00,
                'CAD' => 1.72,
                'AUD' => 1.93,
            ],
        ];
    }

    /**
     * Set exchange rate
     */
    public function setExchangeRate(string $from, string $to, float $rate): void
    {
        if (!isset($this->exchangeRates[$from])) {
            $this->exchangeRates[$from] = [];
        }

        $this->exchangeRates[$from][$to] = $rate;
        $this->conversionCache = []; // Clear cache
    }

    /**
     * Get exchange rate
     */
    public function getExchangeRate(string $from, string $to): ?float
    {
        return $this->exchangeRates[$from][$to] ?? null;
    }

    /**
     * Convert amount between currencies
     */
    public function convert(float $amount, string $from, string $to, int $decimals = 2): float
    {
        if ($from === $to) {
            return round($amount, $decimals);
        }

        $cacheKey = "$from:$to:$amount";
        if (isset($this->conversionCache[$cacheKey])) {
            return $this->conversionCache[$cacheKey];
        }

        $rate = $this->getExchangeRate($from, $to);
        if ($rate === null) {
            throw new \InvalidArgumentException("Exchange rate not found for $from -> $to");
        }

        $converted = $amount * $rate;
        $result = round($converted, $decimals);

        $this->conversionCache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Format amount in specified currency
     */
    public function format(float $amount, string $currency): string
    {
        if (!isset($this->currencyFormats[$currency])) {
            return $amount . ' ' . $currency;
        }

        $format = $this->currencyFormats[$currency];
        $decimals = $format['decimals'];
        $symbol = $format['symbol'];

        $formatted = number_format($amount, $decimals, '.', ',');

        // Replace placeholder with formatted amount
        return str_replace('#', $formatted, $format['format']);
    }

    /**
     * Register a new currency
     */
    public function registerCurrency(string $code, string $symbol, int $decimals, string $format): void
    {
        $this->currencyFormats[$code] = [
            'symbol' => $symbol,
            'decimals' => $decimals,
            'format' => $format,
        ];
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return array_keys($this->currencyFormats);
    }

    /**
     * Check if currency is supported
     */
    public function isSupportedCurrency(string $currency): bool
    {
        return isset($this->currencyFormats[$currency]);
    }

    /**
     * Get currency format info
     */
    public function getCurrencyFormat(string $currency): ?array
    {
        return $this->currencyFormats[$currency] ?? null;
    }

    /**
     * Set base currency
     */
    public function setBaseCurrency(string $currency): void
    {
        $this->baseCurrency = $currency;
    }

    /**
     * Get base currency
     */
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    /**
     * Clear conversion cache
     */
    public function clearCache(): void
    {
        $this->conversionCache = [];
    }

    /**
     * Batch convert amounts
     */
    public function batchConvert(array $amounts, string $from, string $to, int $decimals = 2): array
    {
        $result = [];
        foreach ($amounts as $key => $amount) {
            $result[$key] = $this->convert($amount, $from, $to, $decimals);
        }
        return $result;
    }
}

/**
 * Translation manager for multi-language support
 *
 * Manages translations, language switching, and localization.
 */
class TranslationManager
{
    /**
     * @var array Translations [language][key] => translation
     */
    private array $translations = [];

    /**
     * @var string Current language
     */
    private string $currentLanguage = 'en';

    /**
     * @var string Fallback language
     */
    private string $fallbackLanguage = 'en';

    /**
     * @var array Loaded languages
     */
    private array $loadedLanguages = [];

    public function __construct(string $defaultLanguage = 'en')
    {
        $this->currentLanguage = $defaultLanguage;
        $this->fallbackLanguage = $defaultLanguage;
    }

    /**
     * Add translation
     */
    public function addTranslation(string $language, string $key, string $value): void
    {
        if (!isset($this->translations[$language])) {
            $this->translations[$language] = [];
        }

        $this->translations[$language][$key] = $value;
    }

    /**
     * Add translations batch
     */
    public function addTranslations(string $language, array $translations): void
    {
        if (!isset($this->translations[$language])) {
            $this->translations[$language] = [];
        }

        foreach ($translations as $key => $value) {
            $this->translations[$language][$key] = $value;
        }
    }

    /**
     * Get translation
     */
    public function translate(string $key, string $language = null, array $params = []): string
    {
        $lang = $language ?? $this->currentLanguage;

        if (!isset($this->translations[$lang][$key])) {
            if (!isset($this->translations[$this->fallbackLanguage][$key])) {
                return $key; // Return key if no translation found
            }
            $translation = $this->translations[$this->fallbackLanguage][$key];
        } else {
            $translation = $this->translations[$lang][$key];
        }

        // Replace parameters
        foreach ($params as $param => $value) {
            $translation = str_replace(':' . $param, $value, $translation);
        }

        return $translation;
    }

    /**
     * Alias for translate
     */
    public function t(string $key, array $params = []): string
    {
        return $this->translate($key, null, $params);
    }

    /**
     * Set current language
     */
    public function setLanguage(string $language): void
    {
        $this->currentLanguage = $language;
        if (!isset($this->loadedLanguages[$language])) {
            $this->loadedLanguages[$language] = true;
        }
    }

    /**
     * Get current language
     */
    public function getLanguage(): string
    {
        return $this->currentLanguage;
    }

    /**
     * Get available languages
     */
    public function getAvailableLanguages(): array
    {
        return array_keys($this->translations);
    }

    /**
     * Get all translations for language
     */
    public function getTranslations(string $language): array
    {
        return $this->translations[$language] ?? [];
    }

    /**
     * Check if language exists
     */
    public function hasLanguage(string $language): bool
    {
        return isset($this->translations[$language]);
    }

    /**
     * Check if translation exists
     */
    public function hasTranslation(string $key, string $language = null): bool
    {
        $lang = $language ?? $this->currentLanguage;
        return isset($this->translations[$lang][$key]);
    }

    /**
     * Format date based on language/locale
     */
    public function formatDate(\DateTime $date, string $format = 'medium'): string
    {
        $locale = $this->getLocaleForLanguage($this->currentLanguage);

        $formats = [
            'short' => 'm/d/Y',
            'medium' => 'M d, Y',
            'long' => 'l, F d, Y',
        ];

        $dateFormat = $formats[$format] ?? 'm/d/Y';
        return $date->format($dateFormat);
    }

    /**
     * Format number based on language/locale
     */
    public function formatNumber(float $number, int $decimals = 2): string
    {
        $locale = $this->getLocaleForLanguage($this->currentLanguage);

        if ($locale === 'de') {
            return number_format($number, $decimals, ',', '.');
        } elseif ($locale === 'fr') {
            return number_format($number, $decimals, ',', ' ');
        }

        return number_format($number, $decimals, '.', ',');
    }

    /**
     * Get locale for language
     */
    private function getLocaleForLanguage(string $language): string
    {
        $locales = [
            'en' => 'en_US',
            'de' => 'de_DE',
            'fr' => 'fr_FR',
            'es' => 'es_ES',
            'it' => 'it_IT',
            'ja' => 'ja_JP',
            'zh' => 'zh_CN',
        ];

        return $locales[$language] ?? 'en_US';
    }
}

/**
 * Compliance manager for GDPR and data regulations
 *
 * Manages data retention policies, privacy controls, and consent management.
 */
class ComplianceManager
{
    /**
     * @var array Data retention policies
     */
    private array $retentionPolicies = [];

    /**
     * @var array User consent records
     */
    private array $consentRecords = [];

    /**
     * @var array Data access logs
     */
    private array $dataAccessLogs = [];

    /**
     * @var bool GDPR compliance enabled
     */
    private bool $gdprEnabled = true;

    public function __construct(bool $gdprEnabled = true)
    {
        $this->gdprEnabled = $gdprEnabled;
        $this->initializeDefaultPolicies();
    }

    /**
     * Initialize default retention policies
     */
    private function initializeDefaultPolicies(): void
    {
        $this->retentionPolicies = [
            'user_data' => 1825,     // 5 years in days
            'transaction_logs' => 2555,  // 7 years for financial records
            'access_logs' => 90,     // 3 months
            'consent_records' => 2555,   // 7 years
        ];
    }

    /**
     * Record user consent
     */
    public function recordConsent(string $userId, string $type, bool $consented, ?string $ipAddress = null): void
    {
        $this->consentRecords[] = [
            'user_id' => $userId,
            'type' => $type,
            'consented' => $consented,
            'ip_address' => $ipAddress,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Check if user has given consent
     */
    public function hasConsent(string $userId, string $type): bool
    {
        // Get most recent consent record for this user and type
        $records = array_filter($this->consentRecords, fn($record) =>
            $record['user_id'] === $userId && $record['type'] === $type
        );

        if (empty($records)) {
            return false;
        }

        $lastRecord = end($records);
        return $lastRecord['consented'];
    }

    /**
     * Get user consent records
     */
    public function getConsentHistory(string $userId): array
    {
        return array_filter($this->consentRecords, fn($record) =>
            $record['user_id'] === $userId
        );
    }

    /**
     * Log data access
     */
    public function logDataAccess(string $userId, string $dataType, string $action, ?string $ipAddress = null): void
    {
        $this->dataAccessLogs[] = [
            'user_id' => $userId,
            'data_type' => $dataType,
            'action' => $action,
            'ip_address' => $ipAddress,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get data access log
     */
    public function getDataAccessLog(int $limit = 100): array
    {
        return array_slice($this->dataAccessLogs, -$limit);
    }

    /**
     * Set retention policy
     */
    public function setRetentionPolicy(string $dataType, int $days): void
    {
        $this->retentionPolicies[$dataType] = $days;
    }

    /**
     * Get retention policy
     */
    public function getRetentionPolicy(string $dataType): ?int
    {
        return $this->retentionPolicies[$dataType] ?? null;
    }

    /**
     * Get all retention policies
     */
    public function getRetentionPolicies(): array
    {
        return $this->retentionPolicies;
    }

    /**
     * Check if data should be retained
     */
    public function shouldRetainData(string $dataType, string $createdAt): bool
    {
        $policy = $this->getRetentionPolicy($dataType);
        if ($policy === null) {
            return true; // No policy, retain by default
        }

        $createdDate = new \DateTime($createdAt);
        $expiryDate = $createdDate->modify("+$policy days");

        return new \DateTime() < $expiryDate;
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        $consentTypes = [];
        foreach ($this->consentRecords as $record) {
            if (!isset($consentTypes[$record['type']])) {
                $consentTypes[$record['type']] = ['consented' => 0, 'denied' => 0];
            }
            if ($record['consented']) {
                $consentTypes[$record['type']]['consented']++;
            } else {
                $consentTypes[$record['type']]['denied']++;
            }
        }

        return [
            'total_consent_records' => count($this->consentRecords),
            'total_data_access_logs' => count($this->dataAccessLogs),
            'consent_breakdown' => $consentTypes,
            'retention_policies' => count($this->retentionPolicies),
        ];
    }

    /**
     * Enable GDPR compliance
     */
    public function enableGDPR(): void
    {
        $this->gdprEnabled = true;
    }

    /**
     * Disable GDPR compliance
     */
    public function disableGDPR(): void
    {
        $this->gdprEnabled = false;
    }

    /**
     * Check if GDPR is enabled
     */
    public function isGDPREnabled(): bool
    {
        return $this->gdprEnabled;
    }
}
