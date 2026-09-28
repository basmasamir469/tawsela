<?php

namespace App\Services\Chat;

use App\Factories\IntentionFactory;
use App\Services\Chat\Contracts\AiServiceInterface;

class ChatService
{
    public function __construct(private AiServiceInterface $aiService)
    {
    }
    public function supportChat(array $data)
    {
        try
        {
            $result =  $this->aiService->detectIntent($data['message']);

            return IntentionFactory::make($result)->sendResponse($result);
        }
        catch (\Throwable $th)
        {
            return ['error' => $th->getMessage(), 'code' => 400];
        }
       
    }
}