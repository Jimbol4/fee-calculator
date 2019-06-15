<?php

require_once ('./vendor/autoload.php');

use Lendable\Interview\Interpolation\Model\LoanApplication;
use Lendable\Interview\Interpolation\Service\Fee\FeeCalculator;

$calculator = new FeeCalculator();

$fee = $calculator->calculate(new LoanApplication(24, 2750));

echo var_dump($fee);
//  float(115)

$fee2 = $calculator->calculate(new LoanApplication(12, 4500));
echo var_dump($fee2);
// float(105)

$fee3 = $calculator->calculate(new LoanApplication(12, 2501));
echo var_dump($fee3);
// float(90)


$fee4 = $calculator->calculate(new LoanApplication(12, 2999.97));
echo var_dump($fee4);
// float(90)