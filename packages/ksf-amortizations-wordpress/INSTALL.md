# KSF Amortizations - WordPress Installation

## Prerequisites
- WordPress 5.0+
- PHP 7.4+
- MySQL/MariaDB
- Composer

## Installation Steps

### 1. Install Dependencies

In your WordPress plugin directory:

```bash
cd /path/to/wordpress/wp-content/plugins
mkdir amortizations && cd amortizations
composer require ksfraser/amortizations-core
composer require ksfraser/amortizations-wordpress
```

### 2. Deploy Plugin Files

Copy the plugin structure:

```bash
cp -r vendor/ksfraser/amortizations-wordpress/plugin/* .
```

### 3. Database Setup

The plugin will automatically create tables on first activation. If needed, manually run:

```bash
# Get WordPress database connection info
# Then run schema:
mysql -h localhost -u wp_user -p wordpress < vendor/ksfraser/amortizations-wordpress/plugin/schema.sql
```

### 4. Activate Plugin

1. Log into WordPress admin dashboard
2. Go to: **Plugins**
3. Find "Amortizations" in the list
4. Click **Activate**

The database tables will be created automatically on first activation.

### 5. Configure Plugin

#### Initial Setup

1. Go to: **Settings → Amortizations**
2. Configure:
   - Payment frequencies (monthly, weekly, bi-weekly, etc.)
   - Loan types (auto, mortgage, etc.)
   - Default interest calculation frequency
   - Payment terms

#### User Permissions

1. Go to: **Users → Roles and Capabilities** (requires "User Role Editor" or similar plugin)
2. Enable capability for appropriate roles:
   - `manage_amortizations` - Full access
   - `edit_loan_data` - Edit loans
   - `view_loan_data` - View loans

Or add to functions.php:

```php
// Grant capabilities to specific role
$role = get_role('administrator');
$role->add_cap('manage_amortizations');
$role->add_cap('edit_loan_data');
$role->add_cap('view_loan_data');
```

### 6. Verify Installation

Test functionality:

```bash
cd vendor/ksfraser/amortizations-wordpress
composer test
```

All tests should pass.

## Plugin Location

After installation:

```
/wordpress/wp-content/plugins/amortizations/
├── amortizations.php               # Main plugin file (header)
├── hooks.php                       # WordPress hooks
├── plugin-functions.php            # Plugin functions
├── schema.sql                      # Database schema
├── views/
│   ├── admin-settings.php          # Admin settings page
│   ├── user-loan-setup.php         # User loan setup form
│   ├── user-loan-list.php          # User loans list
│   └── ...
└── (other plugin files)

/wordpress/wp-content/plugins/amortizations/vendor/ksfraser/amortizations-wordpress/
├── src/
│   └── Ksfraser/Amortizations/WordPress/
│       ├── WPLoanEventProvider.php
│       └── (other WP-specific classes)
└── tests/
    └── WPDataProviderTest.php
```

## Database Tables

The plugin creates these tables (with WordPress prefix):

- `{prefix}ksf_amort_loans` - Loan records
- `{prefix}ksf_amort_schedules` - Payment schedules
- `{prefix}ksf_amort_events` - Loan events (payments, extra payments, etc.)
- `{prefix}ksf_amort_selectors` - Configuration options
- `{prefix}ksf_amort_staging` - Payment staging table

## Troubleshooting

### Plugin not appearing in list
- Verify `amortizations.php` exists in plugin directory
- Check file permissions (should be 644)
- Clear WordPress plugin cache if using a cache plugin

### Database errors on activation
- Verify WordPress database user has CREATE TABLE permission
- Check database connection in `wp-config.php`
- Look in `/wp-content/debug.log` for specific errors

### Shortcodes not displaying
- Verify plugin is activated
- Check shortcode spelling: `[amortizations_setup]`
- Verify user has required capabilities

### Permission errors
- Ensure user/role has `manage_amortizations` capability
- Check WordPress user role settings
- Verify capability assignment is persistent (not removed by plugin conflict)

## Using Shortcodes

### Loan Setup Form
```
[amortizations_setup]
```
Displays form for creating/editing loans for current user.

### Loan List
```
[amortizations_list]
```
Displays list of current user's loans.

### Loan Details
```
[amortizations_loan id="123"]
```
Displays details and payment schedule for specific loan.

## Deactivation/Uninstall

### Deactivate
- Go to **Plugins** in WordPress admin
- Click **Deactivate** next to Amortizations

Tables and data are preserved.

### Uninstall
1. Deactivate plugin
2. Delete plugin directory:
   ```bash
   rm -rf /wordpress/wp-content/plugins/amortizations/
   ```
3. Remove Composer packages (optional):
   ```bash
   composer remove ksfraser/amortizations-wordpress
   composer remove ksfraser/amortizations-core
   ```

To remove database tables, run:
```php
// Add to functions.php temporarily, then remove
global $wpdb;
$wpdb->query("DROP TABLE {$wpdb->prefix}ksf_amort_loans");
$wpdb->query("DROP TABLE {$wpdb->prefix}ksf_amort_schedules");
$wpdb->query("DROP TABLE {$wpdb->prefix}ksf_amort_events");
$wpdb->query("DROP TABLE {$wpdb->prefix}ksf_amort_selectors");
$wpdb->query("DROP TABLE {$wpdb->prefix}ksf_amort_staging");
```

## Support

For issues:
- Check WordPress debug log: `/wp-content/debug.log`
- Review plugin test output: `cd vendor/ksfraser/amortizations-wordpress && composer test`
- Verify database tables were created: `wp db tables | grep ksf_amort`

## Next Steps

1. Create pages with loan management shortcodes
2. Configure user roles and permissions
3. Set up email notifications for payments
4. Train users on loan creation and management

---

**Version:** 1.0.0  
**Last Updated:** December 9, 2025
