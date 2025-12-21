<?php

namespace Tests\Integration\Views;

use PHPUnit\Framework\TestCase;

/**
 * SuiteCRMRefactoringViewsTest - Integration tests for SuiteCRM selector views
 * 
 * Tests refactoring of:
 * - suitecrm_loan_borrower_selector.php (AjaxSelectPopulator)
 * - suitecrm_loan_term_selector.php (PaymentFrequencyHandler)
 * 
 * @package    Tests\Integration\Views
 * @since      20251221
 */
class SuiteCRMRefactoringViewsTest extends TestCase
{
    private $borrowerViewPath;
    private $termViewPath;

    protected function setUp(): void
    {
        $this->borrowerViewPath = realpath(__DIR__ . '/../../../modules/amortization/views/suitecrm_loan_borrower_selector.php');
        $this->termViewPath = realpath(__DIR__ . '/../../../modules/amortization/views/suitecrm_loan_term_selector.php');
    }

    public function testBorrowerViewReadable()
    {
        if (!$this->borrowerViewPath) {
            $this->markTestSkipped('View not found');
        }
        $this->assertFileIsReadable($this->borrowerViewPath);
    }

    public function testTermViewReadable()
    {
        if (!$this->termViewPath) {
            $this->markTestSkipped('View not found');
        }
        $this->assertFileIsReadable($this->termViewPath);
    }

    public function testBorrowerViewUsesAjaxSelectPopulator()
    {
        if (!$this->borrowerViewPath) {
            $this->markTestSkipped('View not found');
        }
        $content = file_get_contents($this->borrowerViewPath);
        $this->assertStringContainsString('AjaxSelectPopulator', $content);
    }

    public function testTermViewUsesPaymentFrequencyHandler()
    {
        if (!$this->termViewPath) {
            $this->markTestSkipped('View not found');
        }
        $content = file_get_contents($this->termViewPath);
        $this->assertStringContainsString('PaymentFrequencyHandler', $content);
    }

    public function testBorrowerViewHasValidSyntax()
    {
        if (!$this->borrowerViewPath) {
            $this->markTestSkipped('View not found');
        }
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($this->borrowerViewPath), $output, $returnCode);
        $this->assertEquals(0, $returnCode, implode("\n", $output));
    }

    public function testTermViewHasValidSyntax()
    {
        if (!$this->termViewPath) {
            $this->markTestSkipped('View not found');
        }
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($this->termViewPath), $output, $returnCode);
        $this->assertEquals(0, $returnCode, implode("\n", $output));
    }

    public function testBorrowerViewNoHardcodedAjax()
    {
        if (!$this->borrowerViewPath) {
            $this->markTestSkipped('View not found');
        }
        $content = file_get_contents($this->borrowerViewPath);
        $this->assertStringNotContainsString('$.ajax', $content);
        $this->assertStringNotContainsString('fetch(', $content);
    }

    public function testTermViewNoHardcodedJavaScript()
    {
        if (!$this->termViewPath) {
            $this->markTestSkipped('View not found');
        }
        $content = file_get_contents($this->termViewPath);
        $this->assertStringNotContainsString('updatePaymentsPerYear', $content);
        $this->assertStringNotContainsString('switch (freq)', $content);
    }
}
