<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\CustomerStorage;
use PHPUnit\Framework\TestCase;

final class CustomerStorageTest extends TestCase
{
    private string $filePath;
    private CustomerStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filePath = sys_get_temp_dir()
            .'/customers_'.uniqid('', true).'.json';

        file_put_contents(
            $this->filePath,
            json_encode([
                '1' => [
                    'loyaltyCustomer' => true,
                    'b2b' => true,
                ],
                '2' => [
                    'loyaltyCustomer' => true,
                    'b2b' => false,
                ],
                '3' => [
                    'loyaltyCustomer' => false,
                    'b2b' => true,
                ],
                '4' => [
                    'loyaltyCustomer' => false,
                    'b2b' => false,
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $this->storage = new CustomerStorage($this->filePath);
    }

    protected function tearDown(): void
    {
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }

        parent::tearDown();
    }

    public function testReturnsLoyaltyAndB2BStatus(): void
    {
        $customer = $this->storage->getCustomer(1);

        self::assertSame(1, $customer['id']);
        self::assertTrue($customer['loyaltyCustomer']);
        self::assertTrue($customer['b2b']);
    }

    public function testReturnsLoyaltyCustomerWithoutB2BStatus(): void
    {
        $customer = $this->storage->getCustomer(2);

        self::assertSame(2, $customer['id']);
        self::assertTrue($customer['loyaltyCustomer']);
        self::assertFalse($customer['b2b']);
    }

    public function testReturnsB2BCustomerWithoutLoyaltyStatus(): void
    {
        $customer = $this->storage->getCustomer(3);

        self::assertSame(3, $customer['id']);
        self::assertFalse($customer['loyaltyCustomer']);
        self::assertTrue($customer['b2b']);
    }

    public function testReturnsCustomerWithoutLoyaltyOrB2BStatus(): void
    {
        $customer = $this->storage->getCustomer(4);

        self::assertSame(4, $customer['id']);
        self::assertFalse($customer['loyaltyCustomer']);
        self::assertFalse($customer['b2b']);
    }

    public function testThrowsExceptionForNonExistingCustomer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Customer with ID 999 does not exist.'
        );

        $this->storage->getCustomer(999);
    }

    public function testThrowsExceptionWhenCustomerDataIsInvalid(): void
    {
        file_put_contents(
            $this->filePath,
            json_encode([
                '1' => [
                    'loyaltyCustomer' => true,
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Customer with ID 1 contains invalid data.'
        );

        $this->storage->getCustomer(1);
    }

    public function testThrowsExceptionWhenCustomerFileDoesNotExist(): void
    {
        unlink($this->filePath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Customer file does not exist.'
        );

        $this->storage->getCustomer(1);
    }

    public function testThrowsExceptionWhenCustomerFileContainsInvalidJson(): void
    {
        file_put_contents(
            $this->filePath,
            '{invalid json'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Customer file contains invalid JSON.'
        );

        $this->storage->getCustomer(1);
    }
}