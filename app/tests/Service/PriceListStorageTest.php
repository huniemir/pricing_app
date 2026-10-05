<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\PriceListStorage;
use PHPUnit\Framework\TestCase;

final class PriceListStorageTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = tempnam(
            sys_get_temp_dir(),
            'price-list-'
        );

        file_put_contents(
            $this->file,
            json_encode([
                'A4' => [
                    'Kreda 130 g' => 58.00,
                    'Kreda 170 g' => 69.00,
                ],
            ], JSON_THROW_ON_ERROR)
        );
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    public function testReadsPrice(): void
    {
        $storage = new PriceListStorage($this->file);

        self::assertSame(
            58.00,
            $storage->getPrice('A4', 'Kreda 130 g')
        );
    }

    public function testThrowsForUnknownFormat(): void
    {
        $storage = new PriceListStorage($this->file);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unknown format "A3".'
        );

        $storage->getPrice('A3', 'Kreda 130 g');
    }

    public function testThrowsForUnknownPaper(): void
    {
        $storage = new PriceListStorage($this->file);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unknown paper "Offset 90 g" for format "A4".'
        );

        $storage->getPrice('A4', 'Offset 90 g');
    }

    public function testCanUpdatePrice(): void
    {
        $storage = new PriceListStorage($this->file);

        $storage->setPrice(
            'A4',
            'Kreda 130 g',
            62.50
        );

        self::assertSame(
            62.50,
            $storage->getPrice('A4', 'Kreda 130 g')
        );
    }

    public function testCanCreatePriceForNewPaper(): void
    {
        $storage = new PriceListStorage($this->file);

        $storage->setPrice(
            'A4',
            'Offset 90 g',
            47.00
        );

        self::assertSame(
            47.00,
            $storage->getPrice('A4', 'Offset 90 g')
        );
    }

    public function testRejectsNegativePrice(): void
    {
        $storage = new PriceListStorage($this->file);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Price must be greater than zero.'
        );

        $storage->setPrice(
            'A4',
            'Kreda 130 g',
            -10.00
        );
    }
}