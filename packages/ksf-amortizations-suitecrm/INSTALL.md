# KSF Amortizations - SuiteCRM Installation

## Prerequisites
- SuiteCRM 7.10+
- PHP 7.4+
- MySQL/MariaDB
- Composer

## Installation Steps

### 1. Install Dependencies

In your SuiteCRM custom modules directory:

```bash
cd /path/to/suitecrm/custom/modules
mkdir Amortizations && cd Amortizations
composer require ksfraser/amortizations-core
composer require ksfraser/amortizations-suitecrm
```

### 2. Deploy Module Files

Copy the module structure:

```bash
cp -r vendor/ksfraser/amortizations-suitecrm/module/* .
```

### 3. Database Setup

Run the schema creation:

```bash
# Via SuiteCRM CLI (recommended)
php sugar_cli.php SchemaManager:rebuild

# Or manually
mysql -h localhost -u suite_user -p suitecrmdb < vendor/ksfraser/amortizations-suitecrm/module/schema.sql
```

### 4. Repair SuiteCRM

1. Log into SuiteCRM as administrator
2. Go to: **Admin → System Settings → Repair**
3. Click **Quick Repair & Rebuild**

This will register the new module and update the system.

### 5. Configure Module

#### Initial Setup

1. Go to: **Admin → System Settings → Amortizations**
2. Configure:
   - Loan types
   - Payment frequencies
   - Interest calculation methods
   - Default payment terms

#### Link to Accounts/Leads

1. Go to: **Admin → Studio → Accounts** (or Leads)
2. Add "Related Amortizations" relationship
3. Configure relationship mapping

#### Email Notifications

1. Go to: **Admin → Email Settings → Amortizations**
2. Configure:
   - Payment due notifications
   - Payment confirmation templates
   - Recipient addresses

### 6. Verify Installation

Test functionality:

```bash
cd vendor/ksfraser/amortizations-suitecrm
composer test
```

All tests should pass.

## Module Location

After installation:

```
/suitecrm/custom/modules/Amortizations/
├── manifest.php                # Module manifest
├── hooks.php                   # SuiteCRM hooks
├── module_name_map.php        # Module configuration
├── Views/
│   ├── view.module.php        # Default module view
│   ├── view.setup.php         # Configuration view
│   └── ...
├── Ext/
│   └── (SuiteCRM extensions)
└── (other module files)

/suitecrm/custom/modules/Amortizations/vendor/
├── ksfraser/amortizations-suitecrm/
│   ├── src/
│   │   └── Ksfraser/Amortizations/SuiteCRM/
│   │       ├── SuiteCRMLoanEventProvider.php
│   │       └── (other SuiteCRM-specific classes)
│   └── tests/
│       └── SuiteCRMDataProviderTest.php
```

## Database Tables

The module creates these tables:

- `ksf_amort_loans` - Loan records
- `ksf_amort_schedules` - Payment schedules
- `ksf_amort_events` - Loan events
- `ksf_amort_selectors` - Configuration options
- `ksf_amort_staging` - Payment staging table

## Troubleshooting

### Module not appearing after repair
- Verify manifest.php exists in module directory
- Check file permissions (should be 644)
- Run repair again: Admin → Repair → Quick Repair & Rebuild

### Database errors on schema creation
- Verify database user has CREATE TABLE permission
- Check SuiteCRM database connection configuration
- Look in `suitecrm.log` for specific errors

### Relationship not showing
- Verify Accounts/Leads have been refreshed after repair
- Check relationship configuration in Admin → Studio
- Run repair again if changes don't appear

### Permission errors
- Ensure user role has access to Amortizations module
- Check Admin → User Management → Roles
- Verify module is assigned to role

## CRM Integration

### Link Loan to Account

```php
$loan = BeanFactory::newBean('Amortizations');
$loan->name = 'Loan for Acme Corp';
$loan->amount = 50000;
$loan->account_id = $accountId; // Link to account
$loan->save();
```

### Create Activity from Payment

```php
$activity = BeanFactory::newBean('Tasks');
$activity->name = 'Loan payment due';
$activity->description = 'Payment of $1,500 due on ' . $dueDate;
$activity->parent_type = 'Amortizations';
$activity->parent_id = $loanId;
$activity->save();
```

### Send Email Notification

```php
// Use SuiteCRM email system
$email = BeanFactory::newBean('Emails');
$email->to_addrs = $contactEmail;
$email->subject = 'Loan Payment Due';
$email->body_html = $paymentNotificationTemplate;
$email->save();
```

## Deactivation/Uninstall

### Disable Module
1. Go to: **Admin → System Settings → Module Settings**
2. Disable "Amortizations" module
3. Run repair

### Remove Module
1. Disable module (above)
2. Delete module directory:
   ```bash
   rm -rf /suitecrm/custom/modules/Amortizations/
   ```
3. Run repair again
4. Remove Composer packages (optional):
   ```bash
   composer remove ksfraser/amortizations-suitecrm
   composer remove ksfraser/amortizations-core
   ```

To remove database tables:

```php
// Via SuiteCRM CLI
php sugar_cli.php SchemaManager:deleteTable --table=ksf_amort_loans
php sugar_cli.php SchemaManager:deleteTable --table=ksf_amort_schedules
php sugar_cli.php SchemaManager:deleteTable --table=ksf_amort_events
php sugar_cli.php SchemaManager:deleteTable --table=ksf_amort_selectors
php sugar_cli.php SchemaManager:deleteTable --table=ksf_amort_staging
```

## Support

For issues:
- Check SuiteCRM log: `/suitecrm.log`
- Review module test output: `cd vendor/ksfraser/amortizations-suitecrm && composer test`
- Verify database tables: `mysql> SHOW TABLES LIKE 'ksf_amort%';`

## Next Steps

1. Configure loan types and payment frequencies
2. Set up account relationships
3. Create email notification templates
4. Train CRM users on loan management

---

**Version:** 1.0.0  
**Last Updated:** December 9, 2025
