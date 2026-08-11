<?php

namespace App\Contracts;

interface WhatsAppGroupProviderInterface
{
    public function status(): array;

    public function groups(): array;

    public function qr(): array;

    public function sendGroupMessage(string $groupId, string $message): array;
}
