<?php

namespace App\Http\Controllers\Api;

use App\Models\QuickWin;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class QuickWinsController extends ApiController
{
    /**
     * Get all quick wins
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $quickWins = QuickWin::query()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Extract all user IDs from messages
        $collectedUserIds = [];
        foreach ($quickWins as $quickWin) {
            // Extract all user IDs from message: "<@U042CECCR7A> has tagged you in a message"
            preg_match_all('/<@([A-Z0-9]+)>/', $quickWin->message, $matches);
            foreach ($matches[1] as $match) {
                $collectedUserIds[] = $match;
            }
        }

        $collectedUserIds = array_unique($collectedUserIds);

        // Get all users mentioned in messages
        $users = [];
        if (!empty($collectedUserIds)) {
            $users = User::query()
                ->whereIn('slack_user_id', $collectedUserIds)
                ->get()
                ->keyBy('slack_user_id');
        }

        // Replace user IDs with user names in messages
        foreach ($quickWins as $quickWin) {
            foreach ($users as $user) {
                $quickWin->message = str_replace(
                    "<@{$user->slack_user_id}>",
                    "<@{$user->slack_name}>",
                    $quickWin->message
                );
            }
        }

        return response()->json($quickWins);
    }
}