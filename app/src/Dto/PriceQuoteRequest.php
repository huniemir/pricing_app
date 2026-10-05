<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PriceQuoteRequest
{
    public function __construct(
        #[Assert\Positive]
        public int $customerId,

        #[Assert\NotBlank]
        public string $format,

        #[Assert\NotBlank]
        public string $paper,

        #[Assert\Range(min: 100, max: 20000)]
        #[Assert\Positive]
        public int $quantity,

        #[Assert\NotBlank]
        public string $realizationDate,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            customerId: (int) ($data['customerId'] ?? 0),
            format: (string) ($data['format'] ?? ''),
            paper: (string) ($data['paper'] ?? ''),
            quantity: (int) ($data['quantity'] ?? 0),
            realizationDate: (string) ($data['realizationDate'] ?? ''),
        );
    }
}