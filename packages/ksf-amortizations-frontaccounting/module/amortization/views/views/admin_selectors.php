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
use Ksfraser\HTML\Elements\HtmlHidden;
use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlScript;
use Ksfraser\HTML\Elements\SelectEditJSHandler;
use Ksfraser\HTML\Elements\TableBuilder;
use Ksfraser\Amortizations\Repository\SelectorRepository;

// Get table prefix from FrontAccounting constant
$dbPrefix = defined('TB_PREF') ? TB_PREF : '0_';

// Initialize repository for data access
$selectorRepo = new SelectorRepository($db, 'ksf_selectors', $dbPrefix);

// Handle add/edit/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $selectorRepo->add($_POST['selector_name'], $_POST['option_name'], $_POST['option_value']);
    } elseif (isset($_POST['edit'])) {
        $selectorRepo->update($_POST['id'], $_POST['selector_name'], $_POST['option_name'], $_POST['option_value']);
    } elseif (isset($_POST['delete'])) {
        $selectorRepo->delete($_POST['id']);
    }
}

// Fetch all selector options
$options = $selectorRepo->getAll();

// Build heading
(new Heading(2))->setText('Selector Options Admin')->toHtml();

// Build form
$form = (new Form())->setMethod('post');
$form->appendChild((new HtmlHidden())->setName('id')->setId('edit_id'));

$form->appendChild((new Label())->setFor('selector_name')->setText('Selector Name:'));
$form->appendChild((new Input())->setType('text')->setName('selector_name')->setId('selector_name')->addAttribute('required', 'required'));

$form->appendChild((new Label())->setFor('option_name')->setText('Option Name:'));
$form->appendChild((new Input())->setType('text')->setName('option_name')->setId('option_name')->addAttribute('required', 'required'));

$form->appendChild((new Label())->setFor('option_value')->setText('Option Value:'));
$form->appendChild((new Input())->setType('text')->setName('option_value')->setId('option_value')->addAttribute('required', 'required'));

$form->appendChild((new HtmlSubmit(new HtmlString('Add Option')))->setName('add'));
$form->appendChild((new HtmlSubmit(new HtmlString('Edit Option')))->setName('edit'));

$form->toHtml();

// Build table
$table = (new Table())->addAttribute('border', '1');

// Build header row using TableBuilder
$headerRow = TableBuilder::createHeaderRow([
    'ID',
    'Selector Name',
    'Option Name',
    'Option Value',
    'Actions'
]);
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
    $deleteForm->appendChild((new HtmlHidden())->setName('id')->setValue((string)$opt['id']));
    
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

// Output JavaScript using specialized handler class
$editHandler = new SelectEditJSHandler();
$editHandler->getHtml();
