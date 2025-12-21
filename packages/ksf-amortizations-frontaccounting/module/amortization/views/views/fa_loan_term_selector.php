<?php
/**
 * FrontAccounting Loan Term and Payment Frequency Selector
 * 
 * Provides interface for users to select loan term and payment frequency.
 * Updates hidden field with calculated payments per year based on frequency.
 * Uses HTML builder pattern and PaymentFrequencyHandler for logic encapsulation.
 * SRP: Single responsibility of term/frequency selection UI.
 * 
 * @var int $loanTermYears Current loan term in years
 * @var string $paymentFrequency Currently selected payment frequency
 * @var int $paymentsPerYear Calculated payments per year
 */

use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\PaymentFrequencyHandler;

$loanTermYears = $loanTermYears ?? 1;
$paymentFrequency = $paymentFrequency ?? 'monthly';
$paymentsPerYear = $paymentsPerYear ?? 12;
$freqHandler = (new PaymentFrequencyHandler())->setSelectId('payment_frequency')->setSelectedFrequency($paymentFrequency);
$container = (new Div())->addClass('fa-term-selector')->append((new Div())->addClass('form-group')->append((new Label())->setFor('loan_term_years')->setText('Loan Term (Years) *'))->append((new Input())->setType('number')->setId('loan_term_years')->setName('loan_term_years')->setAttribute('min', '0.25')->setAttribute('step', '0.25')->setAttribute('value', (string)$loanTermYears)->setRequired(true)))->append((new Div())->addClass('form-group')->append((new Label())->setFor('payment_frequency')->setText('Payment Frequency *'))->append((new Select())->setId('payment_frequency')->setName('payment_frequency')->setRequired(true)->addOptionsFromArray($freqHandler->getFrequencyOptions())))->append((new Input())->setType('hidden')->setId('payments_per_year')->setName('payments_per_year')->setAttribute('value', (string)$paymentsPerYear));
echo $container->toHtml();
?>

<?php
// Generate payment frequency handler for JavaScript logic
$handler = (new PaymentFrequencyHandler())
    ->setSelectId('payment_frequency')
    ->setPaymentsFieldId('payments_per_year')
    ->setSelectedFrequency($paymentFrequency);

echo $handler->toHtml();
?>
