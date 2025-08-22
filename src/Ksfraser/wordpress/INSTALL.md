# Installation: WordPress

1. Ensure Composer is installed on your system.
2. Run `composer require ksfraser/amortizations` in your WordPress plugin directory.
3. Copy the contents of `modules/wordpress` to your WordPress plugin directory.
4. Activate the plugin in WordPress admin.
5. Configure the module (menu entry, permissions, etc.).
6. Run unit tests with `composer test`.
7. Review UAT scripts in `tests/UAT.md`.
