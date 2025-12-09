<?php
// Admin screen for selector options
// Assumes $db is the database connection

// Handle add/edit/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $stmt = $db->prepare("INSERT INTO 0_ksf_selectors (selector_name, option_name, option_value) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['selector_name'], $_POST['option_name'], $_POST['option_value']]);
    } elseif (isset($_POST['edit'])) {
        $stmt = $db->prepare("UPDATE 0_ksf_selectors SET selector_name=?, option_name=?, option_value=? WHERE id=?");
        $stmt->execute([$_POST['selector_name'], $_POST['option_name'], $_POST['option_value'], $_POST['id']]);
    } elseif (isset($_POST['delete'])) {
        $stmt = $db->prepare("DELETE FROM 0_ksf_selectors WHERE id=?");
        $stmt->execute([$_POST['id']]);
    }
}

// Fetch all selector options
$options = $db->query("SELECT * FROM 0_ksf_selectors ORDER BY selector_name, option_name")->fetchAll(PDO::FETCH_ASSOC);

// UI
?>
<h2>Selector Options Admin</h2>
<form method="post">
  <input type="hidden" name="id" id="edit_id">
  <label for="selector_name">Selector Name:</label>
  <input type="text" name="selector_name" id="selector_name" required>
  <label for="option_name">Option Name:</label>
  <input type="text" name="option_name" id="option_name" required>
  <label for="option_value">Option Value:</label>
  <input type="text" name="option_value" id="option_value" required>
  <button type="submit" name="add">Add Option</button>
  <button type="submit" name="edit">Edit Option</button>
</form>

<table border="1">
  <tr><th>ID</th><th>Selector Name</th><th>Option Name</th><th>Option Value</th><th>Actions</th></tr>
  <?php foreach ($options as $opt): ?>
    <tr>
      <td><?= $opt['id'] ?></td>
      <td><?= htmlspecialchars($opt['selector_name']) ?></td>
      <td><?= htmlspecialchars($opt['option_name']) ?></td>
      <td><?= htmlspecialchars($opt['option_value']) ?></td>
      <td>
        <button onclick="editOption(<?= $opt['id'] ?>, '<?= addslashes($opt['selector_name']) ?>', '<?= addslashes($opt['option_name']) ?>', '<?= addslashes($opt['option_value']) ?>')">Edit</button>
        <form method="post" style="display:inline">
          <input type="hidden" name="id" value="<?= $opt['id'] ?>">
          <button type="submit" name="delete">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<script>
function editOption(id, selector, name, value) {
  document.getElementById('edit_id').value = id;
  document.getElementById('selector_name').value = selector;
  document.getElementById('option_name').value = name;
  document.getElementById('option_value').value = value;
}
</script>
