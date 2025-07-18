<?php
namespace Ksfraser\Amortizations\Views;

class LoanSummaryTable {
    public static function render(array $loans) {
        ob_start();
        ?>
        <h3>Loan Summary</h3>
        <table border="1">
            <tr><th>ID</th><th>Borrower</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
            <?php foreach ($loans as $loan): ?>
            <tr>
                <td><?= htmlspecialchars($loan->id) ?></td>
                <td><?= htmlspecialchars($loan->borrower) ?></td>
                <td><?= htmlspecialchars($loan->amount) ?></td>
                <td><?= htmlspecialchars($loan->status) ?></td>
                <td>
                    <button>View</button>
                    <button>Edit</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
        return ob_get_clean();
    }
}
