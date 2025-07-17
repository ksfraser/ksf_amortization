<?php
namespace Ksfraser\Amortizations;

use Ksfraser\Amortizations\DataProviderInterface;

require_once __DIR__ . '/model.php';

/**
 * Class FADataProvider
 * FrontAccounting data provider implementation for amortization module
 *
 * @package Ksfraser\Amortizations
 * @author ksfraser
 *
 * UML:
 * ```
 * class FADataProvider {
 *   - pdo: PDO
 *   + __construct(pdo: PDO)
 *   + insertLoan(data: array): int
 *   + getLoan(loan_id: int): array
 *   + insertSchedule(loan_id: int, schedule_row: array): void
 * }
 * ```
 */
}
