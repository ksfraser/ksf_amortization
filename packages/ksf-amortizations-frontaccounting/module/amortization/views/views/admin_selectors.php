<?php
/**
 * Admin Selector Options Management
 * 
 * Allows administrators to manage selector options for loan types, terms, etc.
 * Provides CRUD interface for selector options with HTML builder pattern.
 * Follows SRP - single responsibility of selector option management UI.
 * 
 * @var array $options List of selector options to display
 */

use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Form;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Button;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;
use Ksfraser\HTML\Elements\Paragraph;
use Ksfraser\HTML\Elements\TableBuilder;
use Ksfraser\HTML\Elements\EditButton;
use Ksfraser\HTML\Elements\DeleteButton;
use Ksfraser\HTML\Elements\SelectEditJSHandler;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\Amortizations\Repository\SelectorRepository;

// Initialize repository
$selectorRepo = new SelectorRepository();

// Get FrontAccounting constants
$dbPrefix = defined('TB_PREF') ? TB_PREF : '0_';

// Handle POST actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $selectorRepo->add($_POST);
                break;
            case 'update':
                $selectorRepo->update($_POST);
                break;
            case 'delete':
                $selectorRepo->delete($_POST);
                break;
        }
    }
}

// Load options from database via repository
$options = $selectorRepo->getAll();

// Build the page heading
echo (new Heading(2))->setText('Selector Options Management')->toHtml();

// Build the form with fields
$form = (new Form())->setId('selectorForm')->setMethod('POST');
$form->append((new Div())->addClass('form-group')->append((new Label())->setFor('selector_name')->setText('Selector Name *'))->append((new Input())->setType('text')->setId('selector_name')->setName('selector_name')->setAttribute('placeholder', 'e.g., Loan Type')->setRequired(true)));
$form->append((new Div())->addClass('form-group')->append((new Label())->setFor('option_name')->setText('Option Name *'))->append((new Input())->setType('text')->setId('option_name')->setName('option_name')->setAttribute('placeholder', 'e.g., Personal Loan')->setRequired(true)));
$form->append((new Div())->addClass('form-group')->append((new Label())->setFor('option_value')->setText('Option Value *'))->append((new Input())->setType('text')->setId('option_value')->setName('option_value')->setAttribute('placeholder', 'Internal value')->setRequired(true)));
$actions = (new Div())->addClass('form-actions')->append((new Button())->setType('submit')->setName('action')->setAttribute('value', 'add')->setText('Add Option'))->append((new Button())->setType('reset')->setText('Clear'));
$form->append($actions);
echo $form->toHtml();

// Build the options table
if (!empty($options)) {
    $table = (new Table())->addClass('selectors-table');
    
    // Header row using TableBuilder
    $headerRow = TableBuilder::createHeaderRow(['ID', 'Selector Name', 'Option Name', 'Option Value', 'Actions']);
    $table->append($headerRow);
    
    // Data rows
    foreach ($options as $option) {
        $row = (new TableRow());
        $row->append((new TableData())->setText((string)$option['id']));
        $row->append((new TableData())->setText(htmlspecialchars($option['selector_name'])));
        $row->append((new TableData())->setText(htmlspecialchars($option['option_name'])));
        $row->append((new TableData())->setText(htmlspecialchars($option['option_value'])));
        
        // Actions cell with EditButton and DeleteButton
        $actionsCell = (new TableData());
        $actionsDiv = (new Div());
        
        $actionsDiv->append(
            (new EditButton(
                new HtmlString('Edit'),
                (string)$option['id'],
                sprintf("editOption(%d, '%s', '%s', '%s')", 
                    $option['id'],
                    addslashes($option['selector_name']),
                    addslashes($option['option_name']),
                    addslashes($option['option_value'])
                )
            ))
        );
        
        $actionsDiv->append(
            (new DeleteButton(
                new HtmlString('Delete'),
                (string)$option['id'],
                "deleteOption(" . intval($option['id']) . ")"
            ))
        );
        
        $actionsCell->append($actionsDiv);
        $row->append($actionsCell);
        
        $table->append($row);
    }
    
    echo $table->toHtml();
} else {
    echo (new Paragraph())->addClass('no-data')->setText('No selector options found. Create your first option above.')->toHtml();
}
?>

<?php
// Generate edit JavaScript handler
$editHandler = (new SelectEditJSHandler())
    ->setFormIdPrefix('selector')
    ->setFieldNames(['id', 'selector_name', 'option_name', 'option_value']);

echo $editHandler->toHtml();
?>
