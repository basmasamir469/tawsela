<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\users\ChatRequest;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{

    public function __construct(protected ChatService $chatService)
    {

    }
    public function supportChat(ChatRequest $request)
    {
        $response    = $this->chatService->supportChat($request->validated());

        return $this->dataResponse($response);
    }
}
