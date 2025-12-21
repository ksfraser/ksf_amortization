<?php
require 'vendor/autoload.php';

use Ksfraser\HTML\Elements\Input;

$input = (new Input())
    ->setType('text')
    ->setName('test')
    ->setValue('hello');

echo "Input HTML:\n";
echo htmlspecialchars($input->render());
echo "\n";
