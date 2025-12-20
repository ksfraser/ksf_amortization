<?php
// FA Loan Borrower Selector UI
// Uses Ksfraser\HTML builders for semantic HTML generation

use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;

// Build type selector
$typeLabel = (new Label())->setFor('borrower_type')->setText('Borrower Type:');
$typeSelect = (new Select())->setName('borrower_type')->setId('borrower_type')->addAttribute('onchange', 'faFetchBorrowers()');
$typeSelect->appendChild((new Option())->setValue('')->setText('Select Type'));
$typeSelect->appendChild((new Option())->setValue('Customer')->setText('Customer'));
$typeSelect->appendChild((new Option())->setValue('Supplier')->setText('Supplier'));
$typeSelect->appendChild((new Option())->setValue('Employee')->setText('Employee'));

// Build borrower selector
$borrowerLabel = (new Label())->setFor('borrower_id')->setText('Borrower:');
$borrowerSelect = (new Select())->setName('borrower_id')->setId('borrower_id');
$borrowerSelect->appendChild((new Option())->setValue('')->setText('Select Borrower'));

// Render HTML
$typeLabel->toHtml();
echo "\n";
$typeSelect->toHtml();
echo "\n\n";
$borrowerLabel->toHtml();
echo "\n";
$borrowerSelect->toHtml();
echo "\n";

// Output JavaScript
echo "<script>\n";
echo "function faFetchBorrowers() {\n";
echo "  var type = document.getElementById('borrower_type').value;\n";
echo "  if (!type) return;\n";
echo "  var xhr = new XMLHttpRequest();\n";
echo "  xhr.open('GET', 'borrower_ajax.php?type=' + encodeURIComponent(type));\n";
echo "  xhr.onload = function() {\n";
echo "    if (xhr.status === 200) {\n";
echo "      var data = JSON.parse(xhr.responseText);\n";
echo "      var select = document.getElementById('borrower_id');\n";
echo "      select.innerHTML = '<option value=\"\">Select Borrower</option>';\n";
echo "      data.forEach(function(b) {\n";
echo "        select.innerHTML += '<option value=\"' + b.id + '\">' + b.name + '</option>';\n";
echo "      });\n";
echo "    }\n";
echo "  };\n";
echo "  xhr.send();\n";
echo "}\n";
echo "</script>\n";
