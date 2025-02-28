<?php

namespace App\MessageHandler;

use App\Message\SendTelegramNotification;
use App\Service\TelegramNotifier;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class SendTelegramNotificationHandler
{
    public function __construct(
        private TelegramNotifier $telegramNotifier,
        private LoggerInterface $logger
    ) {}

    public function __invoke(SendTelegramNotification $message): void
    {
        $this->logger->info("Processing Telegram notification for user {$message->getUserId()}");
        
        $this->telegramNotifier->sendMessage(
            $message->getUserId(),
            $message->getMessage()
        );
    }
}
