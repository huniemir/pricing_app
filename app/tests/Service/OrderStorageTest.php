<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\OrderStorage;
use PHPUnit\Framework\TestCase;

final class OrderStorageTest extends TestCase
{
    private string $filePath;
    private OrderStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filePath = sys_get_temp_dir()
            .'/orders_'.uniqid('', true).'.json';

        $this->storage = new OrderStorage($this->filePath);
    }

    protected function tearDown(): void
    {
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }

        parent::tearDown();
    }

    public function testSavesOrderToJsonFile(): void
    {
        $order = [
            'orderId' => 'abc123',
            'createdAt' => '2099-01-03T12:00:00+00:00',
            'customerId' => 1,
            'b2b' => true,
            'format' => 'A6',
            'paper' => 'Kreda 130 g',
            'quantity' => 400,
            'basePrice' => 19,
            'realizationDate' => '2099-01-05T12:00:00+00:00',
            'express' => false,
            'discounts' => [
                'quantity' => 0,
                'loyalty' => 5,
            ],
            'surcharges' => [
                'express' => 0,
            ],
            'net' => 72.2,
            'vat' => 16.61,
            'gross' => 88.81,
        ];

        $this->storage->save($order);

        self::assertFileExists($this->filePath);

        $data = json_decode(
            file_get_contents($this->filePath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertArrayHasKey('abc123', $data);
        self::assertSame($order, $data['abc123']);
    }

    public function testReturnsSavedOrder(): void
    {
        $order = [
            'orderId' => 'abc123',
            'customerId' => 2,
            'format' => 'A4',
            'paper' => 'Kreda 170 g',
            'quantity' => 100,
            'net' => 69,
        ];

        $this->storage->save($order);

        self::assertSame(
            $order,
            $this->storage->get('abc123')
        );
    }

    public function testCanSaveMultipleOrders(): void
    {
        $firstOrder = [
            'orderId' => 'order-1',
            'customerId' => 1,
            'quantity' => 100,
        ];

        $secondOrder = [
            'orderId' => 'order-2',
            'customerId' => 2,
            'quantity' => 500,
        ];

        $this->storage->save($firstOrder);
        $this->storage->save($secondOrder);

        self::assertSame(
            $firstOrder,
            $this->storage->get('order-1')
        );

        self::assertSame(
            $secondOrder,
            $this->storage->get('order-2')
        );
    }

    public function testThrowsExceptionForNonExistingOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Order with ID "does-not-exist" does not exist.'
        );

        $this->storage->get('does-not-exist');
    }

    public function testThrowsExceptionWhenOrderIdIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order ID is required.');

        $this->storage->save([
            'customerId' => 1,
            'quantity' => 100,
        ]);
    }

    public function testCreatesEmptyStorageWhenFileDoesNotExist(): void
    {
        self::assertFalse(is_file($this->filePath));

        $this->expectException(\InvalidArgumentException::class);

        $this->storage->get('order-1');
    }
}