<?php
namespace Tests\Unit\Api;

use Ksfraser\Amortizations\Api\LoanAnalysisRequest;
use Ksfraser\Amortizations\Api\PortfolioRequest;
use Ksfraser\Amortizations\Api\ReportRequest;
use Ksfraser\Amortizations\Api\OriginationRequest;
use Ksfraser\Amortizations\Api\MarketRequest;
use Ksfraser\Amortizations\Api\ApiResponse;
use Ksfraser\Amortizations\Api\LoanAnalysisResponse;
use Ksfraser\Amortizations\Api\PortfolioResponse;
use Ksfraser\Amortizations\Api\ReportResponse;
use Ksfraser\Amortizations\Api\OriginationResponse;
use Ksfraser\Amortizations\Api\MarketResponse;
use Ksfraser\Amortizations\Api\LoanAnalysisController;
use Ksfraser\Amortizations\Api\PortfolioController;
use Ksfraser\Amortizations\Api\ReportingController;
use Ksfraser\Amortizations\Api\OriginationController;
use Ksfraser\Amortizations\Api\MarketController;
use PHPUnit\Framework\TestCase;

class ApiRequestsTest extends TestCase {
    public function testLoanAnalysisRequestFromArray(): void {
        $data = [
            'principal' => 200000,
            'annual_rate' => 0.05,
            'months' => 360,
            'monthly_income' => 8000,
            'credit_score' => 750
        ];

        $request = LoanAnalysisRequest::fromArray($data);

        $this->assertEquals(200000, $request->principal);
        $this->assertEquals(0.05, $request->annualRate);
        $this->assertEquals(360, $request->months);
        $this->assertEquals(8000, $request->monthlyIncome);
        $this->assertEquals(750, $request->creditScore);
    }

    public function testLoanAnalysisRequestValidation(): void {
        $request = new LoanAnalysisRequest();
        $request->principal = -100;
        $request->annualRate = 0.05;
        $request->months = 360;
        $request->monthlyIncome = 8000;

        $errors = $request->validate();

        $this->assertGreaterThan(0, count($errors));
        $this->assertStringContainsString('principal', $errors[0]);
    }

    public function testPortfolioRequestValidation(): void {
        $request = new PortfolioRequest();
        $request->loanIds = [];

        $errors = $request->validate();

        $this->assertGreaterThan(0, count($errors));
    }

    public function testReportRequestFormatValidation(): void {
        $data = [
            'principal' => 200000,
            'annual_rate' => 0.05,
            'months' => 360,
            'format' => 'json'
        ];

        $request = ReportRequest::fromArray($data);
        $errors = $request->validate();

        $this->assertEmpty($errors);
        $this->assertEquals('json', $request->format);
    }

    public function testReportRequestInvalidFormatDefaults(): void {
        $data = [
            'principal' => 200000,
            'annual_rate' => 0.05,
            'months' => 360,
            'format' => 'invalid'
        ];

        $request = ReportRequest::fromArray($data);

        $this->assertEquals('json', $request->format);
    }

    public function testOriginationRequestValidation(): void {
        $data = [
            'applicant_name' => 'John Doe',
            'requested_amount' => 200000,
            'purpose' => 'Home Purchase',
            'principal' => 200000,
            'annual_rate' => 0.05,
            'months' => 360
        ];

        $request = OriginationRequest::fromArray($data);
        $errors = $request->validate();

        $this->assertEmpty($errors);
    }

    public function testMarketRequestValidation(): void {
        $data = [
            'current_rate' => 0.05,
            'margin' => 0.002,
            'competitor_rates' => [0.045, 0.050, 0.055]
        ];

        $request = MarketRequest::fromArray($data);
        $errors = $request->validate();

        $this->assertEmpty($errors);
    }
}

class ApiResponsesTest extends TestCase {
    public function testApiResponseSuccess(): void {
        $data = ['key' => 'value'];
        $response = ApiResponse::success($data, 'Success');

        $this->assertTrue($response->success);
        $this->assertEquals('Success', $response->message);
        $this->assertEquals($data, $response->data);
        $this->assertEquals(200, $response->statusCode);
    }

    public function testApiResponseError(): void {
        $response = ApiResponse::error('Error occurred', ['error1'], 400);

        $this->assertFalse($response->success);
        $this->assertEquals('Error occurred', $response->message);
        $this->assertEquals(['error1'], $response->errors);
        $this->assertEquals(400, $response->statusCode);
    }

