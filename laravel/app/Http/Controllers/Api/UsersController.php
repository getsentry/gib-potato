<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends ApiController
{
    /**
     * List all active users
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $users = User::query()
            ->where('slack_is_bot', false)
            ->where('status', User::STATUS_ACTIVE)
            ->where('role', '!=', User::ROLE_SERVICE)
            ->orderBy('slack_name')
            ->get();

        return response()->json($users);
    }

    /**
     * Get current authenticated user with stats
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        
        // Load user with progression and calculate counts
        $userData = User::query()
            ->with('progression')
            ->withCount([
                'messagesSent as sent_count' => function ($query) {
                    $query->select(DB::raw('COALESCE(SUM(amount), 0)'));
                },
                'messagesReceived as received_count' => function ($query) {
                    $query->select(DB::raw('COALESCE(SUM(amount), 0)'));
                }
            ])
            ->find($user->id);

        // Add calculated fields
        $userData->spendable_count = $userData->getSpendablePotato();
        $userData->potato_sent_today = $userData->getPotatoSentToday();
        $userData->potato_left_today = $userData->getPotatoLeftToday();
        $userData->potato_reset_in_hours = $userData->getPotatoResetInHours();
        $userData->potato_reset_in_minutes = $userData->getPotatoResetInMinutes();

        return response()->json($userData);
    }

    /**
     * Update user preferences
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'notifications.sent' => 'sometimes|boolean',
            'notifications.received' => 'sometimes|boolean',
            'notifications.too_good_to_go' => 'sometimes|boolean',
        ]);

        /** @var User $user */
        $user = $request->user();

        // Update notifications preferences
        if ($request->has('notifications')) {
            $notifications = $user->notifications ?? [];
            
            if ($request->has('notifications.sent')) {
                $notifications['sent'] = (bool) $request->input('notifications.sent');
            }
            if ($request->has('notifications.received')) {
                $notifications['received'] = (bool) $request->input('notifications.received');
            }
            if ($request->has('notifications.too_good_to_go')) {
                $notifications['too_good_to_go'] = (bool) $request->input('notifications.too_good_to_go');
            }

            $user->notifications = $notifications;
            $user->save();
        }

        return response()->json([], 204);
    }

    /**
     * Get user's message history for profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $messages = Message::query()
            ->with(['sender', 'receiver'])
            ->where(function ($query) use ($user) {
                $query->where('sender_user_id', $user->id)
                    ->orWhere('receiver_user_id', $user->id);
            })
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($messages);
    }
}