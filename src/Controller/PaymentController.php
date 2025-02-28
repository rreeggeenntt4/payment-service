<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PaymentProcessor;

class PaymentController extends AbstractController
{
    #[Route('/payment', methods: ['POST'])]
    public function processPayment(Request $request, PaymentProcessor $paymentProcessor): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $result = $paymentProcessor->handlePayment($data);

        return new JsonResponse($result);
    }
}
