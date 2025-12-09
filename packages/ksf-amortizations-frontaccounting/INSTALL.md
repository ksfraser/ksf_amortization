# KSF Amortizations - FrontAccounting Installation

## Prerequisites
- FrontAccounting 2.4+
- PHP 7.4+
- PDO MySQL driver
- Composer

## Installation Steps

### 1. Install Dependencies

In your FrontAccounting installation:

```bash
cd /path/to/frontaccounting
composer require ksfraser/amortizations-core
composer require ksfraser/amortizations-frontaccounting
```

### 2. Deploy Module Files

Copy the module to FrontAccounting's modules directory:

```bash
cp -r vendor/ksfraser/amortizations-frontaccounting/module/amortization ./modules/
```

### 3. Database Setup

Run the schema creation (automatically done when module is installed via FA):

```bash
# Option A: Via FrontAccounting Admin Interface (RECOMMENDED)
# Setup → System Setup → Modules → Amortizations → Install

# Option B: Manual SQL
mysql -h localhost -u root -p frontaccounting < vendor/ksfraser/amortizations-frontaccounting/module/amortization/schema.sql
```

### 4. Activate in FrontAccounting

1. Log in as Administrator
2. Navigate to: **Setup → System Setup → Modules**
3. Find "Amortizations" in the list
4. Click to enable and configure

### 5. Configure Module

#### GL Account Mapping

1. Go to: **Loans → Settings → GL Mapping**
2. Configure which GL accounts to use for:
   - Principal posting
   - Interest posting
   - Unapplied payments
   - Overpayments

#### Payment Options

1. Go to: **Loans → Settings → Payment Options**
2. Configure:
   - Auto-post on creation
   - Auto-reverse on schedule changes
   - Payment staging review requirement

#### Selector Options

1. Go to: **Loans → Settings → Selectors**
2. Define available options for:
   - Payment frequencies (monthly, weekly, bi-weekly, etc.)
   - Loan types (auto, mortgage, etc.)
   - Borrower types (individual, business, etc.)

### 6. Verify Installation

Run the test suite:

```bash
cd vendor/ksfraser/amortizations-frontaccounting
composer test
```

All tests should pass (24 unit tests + 19 integration tests).

## Module Location

After installation, the module structure will be:

```
/frontaccounting/modules/amortization/
├── hooks.php
├── controller.php
├── model.php
├── staging_model.php
├── reporting.php
├── schema.sql
├── views/
│   ├── admin_settings.php
│   ├── admin_selectors.php
│   ├── user_loan_setup.php
│   ├── fa_loan_borrower_selector.php
│   ├── fa_loan_term_selector.php
│   └── ...
├── _init/
│   └── config/
└── (other config files)

/frontaccounting/vendor/ksfraser/amortizations-frontaccounting/
├── src/
│   └── Ksfraser/Amortizations/FA/
│       ├── FAJournalService.php
│       ├── FADataProvider.php
│       ├── GLPostingService.php
│       ├── AmortizationGLController.php
│       ├── GLAccountMapper.php
│       └── JournalEntryBuilder.php
└── tests/
    ├── FADataProviderTest.php
    ├── FAJournalServiceTest.php
    └── TASK3GLIntegrationTest.php
```

## Troubleshooting

### Module not appearing in Setup menu
- Verify `hooks.php` is in `/modules/amortization/`
- Ensure proper FrontAccounting version (2.4+)
- Clear browser cache and FrontAccounting session

### Database errors on install
- Verify database user has CREATE TABLE permissions
- Check database prefix matches FA configuration
- Review FrontAccounting error logs

### GL posting not working
- Verify GL accounts are configured in settings
- Check GL account numbers are valid in FA
- Review journal entry details in FA GL module

## Uninstall

To remove the module:

```bash
# 1. Disable in FA: Setup → System Setup → Modules
# 2. Delete files
rm -rf /frontaccounting/modules/amortization/

# 3. Remove Composer packages (optional)
composer remove ksfraser/amortizations-frontaccounting
composer remove ksfraser/amortizations-core
```

## Support

For issues or questions:
- Check FrontAccounting system logs: `/var/log/frontaccounting/`
- Review module test output: `cd vendor/ksfraser/amortizations-frontaccounting && composer test`
- Check database schema matches expected tables

## Next Steps

After installation:
1. Review [USER_GUIDE.md](../../../docs/USER_GUIDE.md) for end-user documentation
2. Run UAT scenarios: See [UAT_TEST_SCRIPTS.md](../../../docs/UAT_TEST_SCRIPTS.md)
3. Configure GL mappings for your loan types
4. Set up user permissions for loan administrators

---

**Version:** 1.0.0  
**Last Updated:** December 9, 2025
