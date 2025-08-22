<?php
// SuiteCRM Loan Borrower Selector UI
// Assumes $db is the SuiteCRM database connection
?>
<label for="borrower_id">Borrower (Contact):</label>
<select name="borrower_id" id="borrower_id">
  <option value="">Select Contact</option>
  <?php
  $sql = "SELECT id, first_name, last_name FROM contacts";
  $result = $db->query($sql);
  while ($row = $db->fetch_assoc($result)) {
      $name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
      echo "<option value=\"{$row['id']}\">$name</option>";
  }
  ?>
</select>
