<?php
/**
 * GL Selector Helper
 * 
 * Responsible for building GL account select elements.
 * Provides reusable functionality for GL account selection across the application.
 * Follows SRP - single responsibility of GL select building.
 * 
 * @package Ksfraser\Amortizations\FrontAccounting
 */

namespace Ksfraser\Amortizations\FrontAccounting\Helpers;

use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\Label;

class GlSelectorHelper
{
    /**
     * Build a GL account select element
     * 
     * @param string $name - Name/ID attribute for the select
     * @param array $accounts - Array of account objects with 'account_code' and 'account_name'
     * @param string $selected - Currently selected account code
     * @param array $attributes - Additional attributes to set on select
     * @return Select
     */
    public static function buildGlSelect(
        $name,
        array $accounts,
        $selected = '',
        array $attributes = []
    ) {
        $select = (new Select())
            ->setName($name)
            ->setId($name);
        
        // Apply additional attributes
        foreach ($attributes as $key => $value) {
            $select->setAttribute($key, $value);
        }
        
        // Add options for each GL account
        foreach ($accounts as $account) {
            $option = (new Option())
                ->setValue($account['account_code'])
                ->setText($account['account_code'] . ' - ' . $account['account_name']);
            
            if ($account['account_code'] === $selected) {
                $option->setAttribute('selected', 'selected');
            }
            
            $select->append($option);
        }
        
        return $select;
    }
    
    /**
     * Build a complete GL selector form group (label + select)
     * 
     * @param string $name - Field name
     * @param string $label - Display label
     * @param array $accounts - Array of GL accounts
     * @param string $selected - Selected account code
     * @param string $helpText - Optional help text below field
     * @return Div
     */
    public static function buildGlFormGroup(
        $name,
        $label,
        array $accounts,
        $selected = '',
        $helpText = ''
    ) {
        $group = (new Div())->addClass('form-group');
        
        // Add label
        $labelElement = (new Label())
            ->setFor($name)
            ->setText($label);
        $group->append($labelElement);
        
        // Add select
        $select = self::buildGlSelect($name, $accounts, $selected);
        $group->append($select);
        
        // Add help text if provided
        if (!empty($helpText)) {
            $help = (new Div())
                ->addClass('help-text')
                ->setText($helpText);
            $group->append($help);
        }
        
        return $group;
    }
    
    /**
     * Format account display text
     * 
     * @param array $account - Account object with code and name
     * @return string - Formatted "CODE - Name" string
     */
    public static function formatAccountDisplay(array $account)
    {
        return trim($account['account_code']) . ' - ' . trim($account['account_name']);
    }
}
