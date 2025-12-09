<?php
namespace Ksfraser\Amortizations\Views;

class LoanTypeTable {
    public static function render(array $loanTypes) {
        ob_start();
        ?>
        <h3>Loan Types</h3>
        <table border="1">
            <tr><th>ID</th><th>Name</th><th>Description</th><th>Actions</th></tr>
            <?php foreach ($loanTypes as $type): ?>
            <tr>
                <td><?= htmlspecialchars($type->id) ?></td>
                <td><?= htmlspecialchars($type->name) ?></td>
                <td><?= htmlspecialchars($type->description) ?></td>
                <td>
                    <button>Edit</button>
                    <button>Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <form method="post" action="">
            <input type="text" name="loan_type_name" placeholder="New Loan Type">
            <input type="text" name="loan_type_desc" placeholder="Description">
            <input type="submit" value="Add Loan Type">
        </form>
        <?php
        return ob_get_clean();
    }
}
