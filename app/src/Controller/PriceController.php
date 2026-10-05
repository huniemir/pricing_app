<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PriceQuoteRequest;
use App\Service\CustomerStorage;
use App\Service\OrderStorage;
use App\Service\PricingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/price')]
final class PriceController
{
    public function __construct(
        private readonly PricingService $pricingService,
        private readonly CustomerStorage $customerStorage,
        private readonly OrderStorage $orderStorage,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/quote', methods: ['POST'])]
    public function quote(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return new JsonResponse(
                ['error' => 'Invalid JSON body.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $quoteRequest = PriceQuoteRequest::fromArray($data);

        $violations = $this->validator->validate($quoteRequest);

        if ($violations->count() > 0) {
            $errors = [];

            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return new JsonResponse(
                ['errors' => $errors],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $customer = $this->customerStorage->getCustomer(
                $quoteRequest->customerId
            );

            $quote = $this->pricingService->calculate(
                $quoteRequest,
                $customer
            );

            $order = [
                'orderId' => bin2hex(random_bytes(8)),
                'createdAt' => (new \DateTimeImmutable())->format(
                    \DateTimeInterface::ATOM
                ),
                ...$quote,
            ];

            $this->orderStorage->save($order);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\RuntimeException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse(
            $order,
            Response::HTTP_CREATED
        );
    }
}