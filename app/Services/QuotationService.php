<?php

namespace App\Services;

use App\Contracts\WhatsAppLinkGenerator;

class QuotationService
{
    public function __construct(
        private WhatsAppLinkGenerator $whatsapp
    ) {}

    public function canQuote(int $quantity, int $moq): bool
    {
        return $quantity >= $moq;
    }

    public function generateWhatsAppLink(string $phone, string $message): string
    {
        return $this->whatsapp->generate($phone, $message);
    }
}
