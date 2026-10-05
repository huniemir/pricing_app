<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PriceListStorage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/price-list')]
final class PriceListController
{
    public function __construct(
        private readonly PriceListStorage $priceListStorage,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse(
            $this->priceListStorage->getAll()
        );
    }

    #[Route('/{format}/{paper}', methods: ['PUT'])]
    public function update(
        string $format,
        string $paper,
        Request $request,
    ): JsonResponse {
        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return new JsonResponse(
                ['error' => 'Invalid JSON body.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!isset($data['price']) || !is_numeric($data['price'])) {
            return new JsonResponse(
                ['error' => 'Field "price" is required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $this->priceListStorage->setPrice(
                $format,
                $paper,
                (float) $data['price']
            );
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return new JsonResponse([
            'format' => $format,
            'paper' => $paper,
            'price' => round((float) $data['price'], 2),
        ]);
    }
}