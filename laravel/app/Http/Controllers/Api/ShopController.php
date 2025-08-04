<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use App\Services\SlackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends ApiController
{
    /**
     * List all available products
     *
     * @return JsonResponse
     */
    public function products(): JsonResponse
    {
        $products = Product::query()
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }

    /**
     * Purchase a product
     *
     * @param Request $request
     * @param SlackService $slackService
     * @return JsonResponse
     */
    public function purchase(Request $request, SlackService $slackService): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'purchase_mode' => 'required|in:myself,someone-else',
            'presentee_id' => 'required_if:purchase_mode,someone-else|nullable|uuid|exists:users,id',
            'message' => 'required_if:purchase_mode,someone-else|nullable|string|max:500',
        ]);

        /** @var User $user */
        $user = $request->user();

        // Get the product
        $product = Product::find($request->input('product_id'));

        // Check stock
        if ($product->stock < 1) {
            return response()->json([
                'error' => 'Product out of stock 😥'
            ], 400);
        }

        // Check user balance
        if ($product->price > $user->getSpendablePotato()) {
            return response()->json([
                'error' => 'Not enough potato to buy 😥'
            ], 400);
        }

        // Get presentee if purchasing for someone else
        $presentee = null;
        if ($request->input('purchase_mode') === 'someone-else') {
            $presentee = User::find($request->input('presentee_id'));
            
            if (!$presentee) {
                return response()->json([
                    'error' => 'Select someone 🧐'
                ], 400);
            }

            if (empty($request->input('message'))) {
                return response()->json([
                    'error' => 'Add a message 🧐'
                ], 400);
            }
        }

        // Create purchase in transaction
        DB::transaction(function () use ($user, $product, $presentee, $request) {
            // Create purchase record
            Purchase::create([
                'user_id' => $user->id,
                'presentee_id' => $presentee?->id,
                'name' => $product->name,
                'description' => $product->description,
                'image_link' => $product->image_link,
                'price' => $product->price,
                'message' => $request->input('message'),
            ]);

            // Decrease product stock
            $product->decrement('stock');
        });

        // Send Slack notification if it's a gift
        if ($presentee) {
            $blocks = [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "<@{$user->slack_user_id}> did buy a nice little present for <@{$presentee->slack_user_id}> 🎁😊",
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "They got them *{$product->name}* 🚀",
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "_{$request->input('message')}_",
                    ],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => '<' . route('shop') . '|Gib a present to a fellow Sentaur yourself!>',
                    ],
                ],
                [
                    'type' => 'image',
                    'image_url' => url(str_replace('.svg', '.png', $product->image_link)),
                    'alt_text' => $product->name,
                    'title' => [
                        'type' => 'plain_text',
                        'text' => $product->name,
                    ],
                ],
            ];

            $slackService->postMessage(
                channel: config('services.slack.potato_channel'),
                blocks: $blocks
            );
        }

        return response()->json([], 204);
    }
}