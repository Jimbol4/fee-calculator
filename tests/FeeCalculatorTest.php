<?php 

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lendable\Interview\Interpolation\Service\Fee\FeeCalculator;
use Lendable\Interview\Interpolation\Model\LoanApplication;

class FeeCalculatorTest extends TestCase 
{

    // dummy fees for testing purposes. Ideally we would have a db seeder or something
    // similar to handle this in a testing environment.
    public $dummyFees = [
        12 => [
            1000 => 50,
            2000 => 90,
            3000 => 90,
            4000 => 115,
            5000 => 100,
            6000 => 120,
            7000 => 140,
            8000 => 160,
            9000 => 180,
            10000 => 200,
            11000 => 220,
            12000 => 240,
            13000 => 260,
            14000 => 280,
            15000 => 300,
            16000 => 320,
            17000 => 340,
            18000 => 360,
            19000 => 380,
            20000 => 400,
        ],
         24 => [
            1000 => 70,
            2000 => 100,
            3000 => 120,
            4000 => 160,
            5000 => 200,
            6000 => 240,
            7000 => 280,
            8000 => 320,
            9000 => 360,
            10000 => 400,
            11000 => 440,
            12000 => 480,
            13000 => 520,
            14000 => 560,
            15000 => 600,
            16000 => 640,
            17000 => 680,
            18000 => 720,
            19000 => 760,
            20000 => 800,
        ]
    ];

    public function setUp(): void
    {
        $this->calculator = new FeeCalculator($this->dummyFees);
    }

    public function tearDown(): void
    {
        unset($this->calculator);
    }

    public function testItGetsTheCorrectFeeFromTheExample()
    {
        $application = new LoanApplication(24, 2750);

        $fee = $this->calculator->calculate($application);

        $this->assertEquals(115.0, $fee);
    }

    public function testItThrowsExceptionIfTermIsInvalid()
    {
        $application = new LoanApplication(37, 2750);

        $this->expectException(InvalidArgumentException::class);

        $fee = $this->calculator->calculate($application);
    }

    public function testItThrowsExceptionIfAmountIsInvalid()
    {
        $application = new LoanApplication(12, 2750000);

        $this->expectException(InvalidArgumentException::class);

        $fee = $this->calculator->calculate($application);
    }

    public function testItFetchesAnExactMatch()
    {
        $application = new LoanApplication(12, 1000);

        $fee = $this->calculator->calculate($application);

        $this->assertEquals(50.0, $fee);
    }

    public function testItAccountsForTheFeeGoingDown()
    {
        $application = new LoanApplication(12, 4500);

        $fee = $this->calculator->calculate($application);

        $this->assertEquals(105.0, $fee);
    }

    public function testItAccountsForTheFeeStayingTheSame()
    {
        $application = new LoanApplication(12, 2501);

        $fee = $this->calculator->calculate($application);

        $this->assertEquals(90.0, $fee);   
    }

    public function testItCopesWithDecimalAmounts()
    {
        $application = new LoanApplication(12, 4500.37);

        $fee = $this->calculator->calculate($application);

        $this->assertEquals(105.00, $fee);
    }
}