<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class PaymentProcessor
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function handlePayment(array $data): array
    {
        $this->logger->info('Processing payment', $data);

        $status = $data['status'] ?? 'unknown';
        $userId = $data['user_id'] ?? null;

        if (!$userId) {
            return ['error' => 'User ID is missing'];
        }

        // Определяем, новая подписка или продление
        $isNewSubscription = $this->isNewSubscription($userId);

        // Логика обработки платежа
        return [
            'message' => $isNewSubscription ? 'New subscription processed' : 'Subscription renewed',
            'status' => $status
        ];
    }

    private function isNewSubscription(string $userId): bool
    {
        // В реальном проекте тут будет проверка в БД
        return rand(0, 1) === 1; // Для теста возвращаем случайное значение
    }
}
