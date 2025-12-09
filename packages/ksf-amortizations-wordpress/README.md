# KSF Amortizations - WordPress Plugin

## Description

WordPress amortization plugin providing loan management functionality.

## Installation

### Prerequisites
- WordPress 5.0+
- PHP 7.4+
- Composer

### Installation Steps

1. **Install via Composer**
   ```bash
   cd /path/to/wordpress/wp-content/plugins
   mkdir amortizations && cd amortizations
   composer require ksfraser/amortizations-core
   composer require ksfraser/amortizations-wordpress
   ```

2. **Copy plugin files**
   ```bash
   cp -r vendor/ksfraser/amortizations-wordpress/plugin/* .
   ```

3. **Activate plugin**
   - Go to WordPress admin: Plugins
   - Find "Amortizations"
   - Click "Activate"

4. **Configure**
   - Go to: Settings → Amortizations
   - Configure payment frequencies, loan types, etc.

5. **Set user permissions**
   - Go to: Users → Roles and Capabilities
   - Enable "manage_amortizations" capability for appropriate roles

## Plugin Structure

```
plugin/
├── amortizations.php           # Main plugin file
├── hooks.php                   # WordPress hooks
├── views/                      # Plugin views
│   ├── admin-settings.php      # Admin settings page
│   ├── user-loan-setup.php     # User loan interface
│   └── ...
└── schema.sql                  # Database schema

src/Ksfraser/Amortizations/WordPress/
├── WPLoanEventProvider.php     # WordPress data provider
└── (other WP-specific classes)
```

## Features

- ✅ Amortization calculations
- ✅ Loan management interface
- ✅ Payment scheduling
- ✅ Extra payment handling
- ✅ Admin configuration
- ✅ User permissions integration
- ✅ Shortcodes for loan display

## Usage

### Display loan setup shortcode
```
[amortizations_setup]
```

### Display loan list shortcode
```
[amortizations_list]
```

### PHP API
```php
$plugin = new \Ksfraser\Amortizations\WordPress\AmortizationsPlugin();
$loans = $plugin->getUserLoans($userId);
$schedule = $plugin->calculateSchedule($loanId);
```

## Testing

```bash
composer test
```

## License

MIT
