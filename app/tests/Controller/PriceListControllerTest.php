<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PriceListControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private const DEFAULT_PRICE_LIST = [
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->resetPriceList();
    }

    private function resetPriceList(): void
    {
        $priceListPath = static::getContainer()
            ->getParameter('app.price_list_path');

        file_put_contents(
            $priceListPath,
            json_encode(
                self::DEFAULT_PRICE_LIST,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            LOCK_EX
        );
    }

    public function testCanGetPriceList(): void
    {
        $this->client->request(
            'GET',
            '/api/price-list'
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertArrayHasKey('A6', $data);
        $this->assertArrayHasKey('DL', $data);
        $this->assertArrayHasKey('A5', $data);
        $this->assertArrayHasKey('A4', $data);

        $this->assertSame(19, $data['A6']['Kreda 130 g']);
        $this->assertSame(23, $data['A6']['Kreda 170 g']);
        $this->assertSame(15, $data['A6']['Offset 90 g']);
    }

    public function testCanUpdatePrice(): void
    {
        $this->client->request(
            'PUT',
            '/api/price-list/A6/Kreda%20130%20g',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'price' => 21.50,
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('A6', $data['format']);
        $this->assertSame('Kreda 130 g', $data['paper']);
        $this->assertSame(21.5, $data['price']);

        $this->client->request(
            'GET',
            '/api/price-list'
        );

        $this->assertResponseIsSuccessful();

        $priceList = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            21.5,
            $priceList['A6']['Kreda 130 g']
        );
    }

    public function testRejectsInvalidJson(): void
    {
        $this->client->request(
            'PUT',
            '/api/price-list/A6/Kreda%20130%20g',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: '{invalid json'
        );

        $this->assertResponseStatusCodeSame(400);

        $this->assertStringContainsString(
            'Invalid JSON body',
            $this->client->getResponse()->getContent()
        );
    }

    public function testRejectsMissingPrice(): void
    {
        $this->client->request(
            'PUT',
            '/api/price-list/A6/Kreda%20130%20g',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'foo' => 'bar',
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            'Field "price" is required.',
            $data['error']
        );
    }

    public function testRejectsNonPositivePrice(): void
    {
        $this->client->request(
            'PUT',
            '/api/price-list/A6/Kreda%20130%20g',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'price' => 0,
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            'Price must be greater than zero.',
            $data['error']
        );
    }
}