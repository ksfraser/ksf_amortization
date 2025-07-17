# Requirements Traceability Matrix

| Requirement | Test Case(s) | Code File(s) |
|-------------|--------------|--------------|
| Amortization menu (FA) | UAT.md (FA: Menu), test_amortization.php | fa/hooks.php, fa/controller.php |
| MVC architecture | Unit tests, UAT.md (all) | src/Ksfraser/Amortizations/AmortizationModel.php, controller.php, view.php |
| Schedule management | test_amortization.php, UAT.md (all) | src/Ksfraser/Amortizations/AmortizationModel.php, FADataProvider.php, WPDataProvider.php, SuiteCRMDataProvider.php |
| Platform DB/UI integration | UAT.md (all), WP/Suite tests | FADataProvider.php, WPDataProvider.php, SuiteCRMDataProvider.php |
| Unit tests | test_amortization.php, WPDataProviderTest.php, SuiteCRMDataProviderTest.php | tests/ |
| UAT | UAT.md (all) | UAT.md (all) |
| UML diagrams | Architecture.md | Architecture.md |
| Platform adapters | UAT.md (all), adapter tests | FADataProvider.php, WPDataProvider.php, SuiteCRMDataProvider.php |
| Loan types | test_amortization.php, UAT.md (all) | AmortizationModel.php, adapters |
| Repayment schedules | test_amortization.php, UAT.md (all) | AmortizationModel.php |
| Reporting | UAT.md (all) | reporting.php |
| Staging table | test_amortization.php, UAT.md (all) | FADataProvider.php, WPDataProvider.php, SuiteCRMDataProvider.php |
| GL integration (FA) | UAT.md (FA: GL), test_amortization.php | FAJournalService.php, FADataProvider.php |
| Admin screens | UAT.md (all) | view.php, controller.php |
| Multi-platform | UAT.md (all), adapter tests | adapters, Architecture.md |
| Security | UAT.md (all) | controller.php, adapters |
| SOLID/DRY | Unit tests, code review | src/Ksfraser/Amortizations/ |
| Documentation | UAT.md, Architecture.md, README.md | README.md, Architecture.md |

---
