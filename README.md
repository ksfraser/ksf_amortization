# ksf_amortization

Amortization calculation and schedule for FrontAccounting, WordPress, and SuiteCRM.

## Functional Overview

This module provides:
- Amortization schedule calculation for all loan types (Auto, Mortgage, etc.)
- Flexible loan setup: amount financed, interest rate, payment frequency, interest calculation frequency, number of payments, payment override, first/last payment dates
- Repayment schedule options: monthly, bi-weekly, weekly, custom
- Reporting: paydown schedule, principal/interest breakdown, export/print
- Staging table for payment review before posting
- GL integration for FrontAccounting: transfer payments to appropriate accounts
- Admin screens for global settings and GL mapping
- User screens for loan setup and review
- Multi-platform support: business logic is reusable in FA, WordPress, SuiteCRM
- Unit tests and UAT scripts for all features

## Getting Started
- Install via Composer
- Configure platform-specific adaptor (FA, WP, SuiteCRM)
- Run unit tests: `composer test`
- Review UAT scripts: `composer uat`
