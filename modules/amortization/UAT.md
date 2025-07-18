# User Acceptance Testing (UAT) - FrontAccounting

## Amortization Menu
- Verify 'Amortization' appears under Banking and General Ledger.
- Click menu: should open amortization module.

## Loan Setup
- Add new loan: fill all fields, save, verify in list.
- Edit loan: change details, save, verify update.
- Delete loan: remove, verify gone.

## Schedule Calculation
- Calculate schedule: verify payment lines generated in staging table.
- Review payment lines: check principal, interest, balance.

## GL Integration
- Post payment to GL: verify correct accounts, amounts, and status.
- Use 'Add GL' button: create new GL account, select for loan.

## Security
- Test user permissions: restrict access, verify only allowed users can post to GL.

## Reporting
- Generate paydown report: verify amounts, export/print options.

## Error Handling
- Try invalid data: verify error messages and no data saved.

---
