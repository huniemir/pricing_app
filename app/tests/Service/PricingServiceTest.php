<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\PriceQuoteRequest;
use App\Service\PriceListStorage;
use App\Service\PricingService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

final class PricingServiceTest extends KernelTestCase
{
    private const PRICE_LIST = [
        'A6' => [
            'Kreda 130 g' => 19.00,
            'Kreda 170 g' => 23.00,
            'Offset 90 g' => 15.00,
        ],
        'DL' => [
            'Kreda 130 g' => 24.00,
            'Kreda 170 g' => 29.00,
            'Offset 90 g' => 19.00,
        ],
        'A5' => [
            'Kreda 130 g' => 32.00,
            'Kreda 170 g' => 38.00,
            'Offset 90 g' => 26.00,
        ],
        'A4' => [
            'Kreda 130 g' => 58.00,
            'Kreda 170 g' => 69.00,
            'Offset 90 g' => 47.00,
        ],
    ];

    private MockClock $clock;
    private PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();

        $directory = sys_get_temp_dir()
            .'/pricing-service-'.uniqid();

        mkdir($directory, 0777, true);

        $filePath = $directory.'/price-list.json';

        file_put_contents(
            $filePath,
            json_encode(
                self::PRICE_LIST,
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
            )
        );

        $this->clock = new MockClock(
            '2026-01-01 12:00:00+00:00'
        );

        $priceListStorage = new PriceListStorage($filePath);

        $this->pricingService = new PricingService(
            $priceListStorage,
            $this->clock
        );
    }

    public function testCalculatesBasePriceAndVat(): void
    {
        $result = $this->calculate(
            quantity: 400,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );

        self::assertSame(19.0, $result['basePrice']);
        self::assertSame(76.0, $result['net']);
        self::assertSame(17.48, $result['vat']);
        self::assertSame(93.48, $result['gross']);
    }

    public function testMinimumQuantityIsAllowed(): void
    {
        $result = $this->calculate(
            quantity: 100,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false,
            format: 'A4',
            paper: 'Kreda 130 g'
        );

        self::assertSame(100, $result['quantity']);
        self::assertSame(58.0, $result['basePrice']);
        self::assertSame(58.0, $result['net']);
    }

    public function testQuantityBelowMinimumIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculate(
            quantity: 0,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );
    }

    public function testQuantityAboveMaximumIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculate(
            quantity: 20_100,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );
    }

    public function testMaximumQuantityIsAllowed(): void
    {
        $result = $this->calculate(
            quantity: 20_000,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );

        self::assertSame(20_000, $result['quantity']);
        self::assertSame(15.0, $result['discounts']['quantity']);
    }

    public function testQuantityMustBeMultipleOf100(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculate(
            quantity: 150,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );
    }

    public function testQuantity900HasNoQuantityDiscount(): void
    {
        $result = $this->calculate(
            quantity: 900,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );

        self::assertSame(0.0, $result['discounts']['quantity']);
    }

    public function testQuantity1000Gets10PercentDiscount(): void
    {
        $result = $this->calculate(
            quantity: 1000,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );

        self::assertSame(10.0, $result['discounts']['quantity']);
        self::assertSame(171.0, $result['net']);
    }

    public function testQuantity4999IsRejectedBecauseQuantityMustBeMultipleOf100(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculate(
            quantity: 4999,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );
    }

    public function testQuantity4900StillGets10PercentDiscount(): void
    {
        $result = $this->calculate(
            quantity: 4900,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );

        self::assertSame(10.0, $result['discounts']['quantity']);
    }

    public function testQuantity5000Gets15PercentDiscount(): void
    {
        $result = $this->calculate(
            quantity: 5000,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );

        self::assertSame(15.0, $result['discounts']['quantity']);
        self::assertSame(807.5, $result['net']);
    }

    public function testLoyaltyDiscountIsAppliedAfterQuantityDiscount(): void
    {
        $result = $this->calculate(
            quantity: 1000,
            realizationDate: '2026-01-03',
            loyaltyCustomer: true,
            b2b: false
        );

        self::assertSame(10.0, $result['discounts']['quantity']);
        self::assertSame(5.0, $result['discounts']['loyalty']);
        self::assertSame(162.45, $result['net']);
    }

    public function testExpressOrderGets30PercentSurchargeAndNoDiscounts(): void
    {
        $result = $this->calculate(
            quantity: 1000,
            realizationDate: '2026-01-02',
            loyaltyCustomer: true,
            b2b: true
        );

        self::assertTrue($result['express']);
        self::assertSame(0.0, $result['discounts']['quantity']);
        self::assertSame(0.0, $result['discounts']['loyalty']);
        self::assertSame(247.0, $result['net']);
    }

    public function testExactly24HoursIsExpress(): void
    {
        $result = $this->calculate(
            quantity: 400,
            realizationDate: '2026-01-02',
            loyaltyCustomer: false,
            b2b: false
        );

        self::assertTrue($result['express']);
    }

    public function testTodayIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculate(
            quantity: 400,
            realizationDate: '2026-01-01',
            loyaltyCustomer: false,
            b2b: false
        );
    }

    public function testMoreThan24HoursIsStandard(): void
    {
        $result = $this->calculate(
            quantity: 400,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );

        self::assertFalse($result['express']);
    }

    public function testMinimumOrderValueIsEnforced(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculate(
            quantity: 100,
            realizationDate: '2026-01-03',
            loyaltyCustomer: true,
            b2b: false,
            format: 'A6',
            paper: 'Offset 90 g'
        );
    }

    public function testB2BStatusIsReturnedButDoesNotChangePrice(): void
    {
        $regular = $this->calculate(
            quantity: 400,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: false
        );

        $b2b = $this->calculate(
            quantity: 400,
            realizationDate: '2026-01-03',
            loyaltyCustomer: false,
            b2b: true
        );

        self::assertFalse($regular['b2b']);
        self::assertTrue($b2b['b2b']);
        self::assertSame($regular['net'], $b2b['net']);
    }

    private function calculate(
        int $quantity,
        string $realizationDate,
        bool $loyaltyCustomer,
        bool $b2b,
        string $format = 'A6',
        string $paper = 'Kreda 130 g',
    ): array {
        $request = new PriceQuoteRequest(
            customerId: 1,
            format: $format,
            paper: $paper,
            quantity: $quantity,
            realizationDate: $realizationDate,
        );

        return $this->pricingService->calculate(
            $request,
            [
                'id' => 1,
                'loyaltyCustomer' => $loyaltyCustomer,
                'b2b' => $b2b,
            ]
        );
    }
}