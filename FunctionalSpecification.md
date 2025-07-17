# Functional Specification

## Module Scope
- Amortization schedule management for loans
- Platform integration (FA, WP, SuiteCRM)
- Reporting, GL posting (FA), security

## Use Cases
1. Create Loan
2. Edit Loan
3. Delete Loan
4. Calculate Amortization Schedule
5. Review/Approve Payment Lines
6. Post Payment to GL (FA)
7. Generate Reports
8. Configure Module Settings

## Data Model
- Loan: type, amount, rate, term, schedule, etc.
- Payment Schedule: payment date, amount, principal, interest, balance
- GL Mapping (FA): asset, liability, expense, value accounts

## User Interface
- Admin screens for setup/configuration
- User screens for loan management
- Reporting screens

## Integration Points
- FA: GL, menu, staging table
- WP: custom tables, admin UI
- SuiteCRM: modules, vardefs

## Security
- User permissions for module features

---