    public function testApiResponseValidationError(): void {
        $errors = ['field1' => 'error', 'field2' => 'error'];
        $response = ApiResponse::validationError($errors);

        $this->assertFalse($response->success);
        $this->assertEquals('Validation failed', $response->message);
        $this->assertEquals($errors, $response->errors);
        $this->assertEquals(422, $response->statusCode);
    }

    public function testApiResponseNotFound(): void {
        $response = ApiResponse::notFound('Resource not found');

        $this->assertFalse($response->success);
        $this->assertEquals(404, $response->statusCode);
    }

    public function testApiResponseUnauthorized(): void {
        $response = ApiResponse::unauthorized();

        $this->assertFalse($response->success);
        $this->assertEquals(401, $response->statusCode);
    }

    public function testApiResponseTooManyRequests(): void {
        $response = ApiResponse::tooManyRequests();

        $this->assertFalse($response->success);
        $this->assertEquals(429, $response->statusCode);
    }

    public function testApiResponseToArray(): void {
        $response = ApiResponse::success(['key' => 'value']);
        $array = $response->toArray();

        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('timestamp', $array);
    }

    public function testApiResponseToJson(): void {
        $response = ApiResponse::success(['key' => 'value']);
        $json = $response->toJson();

        $this->assertStringContainsString('"success"', $json);
        $this->assertStringContainsString('true', $json);
    }

    public function testLoanAnalysisResponseToArray(): void {
        $response = LoanAnalysisResponse::create(
            true,
            'qualified',
            0.80,
            0.43,
            ['score' => 850],
            ['risk_level' => 'low'],
            250000,
            true
        );

        $array = $response->toArray();

        $this->assertTrue($array['qualified']);
        $this->assertEquals('qualified', $array['recommendation']);
        $this->assertEquals(0.80, $array['loan_to_value']);
    }

    public function testPortfolioResponseToArray(): void {
        $portfolio = ['loans' => 5];
        $riskProfile = ['level' => 'medium'];
        
        $response = PortfolioResponse::create($portfolio, $riskProfile, 0.055, ['ratio' => 0.042]);
        $array = $response->toArray();

        $this->assertEquals($portfolio, $array['portfolio']);
        $this->assertEquals($riskProfile, $array['risk_profile']);
        $this->assertEquals(0.055, $array['yield']);
    }

    public function testReportResponseToArray(): void {
        $content = ['data' => 'report'];
        $response = ReportResponse::create('json', $content, ['generated' => true]);
        $array = $response->toArray();

        $this->assertEquals('json', $array['format']);
        $this->assertEquals($content, $array['data']);
    }

    public function testOriginationResponseToArray(): void {
        $response = OriginationResponse::create(
            'APP-12345',
            'approved',
            200000,
            0.05,
            ['offer' => 'details'],
            ['doc1', 'doc2']
        );

        $array = $response->toArray();

        $this->assertEquals('APP-12345', $array['application_id']);
        $this->assertEquals('approved', $array['status']);
        $this->assertEquals(200000, $array['approved_amount']);
    }

    public function testMarketResponseToArray(): void {
        $response = MarketResponse::create(
            ['mortgage' => 0.067],
            ['rank' => 3],
            ['forecast' => 'increasing'],
            ['action' => 'lock']
        );

        $array = $response->toArray();

        $this->assertArrayHasKey('market_rates', $array);
        $this->assertArrayHasKey('comparison', $array);
        $this->assertArrayHasKey('forecast', $array);
        $this->assertArrayHasKey('recommendations', $array);
    }
}

class ApiControllersTest extends TestCase {
    public function testLoanAnalysisControllerAnalyze(): void {
        $controller = new LoanAnalysisController();
        
        $request = [
            'principal' => 200000,
            'annual_rate' => 0.05,
            'months' => 360,
            'monthly_income' => 8000,
            'credit_score' => 750
        ];

        $response = $controller->analyze($request);

        $this->assertTrue($response->success);
        $this->assertEquals(200, $response->statusCode);
        $this->assertIsArray($response->data);
    }

    public function testLoanAnalysisControllerValidationError(): void {
        $controller = new LoanAnalysisController();
        
        $request = [
            'principal' => -100,
            'annual_rate' => 0.05,
            'months' => 360,
            'monthly_income' => 8000
        ];

        $response = $controller->analyze($request);

        $this->assertFalse($response->success);
        $this->assertEquals(422, $response->statusCode);
    }

    public function testLoanAnalysisControllerGetRates(): void {
        $controller = new LoanAnalysisController();
        $response = $controller->getRates();

        $this->assertTrue($response->success);
        $this->assertArrayHasKey('prime_rate', $response->data);
        $this->assertArrayHasKey('average_mortgage_30', $response->data);
    }

