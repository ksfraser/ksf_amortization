<?php
// vardefs for AmortizationLoans module in SuiteCRM
$dictionary['AmortizationLoans'] = array(
    'table' => 'amortization_loans',
    'fields' => array(
        'id' => array(
            'name' => 'id',
            'type' => 'id',
        ),
        'loan_type' => array(
            'name' => 'loan_type',
            'type' => 'varchar',
            'len' => 50,
        ),
        'principal' => array(
            'name' => 'principal',
            'type' => 'decimal',
            'len' => '15,2',
        ),
        'interest_rate' => array(
            'name' => 'interest_rate',
            'type' => 'decimal',
            'len' => '5,2',
        ),
        'term' => array(
            'name' => 'term',
            'type' => 'int',
        ),
        'schedule' => array(
            'name' => 'schedule',
            'type' => 'varchar',
            'len' => 20,
        ),
        'created_at' => array(
            'name' => 'created_at',
            'type' => 'datetime',
        ),
    ),
    'indices' => array(
        array('name' =>'amortizationloanspk', 'type' =>'primary', 'fields'=>array('id')),
    ),
);
