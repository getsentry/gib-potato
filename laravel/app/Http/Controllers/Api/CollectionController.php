<?php

namespace App\Http\Controllers\Api;

use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends ApiController
{
    /**
     * Get user's collection of purchases
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Get purchases that are either:
        // 1. Self-purchases (user_id = current user AND presentee_id is null)
        // 2. Gifts received (presentee_id = current user AND user_id != current user)
        $collection = Purchase::query()
            ->where(function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    // Self purchases
                    $q->where('user_id', $user->id)
                        ->whereNull('presentee_id');
                })->orWhere(function ($q) use ($user) {
                    // Gifts received from others
                    $q->where('user_id', '!=', $user->id)
                        ->where('presentee_id', $user->id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($collection);
    }
}