<?php
// WordPress Loan Borrower Selector UI
?>
<label for="borrower_id">Borrower (User):</label>
<select name="borrower_id" id="borrower_id">
  <option value="">Select User</option>
  <?php
  $users = get_users(['fields' => ['ID', 'display_name']]);
  foreach ($users as $user) {
      $name = esc_html($user->display_name);
      echo "<option value=\"{$user->ID}\">$name</option>";
  }
  ?>
</select>
