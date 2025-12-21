<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\SelectEditJSHandler;
use Ksfraser\HTML\Elements\AjaxSelectPopulator;
use Ksfraser\HTML\Elements\PaymentFrequencyHandler;

/**
 * JSHandlerTest - Unit Tests for JavaScript Handler Classes
 * 
 * Tests SelectEditJSHandler, AjaxSelectPopulator, and PaymentFrequencyHandler
 * 
 * @package    Tests\Unit\HTML
 * @author     Test Suite
 * @since      20251220
 * @version    1.0.0
 */
class JSHandlerTest extends TestCase
{
    /**
     * Test SelectEditJSHandler creation
     */
    public function testSelectEditJSHandlerCreation()
    {
        $handler = new SelectEditJSHandler();
        
        $this->assertNotNull($handler);
    }

    /**
     * Test SelectEditJSHandler HTML output contains script tag
     */
    public function testSelectEditJSHandlerOutput()
    {
        $handler = new SelectEditJSHandler();
        $html = $handler->getHtml();
        
        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('</script>', $html);
        $this->assertStringContainsString('function editOption', $html);
    }

    /**
     * Test SelectEditJSHandler custom function name
     */
    public function testSelectEditJSHandlerCustomFunction()
    {
        $handler = new SelectEditJSHandler();
        $handler->setFunctionName('customEditFunction');
        $html = $handler->getHtml();
        
        $this->assertStringContainsString('function customEditFunction', $html);
    }

    /**
     * Test SelectEditJSHandler field configuration
     */
    public function testSelectEditJSHandlerFieldConfig()
    {
        $handler = new SelectEditJSHandler();
        $handler->setSourceFieldId('my_id_field');
        $handler->setTargetFieldId('my_value_field');
        $html = $handler->getHtml();
        
        $this->assertStringContainsString('my_id_field', $html);
        $this->assertStringContainsString('my_value_field', $html);
    }

    /**
     * Test AjaxSelectPopulator creation
     */
    public function testAjaxSelectPopulatorCreation()
    {
        $populator = new AjaxSelectPopulator();
        
        $this->assertNotNull($populator);
    }

    /**
     * Test AjaxSelectPopulator HTML output
     */
    public function testAjaxSelectPopulatorOutput()
    {
        $populator = new AjaxSelectPopulator();
        $populator->setFunctionName('fetchBorrowers');
        $populator->setSourceFieldId('type_select');
        $populator->setTargetFieldId('borrower_select');
        $populator->setEndpoint('borrower_ajax.php');
        
        $html = $populator->getHtml();
        
        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('function fetchBorrowers', $html);
        $this->assertStringContainsString('borrower_ajax.php', $html);
        $this->assertStringContainsString('type_select', $html);
    }

    /**
     * Test AjaxSelectPopulator with loading state
     */
    public function testAjaxSelectPopulatorLoadingState()
    {
        $populator = new AjaxSelectPopulator();
        $populator->setFunctionName('loadData');
        $populator->setShowLoadingState(true);
        
        $html = $populator->getHtml();
        
        $this->assertStringContainsString('Loading...', $html);
    }

    /**
     * Test PaymentFrequencyHandler creation
     */
    public function testPaymentFrequencyHandlerCreation()
    {
        $handler = new PaymentFrequencyHandler();
        
        $this->assertNotNull($handler);
    }

    /**
     * Test PaymentFrequencyHandler HTML output
     */
    public function testPaymentFrequencyHandlerOutput()
    {
        $handler = new PaymentFrequencyHandler();
        $html = $handler->getHtml();
        
        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('function updatePaymentsPerYear', $html);
        $this->assertStringContainsString('switch', $html);
    }

    /**
     * Test PaymentFrequencyHandler frequency mapping
     */
    public function testPaymentFrequencyHandlerFrequencies()
    {
        $handler = new PaymentFrequencyHandler();
        $html = $handler->getHtml();
        
        // Check all standard frequencies are in the output
        $this->assertStringContainsString("'annual'", $html);
        $this->assertStringContainsString("'monthly'", $html);
        $this->assertStringContainsString("'weekly'", $html);
        $this->assertStringContainsString("val = 1", $html); // annual = 1
        $this->assertStringContainsString("val = 12", $html); // monthly = 12
        $this->assertStringContainsString("val = 52", $html); // weekly = 52
    }

    /**
     * Test PaymentFrequencyHandler custom frequency
     */
    public function testPaymentFrequencyHandlerCustomFrequency()
    {
        $handler = new PaymentFrequencyHandler();
        $handler->addFrequency('daily', 365);
        $html = $handler->getHtml();
        
        $this->assertStringContainsString("'daily'", $html);
        $this->assertStringContainsString("val = 365", $html);
    }

    /**
     * Test PaymentFrequencyHandler field IDs
     */
    public function testPaymentFrequencyHandlerFieldIds()
    {
        $handler = new PaymentFrequencyHandler();
        $handler->setSourceFieldId('freq_select');
        $handler->setTargetFieldId('ppay_hidden');
        
        $html = $handler->getHtml();
        
        $this->assertStringContainsString('freq_select', $html);
        $this->assertStringContainsString('ppay_hidden', $html);
    }

    /**
     * Test fluent interface on handlers
     */
    public function testFluentInterface()
    {
        $handler = (new AjaxSelectPopulator())
            ->setFunctionName('test')
            ->setSourceFieldId('src')
            ->setTargetFieldId('tgt')
            ->setEndpoint('test.php');
        
        $this->assertNotNull($handler);
    }
}
