# Installation: FrontAccounting (FA)

1. Ensure Composer is installed on your system.
2. Run `composer require ksfraser/amortizations` in your FA modules directory.
3. Copy the contents of `modules/fa` to your FrontAccounting modules directory.
4. Configure the module in FA admin (menu entry, GL mappings, etc.).
5. Run unit tests with `composer test`.
6. Review UAT scripts in `tests/UAT.md`.
