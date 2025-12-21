<?php

namespace Tests\Integration\Views;

use PHPUnit\Framework\TestCase;

/**
 * WordPressRefactoringViewsTest - Integration tests for WordPress selector views
 * 
 * Tests refactoring of:
 * - wp_loan_borrower_selector.php (AjaxSelectPopulator)
 * - wp_loan_term_selector.php (PaymentFrequencyHandler)
 * 
 * @package    Tests\Integration\Views
 * @since      20251221
 */
class WordPressRefactoringViewsTest extends TestCase
{
    private $borrowerViewPath;
    private $termViewPath;

    protected function setUp(): void
    {
        $this->borrowerViewPath = realpath(__DIR__ . '/../../../modules/amortization/views/wp_loan_borrower_selector.php');
        $this->termViewPath = realpath(__DIR__ . '/../../../modules/amortization/views/wp_loan_term_selector.php');
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

    public function testBorrowerViewUsesSelectBuilder()
    {
        if (!$this->borrowerViewPath) {
            $this->markTestSkipped('View not found');
        }
        $content = file_get_contents($this->borrowerViewPath);
        $this->assertStringContainsString('SelectBuilder', $content);
    }

    public function testTermViewUsesBuilders()
    {
        if (!$this->termViewPath) {
            $this->markTestSkipped('View not found');
        }
        $content = file_get_contents($this->termViewPath);
        $this->assertStringContainsString('SelectBuilder', $content);
        $this->assertStringContainsString('HtmlInput', $content);
    }

    public function testBorrowerViewCodeReduced()
    {
        if (!$this->borrowerViewPath) {
            $this->markTestSkipped('View not found');
        }
        $content = file_get_contents($this->borrowerViewPath);
        $lines = count(array_filter(explode("\n", $content)));
        $this->assertLessThan(50, $lines);
    }

    public function testTermViewCodeReduced()
    {
        if (!$this->termViewPath) {
            $this->markTestSkipped('View not found');
        }
        $content = file_get_contents($this->termViewPath);
        $lines = count(array_filter(explode("\n", $content)));
        $this->assertLessThan(60, $lines);
    }
}
