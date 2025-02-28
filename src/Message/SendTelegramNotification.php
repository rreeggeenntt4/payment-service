<?php

namespace App\Message;

class SendTelegramNotification
{
    public function __construct(
        private string $userId,
        private string $message
    ) {}

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
