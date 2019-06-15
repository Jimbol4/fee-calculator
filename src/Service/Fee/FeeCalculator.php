<?php

declare(strict_types=1);

namespace Lendable\Interview\Interpolation\Service\Fee;

use Lendable\Interview\Interpolation\Model\LoanApplication;
use Lendable\Interview\Interpolation\Service\Fee\FeeCalculatorInterface;
use MathPHP\NumericalAnalysis\Interpolation\LagrangePolynomial;

/**
 * Fee Calculator class to calculate (via interpolation) the fees due
 * given a loan application dependent on term/amount.
 */
class FeeCalculator implements FeeCalculatorInterface 
{

    /**
     * @var Lendable\Interview\Interpolation\Model\LoanApplication;
     */
    protected $application;

    /**
     * @var array
     */
    protected $rawFees;

    /**
     * @var array
     */
    protected $fees;

    /**
     * Construct a FeeCalculator (allows passing of dummy fees for testing purposes)
     * 
     * @param array $dummyFees
     * @return Lendable\Interview\Interpolation\Service\Fee\FeeCalculator;
     */
    public function __construct(array $dummyFees = [])
    {
        if ($dummyFees) {
            $this->rawFees = $dummyFees;
        }
    }

    /**
     * Implements Lendable\Interview\Interpolation\Service\Fee\FeeCalculatorInterface
     * 
     * @param Lendable\Interview\Interpolation\Model\LoanApplication $application
     */
    public function calculate(LoanApplication $application) : float
    {
        $this->application = $application;

        $this->loadFeeData();

        return $this->fetchFee();
    }

    /**
     * Return a direct array match if found, or pass to interpolateFee() if not
     * 
     * @return float
     */
    protected function fetchFee() : float
    {

        // return a direct match if one is found
        if (array_key_exists((int) $this->application->getAmount(), $this->fees)) {
            return $this->fees[$this->application->getAmount()];
        }

        // otherwise interpolate the figure
        return $this->interpolateFee();
    }

    /**
     * Load fee data, either via JSON file or directly from passed dummy data
     * NB: this should ideally be a database call or request but I have done it like this
     * for the purposes of this exercise
     * 
     * @throws InvalidArgumentException if LoanApplication parameters fall out of expected ranges
     */
    protected function loadFeeData() : void
    {

        // load via json file (DB or API request in the real world...)
        if (! $this->rawFees) {
            $feesJson = file_get_contents('fees.json');
            $this->rawFees = json_decode($feesJson, true);
        }

        // validate that loan term and amount fall within expected parameters
        if (!array_key_exists($this->application->getTerm(), $this->rawFees)) {
            throw new \InvalidArgumentException('Unrecognised loan term provided');
        }

        if ($this->application->getAmount() < 1000 
            || $this->application->getAmount() > 20000) {
                throw new \InvalidArgumentException('Unrecognised loan amount provided');
            }

        $this->fees = $this->rawFees[$this->application->getTerm()];
    }

    /**
     * Use LagrangePolynomial to interpolate the fee, or get closest matching in edge case
     * 
     * @return float
     */
    protected function interpolateFee() : float
    {
        $input = $this->buildVectors();

        $p = LagrangePolynomial::interpolate($input);

        $fee = $this->roundUpToNearestFive($p($this->application->getAmount()));

        // account for edge case where the interpolation will be 0 if there is no
        // fee change between price bands
        if ($fee == 0.0 || $fee < 0.0)
        {
            return $this->getClosestFee();
        }

        return $fee;
    }

    /**
     * Build an array of vectors to be passed for interpolation
     * 
     * @return array
     */
    protected function buildVectors() : array
    {
        $vectors = [];

        foreach ($this->fees as $amount => $fee) {
            $vectors[] = [$amount, $fee];
        }

        return $vectors;
    }

    /**
     * Round up the passed number to the nearest 5 
     * 
     * @param float $number
     * @return float
     */
    protected function roundUpToNearestFive($number) : float 
    {
        return (ceil($number) % 5 === 0) ? ceil($number) : round(($number + 5 / 2) / 5) * 5;
    }

    /**
     * Get the fee for the closest matching array key to the loan application amount
     * (accounts for edge case where the price stays the same between bands and interpolation is 0.0)
     * 
     * @return float
     */
    protected function getClosestFee() : float
    {
        $feeAmounts = array_keys($this->fees);

        foreach ($feeAmounts as $amount) {
            $smallest[$amount] = abs($amount - $this->application->getAmount());
        }

        asort($smallest);

        return $this->fees[key($smallest)];
    }
}