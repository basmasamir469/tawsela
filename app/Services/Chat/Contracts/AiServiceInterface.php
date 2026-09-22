<?php

namespace App\Services\Chat\Contracts;

Interface AiServiceInterface
{
    public function respond(string $message): string;
}