<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PotatoAgent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'user_id' => ['required', 'exists:users,id'],
            'conversation_id' => ['nullable', 'string'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $agent = new PotatoAgent;

        if ($validated['conversation_id'] ?? null) {
            $agent = $agent->continue($validated['conversation_id'], as: $user);
        } else {
            $agent = $agent->forUser($user);
        }

        $response = $agent->prompt($validated['message']);

        return response()->json([
            'message' => (string) $response,
            'conversation_id' => $response->conversationId,
        ]);
    }
}
