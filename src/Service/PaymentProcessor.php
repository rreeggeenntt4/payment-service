<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class PaymentProcessor
{
    public function __construct(
        private LoggerInterface $logger,
        private TelegramNotifier $telegramNotifier
    ) {}

    public function handlePayment(array $data): array
    {
        $this->logger->info('Processing payment', $data);

        $status = $data['status'] ?? 'unknown';
        $userId = $data['user_id'] ?? null;
        $language = $data['language_code'] ?? 'en';

        if (!$userId) {
            return ['error' => 'User ID is missing'];
        }

        $isNewSubscription = $this->isNewSubscription($userId);

        // Формируем сообщение
        $message = $this->formatMessage($status, $isNewSubscription, $language);

        // Отправляем сообщение в Telegram
        $this->telegramNotifier->sendMessage($userId, $message);

        return [
            'message' => $isNewSubscription ? 'New subscription processed' : 'Subscription renewed',
            'status' => $status
        ];
    }

    private function isNewSubscription(string $userId): bool
    {
        return rand(0, 1) === 1; // Заглушка для проверки подписки
    }

    private function formatMessage(string $status, bool $isNew, string $lang): string
    {
        $messages = [
            'ru' => [
                'new_confirmed' => "🎉 *Поздравляем!* Ваша подписка успешно оформлена.",
                'renew_confirmed' => "🔄 *Подписка продлена!* Спасибо, что остаетесь с нами.",
                'new_rejected' => "⚠️ *Ошибка!* Оплата подписки не прошла.",
                'renew_rejected' => "⚠️ *Ошибка!* Не удалось продлить подписку."
            ],
            'en' => [
                'new_confirmed' => "🎉 *Congratulations!* Your subscription is now active.",
                'renew_confirmed' => "🔄 *Subscription renewed!* Thank you for staying with us.",
                'new_rejected' => "⚠️ *Error!* Subscription payment failed.",
                'renew_rejected' => "⚠️ *Error!* Subscription renewal failed."
            ]
        ];

        $key = ($isNew ? 'new' : 'renew') . "_$status";
        return $messages[$lang][$key] ?? "Unknown status.";
    }
}
