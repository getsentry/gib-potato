<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderBoardController extends ApiController
{
    /**
     * Get the leaderboard data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Determine the date range
        $range = $request->query('range', 'all');
        $rangeDate = match ($range) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'quarter' => Carbon::now()->startOfQuarter(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::parse('2022-08-24 00:00:00'), // Default to project start date
        };

        // Build the query with sent and received counts
        $query = User::query()
            ->select([
                'users.*',
                DB::raw('COALESCE(sent.total, 0) as sent_count'),
                DB::raw('COALESCE(received.total, 0) as received_count'),
            ])
            ->leftJoin(
                DB::raw('(SELECT sender_user_id, SUM(amount) as total FROM messages WHERE created_at >= ? GROUP BY sender_user_id) as sent'),
                'users.id',
                '=',
                'sent.sender_user_id'
            )
            ->leftJoin(
                DB::raw('(SELECT receiver_user_id, SUM(amount) as total FROM messages WHERE created_at >= ? GROUP BY receiver_user_id) as received'),
                'users.id',
                '=',
                'received.receiver_user_id'
            )
            ->where('users.slack_is_bot', false)
            ->where('users.status', User::STATUS_ACTIVE)
            ->where('users.role', '!=', User::ROLE_SERVICE)
            ->addBinding([$rangeDate, $rangeDate], 'join');

        // Apply ordering
        $order = $request->query('order');
        switch ($order) {
            case 'sent':
                $query->orderByRaw('sent_count DESC NULLS LAST');
                break;
            case 'received':
                $query->orderByRaw('received_count DESC NULLS LAST');
                break;
            default:
                $query->orderBy('users.slack_name');
        }

        $users = $query->get();

        return response()->json($users);
    }
}