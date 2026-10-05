<?php

declare(strict_types=1);

namespace App\Service;

final class OrderStorage
{
    public function __construct(
        private readonly string $filePath,
    ) {
    }

    public function save(array $order): array
    {
        $orders = $this->read();

        $orderId = $order['orderId'] ?? null;

        if (!is_string($orderId) || $orderId === '') {
            throw new \InvalidArgumentException(
                'Order ID is required.'
            );
        }

        $orders[$orderId] = $order;

        $this->write($orders);

        return $order;
    }

    public function get(string $orderId): array
    {
        $orders = $this->read();

        if (!isset($orders[$orderId])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Order with ID "%s" does not exist.',
                    $orderId
                )
            );
        }

        if (!is_array($orders[$orderId])) {
            throw new \RuntimeException(
                sprintf(
                    'Order with ID "%s" contains invalid data.',
                    $orderId
                )
            );
        }

        return $orders[$orderId];
    }

    private function read(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);

        if ($content === false) {
            throw new \RuntimeException(
                'Unable to read orders file.'
            );
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new \RuntimeException(
                'Orders file contains invalid JSON.'
            );
        }

        return $data;
    }

    private function write(array $orders): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $result = file_put_contents(
            $this->filePath,
            json_encode(
                $orders,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_THROW_ON_ERROR
            ),
            LOCK_EX
        );

        if ($result === false) {
            throw new \RuntimeException(
                'Unable to write orders file.'
            );
        }
    }
}