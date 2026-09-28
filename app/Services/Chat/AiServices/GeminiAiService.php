<?php

namespace App\Services\Chat\AiServices;

use App\Services\Chat\Contracts\AiServiceInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiAiService implements AiServiceInterface
{
    public function respond(string $message): string
    {
        $apiKey = config('services.gemini.api_key');
        // Here you would implement the logic to send the message to the Gemini AI API

        $response = Http::withHeaders([
             'x-goog-api-key'  => $apiKey,
             'Content-Type'    => 'application/json',
        ])->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
            [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $message,
                            ],
                        ],
                    ],
                ],
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gemini API request failed: ' . $response->body()
            );
        }

        return $response->json(
            'candidates.0.content.parts.0.text',
            'Sorry, I could not generate a response.'
        );     
    }

    public function detectIntent(string $message): array
    {
       $prompt = "
         You are an AI assistant for Tawsela.

        Classify the user's message into exactly one of these intents:

        - order_status
        - driver_location
        - driver_info
        - cancel_order
        - cancellation_policy
        - privacy_policy
        - driver_late
        - general

        Extract the order_id only if the user explicitly mentions an order number.

        If no order number is mentioned, return null for order_id.

        Return JSON only.

        Examples:

        User message:
        What is the status of order 125?

        Response:
        {
            \"intent\": \"order_status\",
            \"order_id\": 125
        }

        User message:
        Where is my driver?

        Response:
        {
            \"intent\": \"driver_location\",
            \"order_id\": null
        }

        User message:
        What is your cancellation policy?

        Response:
        {
            \"intent\": \"cancellation_policy\",
            \"order_id\": null
        }

        User message:
        {$message}
    ";

        $apiKey = config('services.gemini.api_key');

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
            'Content-Type'   => 'application/json',
        ])->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
            [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gemini API request failed: ' . $response->body()
            );
        }

        $jsonResponse = $response->json('candidates.0.content.parts.0.text', '{}');
        $decodedResponse = json_decode($jsonResponse, true);

        return [
            'intent' => $decodedResponse['intent'] ?? 'general',
            'order_id' => $decodedResponse['order_id'] ?? null,
            'user_id'  => request()->user()->id ?? null
        ];}
}