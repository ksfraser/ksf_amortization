document.getElementById("selector_name").addEventListener("change", function() {
    var newDiv = document.getElementById("new_selector_div");
    if (this.value === "_new") {
        newDiv.style.display = "block";
        document.getElementById("new_selector_name").required = true;
    } else {
        newDiv.style.display = "none";
        document.getElementById("new_selector_name").required = false;
    }
});
</script>';
</script>';

global $db;
$dbPrefix = defined('TB_PREF') ? TB_PREF : '';

// Robust DB check: unset, null, not PDO
if (!isset($db) || !$db || !($db instanceof PDO)) {
    echo (new Heading(2))->setText('Manage Selectors')->render();
    echo '<p>Database connection not available. This view must be accessed through FrontAccounting.</p>';
    return;
}

echo (new Heading(2))->setText('Manage Selectors')->render();

// Use _core SelectorProvider for all selector data
require_once __DIR__ . '/../../../../vendor-src/ksf_amortization_core/Amortizations/SelectorProvider.php';

class PDOAdapter implements \Ksfraser\Amortizations\SelectorDbAdapter {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }
    public function query(string $sql) { return $this->pdo->query($sql); }
    public function fetch_assoc($result) { return $result->fetch(\PDO::FETCH_ASSOC); }
    public function escape($value) { return $this->pdo->quote($value); }
    public function execute(string $sql, array $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
}

$adapter = new PDOAdapter($db);
$selectorProvider = new \Ksfraser\Amortizations\SelectorProvider($adapter, $dbPrefix);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_option'])) {
        $selectorName = $_POST['selector_name'] === '_new' ? $_POST['new_selector_name'] : $_POST['selector_name'];
        // For demo: treat all as loan types
        $selectorProvider->addLoanType($selectorName, $_POST['option_value']);
        echo '<p style="color: green;">Option added successfully.</p>';
    } elseif (isset($_POST['delete_option'])) {
        $selectorProvider->deleteLoanType($_POST['option_id']);
        echo '<p style="color: green;">Option deleted successfully.</p>';
    }
}

$allLoanTypes = $selectorProvider->getLoanTypes();

// Display existing selectors (loan types)
echo '<h3>Existing Loan Types</h3>';
if (!empty($allLoanTypes)) {
    echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
    echo '<tr><th>ID</th><th>Name</th><th>Description</th><th>Action</th></tr>';
    foreach ($allLoanTypes as $type) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($type->id ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($type->name ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($type->description ?? '') . '</td>';
        echo '<td>';
        echo '<form method="post" style="display:inline;">';
        echo '<input type="hidden" name="option_id" value="' . htmlspecialchars($type->id ?? '') . '">';
        echo '<input type="submit" name="delete_option" value="Delete" onclick="return confirm(\'Are you sure?\');">';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p><em>No loan types defined</em></p>';
}

// Add new loan type form
echo '<h3>Add New Loan Type</h3>';
echo '<form method="post" style="border: 1px solid #ccc; padding: 15px; max-width: 500px;">';
echo '<div style="margin-bottom: 10px;">';
echo '<label for="selector_name">Loan Type Name:</label><br>';
echo '<input type="text" name="selector_name" id="selector_name" required style="width: 100%;">';
echo '</div>';
echo '<div style="margin-bottom: 10px;">';
echo '<label for="option_value">Description:</label><br>';
echo '<input type="text" name="option_value" id="option_value" required style="width: 100%;">';
echo '</div>';
echo '<div>';
echo '<input type="submit" name="add_option" value="Add Loan Type" class="button">';
echo '</div>';
echo '</form>';
?>
