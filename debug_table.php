<?php
require 'vendor/autoload.php';

use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;

// Create a simple table with one row
$table = (new Table())->addClass('my-table');

$row = (new TableRow())->addClass('my-row');
$row->append(
    (new TableData())->setText('Cell 1'),
    (new TableData())->setText('Cell 2')
);

$table->append($row);

echo "Table HTML:\n";
$html = $table->render();
echo htmlspecialchars($html);
echo "\n\nTable Row HTML:\n";
echo htmlspecialchars($row->render());
echo "\n\nCell HTML:\n";
echo htmlspecialchars((new \Ksfraser\HTML\Elements\TableData())->setText('Test')->render());
echo "\n";

