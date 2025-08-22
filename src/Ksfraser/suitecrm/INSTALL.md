# Installation: SuiteCRM

1. Ensure Composer is installed on your system.
2. Run `composer require ksfraser/amortizations` in your SuiteCRM custom modules directory.
3. Copy the contents of `modules/suitecrm` to your SuiteCRM custom modules directory.
4. Configure the module in SuiteCRM admin (menu entry, permissions, etc.).
5. Run unit tests with `composer test`.
6. Review UAT scripts in `tests/UAT.md`.
