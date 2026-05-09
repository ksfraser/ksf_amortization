<?php

namespace Ksfraser\Amortizations\FA;

use Ksfraser\HTML\Elements\HtmlA;
use Ksfraser\HTML\Elements\HtmlDiv;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlAttribute;

/**
 * Amortization Module Navigation Menu Builder
 * 
 * Single Responsibility: Build the module's navigation menu with action links
 * Encapsulates menu structure and link generation using HTML builder classes
 * 
 * @package Ksfraser\Amortizations\FA
 */
class AmortizationMenuBuilder
{
    private $pathToRoot;
    private $controllerUrl;
    
    /**
     * Initialize menu builder with FA path
     * 
     * @param string $pathToRoot FrontAccounting root path (e.g., '/frontaccounting')
     */
    public function __construct($pathToRoot = '')
    {
        $this->pathToRoot = $pathToRoot;
        // Single master path to controller - use current script if available, otherwise construct path
        if (isset($_SERVER['SCRIPT_NAME'])) {
            // We're already at the controller, use current script
            $this->controllerUrl = $_SERVER['SCRIPT_NAME'];
        } else {
            // Fallback to constructed path
            $this->controllerUrl = $pathToRoot . '/modules/amortization/controller.php';
        }
    }
    
    /**
     * Build the module navigation menu with all action links
     * 
     * @return string HTML for navigation menu
     */
    public function build()
    {
        // Create container div with styling
        $nav = new HtmlDiv(new HtmlString(''));
        $nav->addAttribute(new HtmlAttribute('class', 'module-nav'));
        $nav->addAttribute(new HtmlAttribute('style', 'margin-bottom: 20px; text-align: right;'));
        
        // Add New Loan link
        $createLink = $this->createStyledLink('Add New Loan', '?action=create');
        $nav->addNested($createLink);
        
        // Spacing
        $nav->addNested(new HtmlString(' '));
        
        // Admin Settings link
        $adminLink = $this->createStyledLink('Admin Settings', '?action=admin');
        $nav->addNested($adminLink);
        
        // Spacing
        $nav->addNested(new HtmlString(' '));
        
        // Manage Selectors link
        $selectorsLink = $this->createStyledLink('Manage Selectors', '?action=admin_selectors');
        $nav->addNested($selectorsLink);
        
        // Spacing
        $nav->addNested(new HtmlString(' '));
        
        // Installation Status link
        $installLink = $this->createStyledLink('System Status', '?action=admin_install');
        $nav->addNested($installLink);
        
        // Spacing
        $nav->addNested(new HtmlString(' '));
        
        // Reports link
        $reportLink = $this->createStyledLink('Reports', '?action=report');
        $nav->addNested($reportLink);
        
        return $nav->getHtml();
    }
    
    /**
     * Create a styled action link using HTML builder
     * 
     * @param string $text Link display text
     * @param string $queryString Query string starting with ? (e.g., '?action=create')
     * @return HtmlA Styled link element
     */
    private function createStyledLink($text, $queryString)
    {
        $link = new HtmlA(new HtmlString(''));
        $link->addHref($this->controllerUrl . $queryString, $text);
        $link->addAttribute(new HtmlAttribute('class', 'button'));
        return $link;
    }
}
?>
