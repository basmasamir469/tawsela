<?php

namespace App\Services\Chat\Contracts;

Interface IntentionToolInterface
{
    public function sendResponse(array $data);
}