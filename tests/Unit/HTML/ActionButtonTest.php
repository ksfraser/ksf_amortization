<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\EditButton;
use Ksfraser\HTML\Elements\DeleteButton;
use Ksfraser\HTML\Elements\AddButton;
use Ksfraser\HTML\Elements\CancelButton;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * ActionButtonTest - Unit Tests for Specialized Action Button Classes
 * 
 * Tests all action button classes: EditButton, DeleteButton, AddButton, CancelButton
 * 
 * @package    Tests\Unit\HTML
 * @author     Test Suite
 * @since      20251220
 * @version    1.0.0
 */
class ActionButtonTest extends TestCase
{
    /**
     * Test EditButton creation
     */
    public function testEditButtonCreation()
    {
        $button = new EditButton(
            new HtmlString('Edit'),
            '123',
            'editItem(123)'
        );
        
        $this->assertNotNull($button);
        $this->assertIsObject($button);
    }

    /**
     * Test EditButton HTML output
     */
    public function testEditButtonOutput()
    {
        $button = new EditButton(
            new HtmlString('Edit'),
            '456',
            'handleEdit(456)'
        );
        
        $html = $button->getHtml();
        
        $this->assertStringContainsString('edit_btn_456', $html);
        $this->assertStringContainsString('btn btn-primary', $html);
        $this->assertStringContainsString('Edit', $html);
    }

    /**
     * Test DeleteButton creation
     */
    public function testDeleteButtonCreation()
    {
        $button = new DeleteButton(
            new HtmlString('Delete'),
            '789'
        );
        
        $this->assertNotNull($button);
    }

    /**
     * Test DeleteButton confirmation
     */
    public function testDeleteButtonConfirmation()
    {
        $button = new DeleteButton(
            new HtmlString('Delete'),
            '789'
        );
        
        $html = $button->getHtml();
        
        $this->assertStringContainsString('confirm', $html);
        $this->assertStringContainsString('delete_btn_789', $html);
        $this->assertStringContainsString('btn btn-danger', $html);
    }

    /**
     * Test DeleteButton custom confirmation message
     */
    public function testDeleteButtonCustomConfirmation()
    {
        $button = new DeleteButton(
            new HtmlString('Delete'),
            '999'
        );
        
        $button->setConfirmation('Really remove this?');
        $html = $button->getHtml();
        
        $this->assertStringContainsString('Really remove this?', $html);
    }

    /**
     * Test DeleteButton no confirmation
     */
    public function testDeleteButtonNoConfirmation()
    {
        $button = new DeleteButton(
            new HtmlString('Delete'),
            '111'
        );
        
        $button->noConfirmation();
        $html = $button->getHtml();
        
        // Should still be valid HTML
        $this->assertStringContainsString('delete_btn_111', $html);
    }

    /**
     * Test AddButton creation
     */
    public function testAddButtonCreation()
    {
        $button = new AddButton(new HtmlString('Add New'));
        
        $this->assertNotNull($button);
    }

    /**
     * Test AddButton styling
     */
    public function testAddButtonStyling()
    {
        $button = new AddButton(new HtmlString('Add New'));
        $html = $button->getHtml();
        
        $this->assertStringContainsString('add_btn', $html);
        $this->assertStringContainsString('btn btn-success', $html);
    }

    /**
     * Test CancelButton creation
     */
    public function testCancelButtonCreation()
    {
        $button = new CancelButton();
        
        $this->assertNotNull($button);
    }

    /**
     * Test CancelButton styling
     */
    public function testCancelButtonStyling()
    {
        $button = new CancelButton();
        $html = $button->getHtml();
        
        $this->assertStringContainsString('cancel_btn', $html);
        $this->assertStringContainsString('btn btn-secondary', $html);
    }

    /**
     * Test CancelButton go back functionality
     */
    public function testCancelButtonGoBack()
    {
        $button = new CancelButton();
        $button->setGoBack();
        $html = $button->getHtml();
        
        $this->assertStringContainsString('history.back', $html);
    }

    /**
     * Test EditButton onclick handler
     */
    public function testEditButtonOnclickHandler()
    {
        $button = new EditButton(
            new HtmlString('Edit'),
            '222',
            'alert("Editing 222")'
        );
        
        $button->setOnclickFunction('openDialog(222)');
        $html = $button->getHtml();
        
        $this->assertStringContainsString('openDialog(222)', $html);
    }

    /**
     * Test button CSS class customization
     */
    public function testEditButtonCustomCss()
    {
        $button = new EditButton(
            new HtmlString('Edit'),
            '333',
            'edit(333)'
        );
        
        $button->setCssClass('btn btn-info btn-lg');
        $html = $button->getHtml();
        
        $this->assertStringContainsString('btn btn-info', $html);
    }
}
