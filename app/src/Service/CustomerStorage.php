<?php

declare(strict_types=1);

namespace App\Service;

final class CustomerStorage
{
    public function __construct(
        private readonly string $filePath,
    ) {
    }

    public function getCustomer(int $customerId): array
    {
        $customers = $this->read();

        $customerKey = (string) $customerId;

        if (!isset($customers[$customerKey])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Customer with ID %d does not exist.',
                    $customerId
                )
            );
        }

        $customer = $customers[$customerKey];

        if (
            !is_array($customer)
            || !isset($customer['loyaltyCustomer'])
            || !isset($customer['b2b'])
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Customer with ID %d contains invalid data.',
                    $customerId
                )
            );
        }

        return [
            'id' => $customerId,
            'loyaltyCustomer' => (bool) $customer['loyaltyCustomer'],
            'b2b' => (bool) $customer['b2b'],
        ];
    }

    private function read(): array
    {
        if (!is_file($this->filePath)) {
            throw new \RuntimeException(
                'Customer file does not exist.'
            );
        }

        $content = file_get_contents($this->filePath);

        if ($content === false) {
            throw new \RuntimeException(
                'Unable to read customer file.'
            );
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new \RuntimeException(
                'Customer file contains invalid JSON.'
            );
        }

        return $data;
    }
}