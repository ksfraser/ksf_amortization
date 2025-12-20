<?php
// Admin screen for selector options
// Uses Ksfraser\HTML builders for semantic HTML generation

use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Form;
use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Button;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableCell;
use Ksfraser\HTML\Elements\TableHeaderCell;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\EditButton;
use Ksfraser\HTML\Elements\DeleteButton;
use Ksfraser\HTML\Elements\HtmlString;

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

// Build heading
(new Heading(2))->setText('Selector Options Admin')->toHtml();

// Build form
$form = (new Form())->setMethod('post');
$form->appendChild((new Input())->setType('hidden')->setName('id')->setId('edit_id'));

$form->appendChild((new Label())->setFor('selector_name')->setText('Selector Name:'));
$form->appendChild((new Input())->setType('text')->setName('selector_name')->setId('selector_name')->addAttribute('required', 'required'));

$form->appendChild((new Label())->setFor('option_name')->setText('Option Name:'));
$form->appendChild((new Input())->setType('text')->setName('option_name')->setId('option_name')->addAttribute('required', 'required'));

$form->appendChild((new Label())->setFor('option_value')->setText('Option Value:'));
$form->appendChild((new Input())->setType('text')->setName('option_value')->setId('option_value')->addAttribute('required', 'required'));

$form->appendChild((new Button())->setType('submit')->setName('add')->setText('Add Option'));
$form->appendChild((new Button())->setType('submit')->setName('edit')->setText('Edit Option'));

$form->toHtml();

// Build table
$table = (new Table())->addAttribute('border', '1');

// Build header row
$headerRow = (new TableRow());
$headerRow->appendChild((new TableHeaderCell())->setText('ID'));
$headerRow->appendChild((new TableHeaderCell())->setText('Selector Name'));
$headerRow->appendChild((new TableHeaderCell())->setText('Option Name'));
$headerRow->appendChild((new TableHeaderCell())->setText('Option Value'));
$headerRow->appendChild((new TableHeaderCell())->setText('Actions'));

$table->appendChild($headerRow);

// Build data rows
foreach ($options as $opt) {
    $row = (new TableRow());
    $row->appendChild((new TableCell())->setText((string)$opt['id']));
    $row->appendChild((new TableCell())->setText(htmlspecialchars($opt['selector_name'])));
    $row->appendChild((new TableCell())->setText(htmlspecialchars($opt['option_name'])));
    $row->appendChild((new TableCell())->setText(htmlspecialchars($opt['option_value'])));
    
    // Actions cell
    $actionsDiv = (new Div());
    
    // Edit button using specialized EditButton class
    $editBtn = new EditButton(
        new HtmlString('Edit'),
        (string)$opt['id'],
        sprintf(
            "editOption(%d, '%s', '%s', '%s')",
            $opt['id'],
            addslashes($opt['selector_name']),
            addslashes($opt['option_name']),
            addslashes($opt['option_value'])
        )
    );
    $actionsDiv->appendChild($editBtn);
    
    // Delete button with form submission
    $deleteForm = (new Form())->setMethod('post')->addAttribute('style', 'display:inline');
    $deleteForm->appendChild((new Input())->setType('hidden')->setName('id')->setValue((string)$opt['id']));
    
    $deleteBtn = new DeleteButton(new HtmlString('Delete'), (string)$opt['id']);
    $deleteBtn->setName('delete_btn')->setType('submit'); // Make it a submit button
    $deleteForm->appendChild($deleteBtn);
    
    $actionsDiv->appendChild($deleteForm);
    
    $actionCell = (new TableCell());
    $actionCell->appendChild($actionsDiv);
    $row->appendChild($actionCell);
    
    $table->appendChild($row);
}

$table->toHtml();

// Output JavaScript
echo "<script>\n";
echo "function editOption(id, selector, name, value) {\n";
echo "  document.getElementById('edit_id').value = id;\n";
echo "  document.getElementById('selector_name').value = selector;\n";
echo "  document.getElementById('option_name').value = name;\n";
echo "  document.getElementById('option_value').value = value;\n";
echo "}\n";
echo "</script>\n";
