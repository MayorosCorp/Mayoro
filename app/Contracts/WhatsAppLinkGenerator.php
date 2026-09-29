<?php

namespace App\Contracts;

interface WhatsAppLinkGenerator
{
    public function generate(string $phone, string $message): string;
}
