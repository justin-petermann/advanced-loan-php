#!/usr/bin/env php
<?php

require_once('../lib/loan.php');

$loan = new LoanPhp\loan();

$loan->setInsuranceRate(0.98);
#$loan->setInsuranceBase('initialCapaital');
$loan->setCreditRate(3.10);
$loan->setLoanTerm(214);
$loan->setAmountBorrowed(203000);

$loan->setModificator(6, 50000, -12*1, False);

#$loan->setModificator(10, 00, +1, True);

$loan->findMonthlyPayment();

$html = $loan->getHtml();
print ($html);

file_put_contents('index.html', $html);

unset($loan);
