<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PriceQuoteRequest;
use Symfony\Component\Clock\ClockInterface;

final class PricingService
{
    private const VAT_RATE = 0.23;
    private const EXPRESS_MULTIPLIER = 1.30;
    private const EXPRESS_SURCHARGE = 30.0;
    private const MIN_NET_VALUE = 50.00;

    public function __construct(
        private readonly PriceListStorage $priceListStorage,
        private readonly ClockInterface $clock,
    ) {
    }

    public function calculate(
        PriceQuoteRequest $request,
        array $customer,
    ): array {
        if ($request->quantity < 100 || $request->quantity > 20_000) {
            throw new \InvalidArgumentException(
                'Quantity must be between 100 and 20000.'
            );
        }

        if ($request->quantity % 100 !== 0) {
            throw new \InvalidArgumentException(
                'Quantity must be a multiple of 100.'
            );
        }

        try {
            $realizationDate = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $request->realizationDate
            );

            $errors = \DateTimeImmutable::getLastErrors();

            if (
                $realizationDate === false
                || (
                    $errors !== false
                    && (
                        $errors['warning_count'] > 0
                        || $errors['error_count'] > 0
                    )
                )
                || $realizationDate->format('Y-m-d') !== $request->realizationDate
            ) {
                throw new \Exception();
            }
        } catch (\Exception) {
            throw new \InvalidArgumentException(
                'Invalid realization date. Expected format: YYYY-MM-DD.'
            );
        }

        $today = $this->clock->now()->setTime(0, 0);
        $tomorrow = $today->modify('+1 day');

        if ($realizationDate < $tomorrow) {
            throw new \InvalidArgumentException(
                'Realization date must be at least tomorrow.'
            );
        }

        $isExpress = $realizationDate == $tomorrow;
        $basePrice = $this->priceListStorage->getPrice(
            $request->format,
            $request->paper
        );

        $net = $basePrice * ($request->quantity / 100);

        $quantityDiscount = 0.0;
        $loyaltyDiscount = 0.0;
        $expressSurcharge = 0.0;

        if ($isExpress) {
            $expressSurcharge = self::EXPRESS_SURCHARGE;
            $net *= self::EXPRESS_MULTIPLIER;
        } else {
            if ($request->quantity >= 5000) {
                $quantityDiscount = 0.15;
            } elseif ($request->quantity >= 1000) {
                $quantityDiscount = 0.10;
            }

            $net *= (1 - $quantityDiscount);

            if ($customer['loyaltyCustomer']) {
                $loyaltyDiscount = 0.05;
                $net *= (1 - $loyaltyDiscount);
            }
        }

        if ($net < self::MIN_NET_VALUE) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Minimum order value is %.2f PLN net.',
                    self::MIN_NET_VALUE
                )
            );
        }

        $net = round($net, 2);
        $vat = round($net * self::VAT_RATE, 2);
        $gross = round($net + $vat, 2);

        return [
            'customerId' => $customer['id'],
            'b2b' => $customer['b2b'],
            'format' => $request->format,
            'paper' => $request->paper,
            'quantity' => $request->quantity,
            'basePrice' => $basePrice,
            'realizationDate' => $realizationDate->format('Y-m-d'),
            'express' => $isExpress,
            'discounts' => [
                'quantity' => $quantityDiscount * 100,
                'loyalty' => $loyaltyDiscount * 100,
            ],
            'surcharges' => [
                'express' => $expressSurcharge,
            ],
            'net' => $net,
            'vat' => $vat,
            'gross' => $gross,
        ];
    }
}