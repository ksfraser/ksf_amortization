<?php
// User Loan Setup Form using SelectorModel for choices
use Ksfraser\Amortizations\SelectorModel;

$selectorModel = new SelectorModel($db);
$paymentFrequencies = $selectorModel->getOptions('payment_frequency');
$borrowerTypes = $selectorModel->getOptions('borrower_type');
?>
<form method="post">
  <label for="loan_term_years">Loan Term (Years):</label>
  <input type="number" name="loan_term_years" id="loan_term_years" min="1" value="1" required>

  <label for="payment_frequency">Payment Frequency:</label>
  <select name="payment_frequency" id="payment_frequency">
    <?php foreach ($paymentFrequencies as $opt): ?>
      <option value="<?= htmlspecialchars($opt['option_value']) ?>"><?= htmlspecialchars($opt['option_name']) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="borrower_type">Borrower Type:</label>
  <select name="borrower_type" id="borrower_type">
    <?php foreach ($borrowerTypes as $opt): ?>
      <option value="<?= htmlspecialchars($opt['option_value']) ?>"><?= htmlspecialchars($opt['option_name']) ?></option>
    <?php endforeach; ?>
  </select>

  <!-- Add other loan fields as needed -->
  <button type="submit">Submit</button>
</form>
