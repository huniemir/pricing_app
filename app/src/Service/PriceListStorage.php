<?php

declare(strict_types=1);

namespace App\Service;

final class PriceListStorage
{
    public function __construct(
        private readonly string $filePath,
    ) {
    }

    public function getPrice(string $format, string $paper): float
    {
        $prices = $this->read();

        if (!isset($prices[$format])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown format "%s".', $format)
            );
        }

        if (!isset($prices[$format][$paper])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown paper "%s" for format "%s".',
                    $paper,
                    $format
                )
            );
        }

        return (float) $prices[$format][$paper];
    }

    public function getAll(): array
    {
        return $this->read();
    }

    public function setPrice(
        string $format,
        string $paper,
        float $price
    ): void {
        if ($price <= 0) {
            throw new \InvalidArgumentException(
                'Price must be greater than zero.'
            );
        }

        $prices = $this->read();

        $prices[$format] ??= [];
        $prices[$format][$paper] = round($price, 2);

        $this->write($prices);
    }

    private function read(): array
    {
        if (!is_file($this->filePath)) {
            throw new \RuntimeException(
                'Price list file does not exist.'
            );
        }

        $content = file_get_contents($this->filePath);

        if ($content === false) {
            throw new \RuntimeException(
                'Unable to read price list.'
            );
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new \RuntimeException(
                'Price list contains invalid JSON.'
            );
        }

        return $data;
    }

    private function write(array $prices): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $result = file_put_contents(
            $this->filePath,
            json_encode(
                $prices,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            LOCK_EX
        );

        if ($result === false) {
            throw new \RuntimeException(
                'Unable to write price list.'
            );
        }
    }
}