<?php
namespace Ksfraser\Amortizations\Views;

class InterestCalcFrequencyTable {
    public static function render(array $interestCalcFreqs) {
        ob_start();
        ?>
        <h3>Interest Calculation Frequencies</h3>
        <table border="1">
            <tr><th>ID</th><th>Name</th><th>Description</th><th>Actions</th></tr>
            <?php foreach ($interestCalcFreqs as $freq): ?>
            <tr>
                <td><?= htmlspecialchars($freq->id) ?></td>
                <td><?= htmlspecialchars($freq->name) ?></td>
                <td><?= htmlspecialchars($freq->description) ?></td>
                <td>
                    <button>Edit</button>
                    <button>Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <form method="post" action="">
            <input type="text" name="interest_calc_freq_name" placeholder="New Frequency">
            <input type="text" name="interest_calc_freq_desc" placeholder="Description">
            <input type="submit" value="Add Frequency">
        </form>
        <?php
        return ob_get_clean();
    }
}