    public function testLoanAnalysisControllerCompare(): void {
        $controller = new LoanAnalysisController();
        
        $request = [
            'loans' => [
                ['principal' => 200000, 'annual_rate' => 0.05, 'months' => 360],
                ['principal' => 150000, 'annual_rate' => 0.06, 'months' => 300]
            ],
            'monthly_income' => 8000,
            'credit_score' => 750
        ];

        $response = $controller->compare($request);

        $this->assertTrue($response->success);
        $this->assertArrayHasKey('comparisons', $response->data);
    }

    public function testPortfolioControllerAnalyze(): void {
        $controller = new PortfolioController();
        
        $request = [
            'loan_ids' => [1, 2, 3],
            'name' => 'Test Portfolio'
        ];

        $response = $controller->analyze($request);

        $this->assertTrue($response->success);
        $this->assertArrayHasKey('portfolio', $response->data);
    }

    public function testPortfolioControllerRetrieve(): void {
        $controller = new PortfolioController();
        $response = $controller->retrieve('PORTFOLIO-123');

        $this->assertTrue($response->success);
        $this->assertArrayHasKey('portfolio_id', $response->data);
    }

    public function testPortfolioControllerGetYield(): void {
        $controller = new PortfolioController();
        $response = $controller->getYield('PORTFOLIO-123');

        $this->assertTrue($response->success);
        $this->assertArrayHasKey('total_yield', $response->data);
    }

    public function testReportingControllerGenerate(): void {
        $controller = new ReportingController();
        
        $request = [
            'principal' => 200000,
            'annual_rate' => 0.05,
            'months' => 360,
            'format' => 'json'
        ];

        $response = $controller->generate($request);

        $this->assertTrue($response->success);
        $this->assertArrayHasKey('format', $response->data);
    }

    public function testReportingControllerGenerateCsv(): void {
        $controller = new ReportingController();
        
        $request = [
            'principal' => 200000,
            'annual_rate' => 0.05,
            'months' => 360,
            'format' => 'csv'
        ];

        $response = $controller->generate($request);

        $this->assertTrue($response->success);
        $this->assertEquals('csv', $response->data['format']);
        $this->assertStringContainsString('Principal', $response->data['content']);
    }

    public function testReportingControllerExport(): void {
        $controller = new ReportingController();
        $response = $controller->export(['format' => 'pdf']);

        $this->assertTrue($response->success);
    }

    public function testOriginationControllerCreateApplication(): void {
        $controller = new OriginationController();
        
        $request = [
            'applicant_name' => 'John Doe',
            'requested_amount' => 200000,
            'purpose' => 'Home Purchase',
            'principal' => 200000,
            'annual_rate' => 0.05,
            'months' => 360
        ];

        $response = $controller->createApplication($request);

        $this->assertTrue($response->success);
        $this->assertEquals(201, $response->statusCode);
        $this->assertArrayHasKey('application_id', $response->data);
        $this->assertEquals('pending_review', $response->data['status']);
    }

    public function testOriginationControllerApprove(): void {
        $controller = new OriginationController();
        
        $response = $controller->approve('APP-12345', [
            'approved_amount' => 200000,
            'approved_rate' => 0.05
        ]);

        $this->assertTrue($response->success);
        $this->assertEquals('approved', $response->data['status']);
    }

    public function testOriginationControllerReject(): void {
        $controller = new OriginationController();
        $response = $controller->reject('APP-12345', ['reason' => 'Low credit score']);

        $this->assertTrue($response->success);
        $this->assertEquals('rejected', $response->data['status']);
    }

    public function testMarketControllerGetRates(): void {
        $controller = new MarketController();
        $response = $controller->getRates();

        $this->assertTrue($response->success);
        $this->assertArrayHasKey('mortgage_30_year', $response->data);
    }

    public function testMarketControllerForecast(): void {
        $controller = new MarketController();
        
        $request = [
            'current_rate' => 0.05,
            'margin' => 0.002,
            'competitor_rates' => [0.045, 0.050, 0.055]
        ];

        $response = $controller->forecast($request);

        $this->assertTrue($response->success);
        $this->assertArrayHasKey('forecast', $response->data);
    }

    public function testMarketControllerCompareRates(): void {
        $controller = new MarketController();
        
        $request = [
            'current_rate' => 0.05,
            'competitor_rates' => [0.045, 0.055]
        ];

        $response = $controller->compareRates($request);

        $this->assertTrue($response->success);
        $this->assertArrayHasKey('competitiveness_rank', $response->data);
    }
}
