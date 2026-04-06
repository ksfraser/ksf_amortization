<?php
namespace Ksfraser\Api\Controllers;

use Ksfraser\Monitoring\PerformanceMetrics;

/**
 * Metrics Controller - Performance Metrics REST API
 * 
 * Provides real-time metrics and performance data for dashboard visualization.
 * 
 * Endpoints:
 * - GET /api/v1/admin/metrics        - Get aggregated metrics
 * - GET /api/v1/admin/metrics/live   - Get real-time metrics (streaming)
 * - GET /api/v1/admin/dashboard      - Get dashboard summary
 * 
 * @package   Ksfraser\Api\Controllers
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class MetricsController
{
    /**
     * @var PerformanceMetrics Metrics collector
     */
    private $metrics;

    /**
     * Constructor
     *
     * @param PerformanceMetrics|null $metrics Metrics collector
     */
    public function __construct(?PerformanceMetrics $metrics = null)
    {
        $this->metrics = $metrics ?? new PerformanceMetrics();
    }

    /**
     * GET /api/v1/admin/metrics
     * 
     * Get aggregated metrics for dashboard
     *
     * @param array $query Query parameters: { period, metric_type }
     *
     * @return array JSON response
     */
    public function getMetrics(array $query = []): array
    {
        try {
            $period = $query['period'] ?? 'last_hour'; // last_hour, last_day, last_week
            $metricType = $query['metric_type'] ?? null; // specific metric or all

            // Get dashboard data from metrics collector
            $dashboard = $this->metrics->getDashboardData();

            $response = [
                'period' => $period,
                'timestamp' => date('Y-m-d H:i:s'),
                'performance' => $dashboard['performance'] ?? [],
                'resources' => $dashboard['resources'] ?? [],
                'cache_hit_rates' => $dashboard['top_metrics'] ?? [],
                'error_summary' => [
                    'total_errors' => 0,
                    'error_rate' => 0,
                    'top_errors' => []
                ]
            ];

            // Add operation-specific metrics
            $summary = $this->metrics->getSummary();
            if (!empty($summary['metrics'])) {
                $response['operation_metrics'] = $this->formatOperationMetrics($summary['metrics']);
            }

            return $this->success($response);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/admin/metrics/live
     * 
     * Get real-time metrics (for streaming/polling)
     *
     * @return array JSON response
     */
    public function getMetricsLive(): array
    {
        try {
            $dashboard = $this->metrics->getDashboardData();

            return $this->success([
                'status' => $dashboard['status'] ?? 'healthy',
                'performance' => $dashboard['performance'] ?? [],
                'resources' => $dashboard['resources'] ?? [],
                'timestamp' => date('Y-m-d\TH:i:s\Z')
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/admin/dashboard
     * 
     * Get full dashboard data
     *
     * @return array JSON response
     */
    public function getDashboard(): array
    {
        try {
            $summary = $this->metrics->getSummary();
            $dashboard = $this->metrics->getDashboardData();

            return $this->success([
                'title' => 'OAuth2 Dashboard',
                'health_status' => $dashboard['status'] ?? 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'widgets' => [
                    'performance' => [
                        'title' => 'Performance Metrics',
                        'data' => $this->formatPerformanceWidget($dashboard, $summary)
                    ],
                    'cache' => [
                        'title' => 'Cache Hit Rates',
                        'data' => $this->formatCacheWidget($dashboard, $summary)
                    ],
                    'errors' => [
                        'title' => 'Error Summary',
                        'data' => $this->formatErrorWidget($summary)
                    ],
                    'resources' => [
                        'title' => 'Resource Usage',
                        'data' => $this->formatResourceWidget($dashboard, $summary)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Format operation metrics for response
     *
     * @param array $metrics Metrics data
     *
     * @return array Formatted metrics
     */
    private function formatOperationMetrics(array $metrics): array
    {
        $formatted = [];
        foreach ($metrics as $operation => $data) {
            if (is_array($data) && isset($data['avg'])) {
                $formatted[] = [
                    'operation' => $operation,
                    'avg_ms' => round($data['avg'], 2),
                    'min_ms' => round($data['min'] ?? 0, 2),
                    'max_ms' => round($data['max'] ?? 0, 2),
                    'count' => $data['count'] ?? 0
                ];
            }
        }
        return $formatted;
    }

    /**
     * Format performance widget
     *
     * @param array $dashboard Dashboard data
     * @param array $summary Summary data
     *
     * @return array Formatted data
     */
    private function formatPerformanceWidget(array $dashboard, array $summary): array
    {
        $perf = $dashboard['performance'] ?? [];
        return [
            'authorization_p99_ms' => $perf['authorization']['p99'] ?? 0,
            'token_p99_ms' => $perf['token_exchange']['p99'] ?? 0,
            'userinfo_p99_ms' => $perf['userinfo']['p99'] ?? 0,
            'status' => 'operational'
        ];
    }

    /**
     * Format cache widget
     *
     * @param array $dashboard Dashboard data
     * @param array $summary Summary data
     *
     * @return array Formatted data
     */
    private function formatCacheWidget(array $dashboard, array $summary): array
    {
        $cacheRates = $dashboard['top_metrics'] ?? [];
        return [
            'tokens_hit_rate' => $cacheRates['token_cache'] ?? 0,
            'authorization_hit_rate' => $cacheRates['auth_code_cache'] ?? 0,
            'consent_hit_rate' => $cacheRates['consent_cache'] ?? 0,
            'overall_hit_rate' => $cacheRates['overall'] ?? 0
        ];
    }

    /**
     * Format error widget
     *
     * @param array $summary Summary data
     *
     * @return array Formatted data
     */
    private function formatErrorWidget(array $summary): array
    {
        $errors = $summary['errors'] ?? [];
        return [
            'total_errors' => $errors['total_errors'] ?? 0,
            'error_rate_percent' => round(($errors['total_errors'] / max(1, $summary['total_requests'] ?? 1)) * 100, 2),
            'top_errors' => array_slice($errors['by_type'] ?? [], 0, 3)
        ];
    }

    /**
     * Format resource widget
     *
     * @param array $dashboard Dashboard data
     * @param array $summary Summary data
     *
     * @return array Formatted data
     */
    private function formatResourceWidget(array $dashboard, array $summary): array
    {
        $resources = $dashboard['resources'] ?? [];
        return [
            'memory_current_mb' => $resources['memory_current_mb'] ?? 0,
            'memory_peak_mb' => $resources['memory_peak_mb'] ?? 0,
            'active_connections' => $resources['active_connections'] ?? 0,
            'cached_items' => $resources['cached_items'] ?? 0
        ];
    }

    /**
     * Return success response
     *
     * @param array $data Response data
     *
     * @return array JSON response
     */
    private function success(array $data): array
    {
        return [
            'success' => true,
            'data' => $data
        ];
    }

    /**
     * Return error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     *
     * @return array JSON response
     */
    private function error(string $message, int $code = 400): array
    {
        return [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code
            ]
        ];
    }
}
