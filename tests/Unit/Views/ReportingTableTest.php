<?php
namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Views\ReportingTable;

/**
 * ReportingTable Unit Tests
 * 
 * Tests for the reporting table view rendering, HTML structure,
 * security, CSS classes, and form attributes.
 */
class ReportingTableTest extends TestCase
{
    /**
     * Test rendering with empty array
     */
    public function testRenderWithEmptyArray(): void
    {
        $output = ReportingTable::render([]);
        $this->assertIsString($output);
        $this->assertStringContainsString('Reports', $output);
        $this->assertStringContainsString('<table', $output);
    }

    /**
     * Test rendering with single report item
     */
    public function testRenderWithSingleReport(): void
    {
        $report = (object)[
            'id' => 1,
            'type' => 'Amortization Schedule',
            'date' => '2025-12-20 10:30:00'
        ];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('Amortization Schedule', $output);
        $this->assertStringContainsString('2025-12-20', $output);
    }

    /**
     * Test rendering with multiple report items
     */
    public function testRenderWithMultipleReports(): void
    {
        $reports = [
            (object)['id' => 1, 'type' => 'Amortization Schedule', 'date' => '2025-12-20 10:30:00'],
            (object)['id' => 2, 'type' => 'Interest Summary', 'date' => '2025-12-19 14:15:00'],
            (object)['id' => 3, 'type' => 'Payment History', 'date' => '2025-12-18 09:45:00'],
        ];
        $output = ReportingTable::render($reports);
        
        $this->assertStringContainsString('Amortization Schedule', $output);
        $this->assertStringContainsString('Interest Summary', $output);
        $this->assertStringContainsString('Payment History', $output);
    }

    /**
     * Test HTML structure contains required elements
     */
    public function testHtmlStructureContainsRequiredElements(): void
    {
        $report = (object)['id' => 1, 'type' => 'Test', 'date' => '2025-12-20'];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('<h3', $output);
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('<thead>', $output);
        $this->assertStringContainsString('<tbody>', $output);
        $this->assertStringContainsString('ID', $output);
        $this->assertStringContainsString('Type', $output);
        $this->assertStringContainsString('Date', $output);
        $this->assertStringContainsString('Actions', $output);
    }

    /**
     * Test action buttons are included (View)
     */
    public function testActionButtonsAreIncluded(): void
    {
        $report = (object)['id' => 1, 'type' => 'Test', 'date' => '2025-12-20'];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('View', $output);
        $this->assertStringContainsString('btn-small', $output);
    }

    /**
     * Test download button is included when download_url is provided
     */
    public function testDownloadButtonIncludedWithDownloadUrl(): void
    {
        $report = (object)[
            'id' => 1,
            'type' => 'Test',
            'date' => '2025-12-20',
            'download_url' => '/downloads/report-1.pdf'
        ];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('Download', $output);
        $this->assertStringContainsString('btn-download', $output);
    }

    /**
     * Test download button is not included when download_url is not provided
     */
    public function testDownloadButtonOmittedWithoutDownloadUrl(): void
    {
        $report = (object)['id' => 1, 'type' => 'Test', 'date' => '2025-12-20'];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringNotContainsString('btn-download', $output);
    }

    /**
     * Test CSS links are included
     */
    public function testCssLinksAreIncluded(): void
    {
        $output = ReportingTable::render([]);
        
        if (function_exists('asset_url')) {
            $this->assertStringContainsString('reporting-table.css', $output);
            $this->assertStringContainsString('reporting-form.css', $output);
            $this->assertStringContainsString('reporting-buttons.css', $output);
        }
    }

    /**
     * Test JavaScript is included
     */
    public function testJavaScriptIsIncluded(): void
    {
        $output = ReportingTable::render([]);
        
        $this->assertStringContainsString('<script>', $output);
        $this->assertStringContainsString('viewReport', $output);
    }

    /**
     * Test HTML encoding of special characters in type
     */
    public function testHtmlEncodingOfSpecialCharactersInType(): void
    {
        $report = (object)[
            'id' => 1,
            'type' => '<script>alert("xss")</script>',
            'date' => '2025-12-20'
        ];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringNotContainsString('<script>alert', $output);
    }

    /**
     * Test HTML encoding of download_url attribute
     */
    public function testHtmlEncodingOfDownloadUrl(): void
    {
        $report = (object)[
            'id' => 1,
            'type' => 'Test',
            'date' => '2025-12-20',
            'download_url' => '" onclick="alert(1)" x="'
        ];
        $output = ReportingTable::render([$report]);
        
        // Should be encoded as attribute
        $this->assertStringContainsString('&quot;', $output);
        $this->assertStringNotContainsString('onclick="alert(1)"', $output);
    }

    /**
     * Test handling of missing properties with defaults
     */
    public function testHandlingOfMissingProperties(): void
    {
        $report = (object)[]; // No properties
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('N/A', $output);
        $this->assertIsString($output);
    }

    /**
     * Test date formatting for DateTime objects
     */
    public function testDateFormattingForDateTimeObjects(): void
    {
        $date = new \DateTime('2025-12-20 14:30:45');
        $report = (object)['id' => 1, 'type' => 'Test', 'date' => $date];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('2025-12-20', $output);
        $this->assertStringContainsString('14:30:45', $output);
    }

    /**
     * Test date formatting for string dates
     */
    public function testDateFormattingForStringDates(): void
    {
        $report = (object)['id' => 1, 'type' => 'Test', 'date' => '2025-12-20 14:30:45'];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('2025-12-20', $output);
        $this->assertStringContainsString('14:30:45', $output);
    }

    /**
     * Test table classes are applied
     */
    public function testTableClassesAreApplied(): void
    {
        $report = (object)['id' => 1, 'type' => 'Test', 'date' => '2025-12-20'];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('reporting-table', $output);
        $this->assertStringContainsString('id-cell', $output);
        $this->assertStringContainsString('type-cell', $output);
        $this->assertStringContainsString('date-cell', $output);
        $this->assertStringContainsString('actions-cell', $output);
    }

    /**
     * Test button onclick attributes contain proper handler calls
     */
    public function testButtonOnclickAttributesWithHandlerCalls(): void
    {
        $report = (object)['id' => 42, 'type' => 'Test', 'date' => '2025-12-20'];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('viewReport(42)', $output);
    }

    /**
     * Test download button onclick sets window.location correctly
     */
    public function testDownloadButtonSetsWindowLocation(): void
    {
        $report = (object)[
            'id' => 1,
            'type' => 'Test',
            'date' => '2025-12-20',
            'download_url' => '/downloads/report.pdf'
        ];
        $output = ReportingTable::render([$report]);
        
        $this->assertStringContainsString('window.location.href', $output);
        $this->assertStringContainsString('/downloads/report.pdf', $output);
    }
}
