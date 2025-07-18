<?php
namespace Ksfraser\Amortizations\Views;

class ReportingTable {
    public static function render(array $reports) {
        ob_start();
        ?>
        <h3>Reports</h3>
        <table border="1">
            <tr><th>ID</th><th>Type</th><th>Date</th><th>Actions</th></tr>
            <?php foreach ($reports as $report): ?>
            <tr>
                <td><?= htmlspecialchars($report->id) ?></td>
                <td><?= htmlspecialchars($report->type) ?></td>
                <td><?= htmlspecialchars($report->date) ?></td>
                <td>
                    <button>View</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
        return ob_get_clean();
    }
}
