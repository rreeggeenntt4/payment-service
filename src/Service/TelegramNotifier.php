<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class TelegramNotifier
{
    private string $botToken = '123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZ'; // Заменить на токен бота
    private string $apiUrl = 'https://api.telegram.org/bot';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    public function sendMessage(string $chatId, string $message): void
    {
        $url = "{$this->apiUrl}{$this->botToken}/sendMessage";
        $params = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'MarkdownV2'
        ];

        try {
            $this->httpClient->request('POST', $url, [
                'json' => $params
            ]);
            $this->logger->info("Telegram message sent to {$chatId}");
        } catch (\Exception $e) {
            $this->logger->error("Telegram send failed: " . $e->getMessage());
        }
    }
}
