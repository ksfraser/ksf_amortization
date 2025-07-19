<?php
namespace Ksfraser\Amortizations\WordPress;

// Include WordPress mock definitions for linting/testing outside WP
require_once __DIR__ . '/wp_mock.php';

/**
 * Handles creation of custom tables for amortization in WordPress.
 */
class WPAmortizationTables
{
    /**
     * Create required tables for amortization module.
     */
    public static function createTables($wpdb)
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$wpdb->prefix}amortization_loans (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            loan_type varchar(50) NOT NULL,
            principal decimal(15,2) NOT NULL,
            interest_rate decimal(5,2) NOT NULL,
            term int NOT NULL,
            schedule varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($sql);
    }
}
