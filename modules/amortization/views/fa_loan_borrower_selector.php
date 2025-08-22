<?php
// FA Loan Borrower Selector UI
// Assumes $db is the FA database connection
?>
<label for="borrower_type">Borrower Type:</label>
<select name="borrower_type" id="borrower_type" onchange="faFetchBorrowers()">
  <option value="">Select Type</option>
  <option value="Customer">Customer</option>
  <option value="Supplier">Supplier</option>
  <option value="Employee">Employee</option>
</select>

<label for="borrower_id">Borrower:</label>
<select name="borrower_id" id="borrower_id">
  <option value="">Select Borrower</option>
</select>

<script>
function faFetchBorrowers() {
  var type = document.getElementById('borrower_type').value;
  if (!type) return;
  var xhr = new XMLHttpRequest();
  xhr.open('GET', 'borrower_ajax.php?type=' + encodeURIComponent(type));
  xhr.onload = function() {
    if (xhr.status === 200) {
      var data = JSON.parse(xhr.responseText);
      var select = document.getElementById('borrower_id');
      select.innerHTML = '<option value="">Select Borrower</option>';
      data.forEach(function(b) {
        select.innerHTML += '<option value="' + b.id + '">' + b.name + '</option>';
      });
    }
  };
  xhr.send();
}
</script>
