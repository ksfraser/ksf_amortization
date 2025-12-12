<?php
namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Services\MarketAnalysisService;
use PHPUnit\Framework\TestCase;

class MarketAnalysisServiceTest extends TestCase {
    private MarketAnalysisService $service;

    protected function setUp(): void {
        $this->service = new MarketAnalysisService();
    }

    public function testGetMarketRates(): void {
        $rates = $this->service->getMarketRates();

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('mortgage_30_year', $rates);
        $this->assertGreaterThan(0, $rates['mortgage_30_year']);
    }

    public function testCompareToMarketAverage(): void {
        $comparison = $this->service->compareToMarketAverage(0.05, 'mortgage_30_year');

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('loan_rate', $comparison);
        $this->assertArrayHasKey('market_rate', $comparison);
        $this->assertArrayHasKey('difference_bps', $comparison);
        $this->assertArrayHasKey('competitive', $comparison);
    }

    public function testRankRateCompetitiveness(): void {
        $competitors = [0.045, 0.055, 0.065, 0.075];
        $ranking = $this->service->rankRateCompetitiveness(0.05, $competitors);

        $this->assertIsArray($ranking);
        $this->assertArrayHasKey('rank', $ranking);
        $this->assertArrayHasKey('percentile', $ranking);
        $this->assertArrayHasKey('competitive_position', $ranking);
        $this->assertGreaterThan(0, $ranking['rank']);
    }

    public function testAnalyzeTrendDirection(): void {
        $historicalRates = [0.04, 0.042, 0.045, 0.048, 0.05];
        $trend = $this->service->analyzeTrendDirection($historicalRates);

        $this->assertIsArray($trend);
        $this->assertArrayHasKey('trend', $trend);
        $this->assertArrayHasKey('volatility', $trend);
    }

    public function testForecastRateMovement(): void {
        $historicalRates = [0.04, 0.042, 0.045, 0.048, 0.05];
        $forecast = $this->service->forecastRateMovement($historicalRates, 3);

        $this->assertIsArray($forecast);
        $this->assertArrayHasKey('forecast', $forecast);
        $this->assertArrayHasKey('confidence', $forecast);
        $this->assertGreaterThan(0, count($forecast['forecast']));
    }

    public function testIdentifyArbitrage(): void {
        $borrowRates = ['auto' => 0.03, 'mortgage' => 0.04];
        $lendRates = ['auto' => 0.06, 'mortgage' => 0.075];

        $arb = $this->service->identifyArbitrage($borrowRates, $lendRates);

        $this->assertIsArray($arb);
        $this->assertArrayHasKey('arbitrage_opportunities', $arb);
    }

    public function testSuggestRateOptimization(): void {
        $competitors = [0.045, 0.055, 0.065];
        $suggestion = $this->service->suggestRateOptimization(0.07, 0.055, $competitors);

        $this->assertIsArray($suggestion);
        $this->assertArrayHasKey('recommendation', $suggestion);
        $this->assertArrayHasKey('suggested_rate', $suggestion);
        $this->assertContains($suggestion['recommendation'], ['reduce_rate', 'increase_rate', 'maintain_rate']);
    }

    public function testCalculateMarketShare(): void {
        $share = $this->service->calculateMarketShare(50000000, 1000000000);

        $this->assertIsArray($share);
        $this->assertArrayHasKey('market_share_percent', $share);
        $this->assertArrayHasKey('market_share_rank', $share);
        $this->assertEquals(5, $share['market_share_percent']);
    }

    public function testAnalyzeLenderComparison(): void {
        $lenders = [
            ['name' => 'Bank A', 'rate' => 0.045],
            ['name' => 'Bank B', 'rate' => 0.055],
            ['name' => 'Bank C', 'rate' => 0.065]
        ];

        $analysis = $this->service->analyzeLenderComparison($lenders);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('total_lenders', $analysis);
        $this->assertArrayHasKey('average_rate', $analysis);
        $this->assertEquals(3, $analysis['total_lenders']);
    }

    public function testIdentifyMarketOpportunities(): void {
        $demographics = ['age_group' => 'young_professionals', 'credit_score' => 780];
        $opportunities = $this->service->identifyMarketOpportunities(0.07, $demographics);

        $this->assertIsArray($opportunities);
        $this->assertArrayHasKey('opportunities', $opportunities);
    }

    public function testGenerateMarketReport(): void {
        $historicalRates = [0.04, 0.042, 0.045];
        $competitors = [
            ['name' => 'Bank A', 'rate' => 0.045],
            ['name' => 'Bank B', 'rate' => 0.055]
        ];

        $report = $this->service->generateMarketReport(0.05, $historicalRates, $competitors);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('report_date', $report);
        $this->assertArrayHasKey('our_current_rate', $report);
        $this->assertArrayHasKey('market_trend', $report);
    }

    public function testCreateRateForecast(): void {
        $historicalRates = [0.04, 0.042, 0.045, 0.048];
        $forecast = $this->service->createRateForecast($historicalRates, 6);

        $this->assertIsArray($forecast);
        $this->assertArrayHasKey('forecast_period_months', $forecast);
        $this->assertArrayHasKey('forecast_data', $forecast);
        $this->assertEquals(6, $forecast['forecast_period_months']);
    }

    public function testOptimizeRateStrategy(): void {
        $marketRates = [0.04, 0.045, 0.05, 0.055];
        $optimization = $this->service->optimizeRateStrategy(0.06, 0.002, $marketRates);

        $this->assertIsArray($optimization);
        $this->assertArrayHasKey('current_rate', $optimization);
        $this->assertArrayHasKey('optimal_rate', $optimization);
        $this->assertArrayHasKey('adjustment_needed', $optimization);
    }

    public function testExportMarketAnalysis(): void {
        $analysis = ['rate' => 0.05, 'trend' => 'increasing'];
        $json = $this->service->exportMarketAnalysis($analysis);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }
}
