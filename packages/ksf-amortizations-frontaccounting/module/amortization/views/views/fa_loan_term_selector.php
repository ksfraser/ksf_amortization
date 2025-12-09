<?php
// FA Loan Term and Payment Frequency Selector UI
?>
<label for="loan_term_years">Loan Term (Years):</label>
<input type="number" name="loan_term_years" id="loan_term_years" min="1" value="1" required>

<label for="payment_frequency">Payment Frequency:</label>
<select name="payment_frequency" id="payment_frequency" onchange="updatePaymentsPerYear()">
  <option value="annual">Annual</option>
  <option value="semi-annual">Semi-Annual</option>
  <option value="monthly">Monthly</option>
  <option value="semi-monthly">Semi-Monthly</option>
  <option value="bi-weekly">Bi-Weekly</option>
  <option value="weekly">Weekly</option>
</select>

<input type="hidden" name="payments_per_year" id="payments_per_year" value="12">

<script>
function updatePaymentsPerYear() {
  var freq = document.getElementById('payment_frequency').value;
  var val = 12;
  switch (freq) {
    case 'annual': val = 1; break;
    case 'semi-annual': val = 2; break;
    case 'monthly': val = 12; break;
    case 'semi-monthly': val = 24; break;
    case 'bi-weekly': val = 26; break;
    case 'weekly': val = 52; break;
  }
  document.getElementById('payments_per_year').value = val;
}
</script>
