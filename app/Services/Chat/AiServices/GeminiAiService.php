<?php

namespace App\Services\Chat\AiServices;

use App\Services\Chat\Contracts\AiServiceInterface;

class GeminiAiService implements AiServiceInterface
{
    public function respond(string $message): string
    {
        // Implement the logic to interact with the Gemini AI service and return a response.
        return "Gemini AI response to: " . $message;
    }
}