<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PriceControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $ordersFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->ordersFile = static::getContainer()
            ->getParameter('app.orders_path');

        file_put_contents(
            $this->ordersFile,
            '{}'
        );
    }

    public function testReturnsPriceQuote(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'customerId' => 1,
                'format' => 'A6',
                'paper' => 'Kreda 130 g',
                'quantity' => 400,
                'realizationDate' => '2099-01-03',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(1, $data['customerId']);
        self::assertSame('A6', $data['format']);
        self::assertSame('Kreda 130 g', $data['paper']);
        self::assertSame(400, $data['quantity']);
        self::assertSame(19, $data['basePrice']);
        self::assertSame(72.2, $data['net']);
        self::assertSame(16.61, $data['vat']);
        self::assertSame(88.81, $data['gross']);
        self::assertArrayHasKey('orderId', $data);
        self::assertArrayHasKey('createdAt', $data);
    }

    public function testSavesCreatedOrderToJsonFile(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'customerId' => 1,
                'format' => 'A6',
                'paper' => 'Kreda 130 g',
                'quantity' => 400,
                'realizationDate' => '2099-01-03',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);

        $responseData = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertArrayHasKey('orderId', $responseData);

        $orders = json_decode(
            file_get_contents($this->ordersFile),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertArrayHasKey(
            $responseData['orderId'],
            $orders
        );

        $savedOrder = $orders[$responseData['orderId']];

        self::assertSame(
            $responseData['orderId'],
            $savedOrder['orderId']
        );
        self::assertSame(
            $responseData['customerId'],
            $savedOrder['customerId']
        );
        self::assertSame(
            $responseData['format'],
            $savedOrder['format']
        );
        self::assertSame(
            $responseData['paper'],
            $savedOrder['paper']
        );
        self::assertSame(
            $responseData['quantity'],
            $savedOrder['quantity']
        );
        self::assertSame(
            $responseData['basePrice'],
            $savedOrder['basePrice']
        );
        self::assertSame(
            $responseData['net'],
            $savedOrder['net']
        );
        self::assertSame(
            $responseData['vat'],
            $savedOrder['vat']
        );
        self::assertSame(
            $responseData['gross'],
            $savedOrder['gross']
        );
    }

    public function testReturnsBasePriceFromPriceList(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'customerId' => 4,
                'format' => 'A4',
                'paper' => 'Kreda 170 g',
                'quantity' => 100,
                'realizationDate' => '2099-01-03',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(69, $data['basePrice']);
        self::assertSame(69, $data['net']);
    }

    public function testLoyaltyCustomerStatusComesFromBackend(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'customerId' => 2,
                'format' => 'A6',
                'paper' => 'Kreda 130 g',
                'quantity' => 1000,
                'realizationDate' => '2099-01-03',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(10, $data['discounts']['quantity']);
        self::assertSame(5, $data['discounts']['loyalty']);
        self::assertSame(162.45, $data['net']);
    }

    public function testNonExistingCustomerIsRejected(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'customerId' => 999,
                'format' => 'A6',
                'paper' => 'Kreda 130 g',
                'quantity' => 400,
                'realizationDate' => '2099-01-03',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(
            'Customer with ID 999 does not exist.',
            $data['error']
        );
    }

    public function testQuantityBelowMinimumIsRejected(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'customerId' => 1,
                'format' => 'A6',
                'paper' => 'Kreda 130 g',
                'quantity' => 0,
                'realizationDate' => '2099-01-03',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testQuantityAboveMaximumIsRejected(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'customerId' => 1,
                'format' => 'A6',
                'paper' => 'Kreda 130 g',
                'quantity' => 20100,
                'realizationDate' => '2099-01-03',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testQuantityNotMultipleOf100IsRejected(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'customerId' => 1,
                'format' => 'A6',
                'paper' => 'Kreda 130 g',
                'quantity' => 150,
                'realizationDate' => '2099-01-03',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testInvalidJsonIsRejected(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"customerId":'
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testRealizationDateTooEarlyIsRejected(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'customerId' => 1,
                'format' => 'A6',
                'paper' => 'Kreda 130 g',
                'quantity' => 400,
                'realizationDate' => '2020-01-01',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testMissingCustomerIdIsRejected(): void
    {
        $this->client->request(
            'POST',
            '/api/price/quote',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'format' => 'A6',
                'paper' => 'Kreda 130 g',
                'quantity' => 400,
                'realizationDate' => '2099-01-03',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
    }
}