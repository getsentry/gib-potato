<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySlackSignature
{
    /**
     * Handle an incoming request from Slack.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');
        
        if (!$timestamp || !$signature) {
            return response()->json([
                'error' => 'Missing required Slack headers',
            ], 401);
        }
        
        // Verify the request is not too old (5 minutes)
        if (abs(time() - $timestamp) > 300) {
            return response()->json([
                'error' => 'Request timestamp is too old',
            ], 401);
        }
        
        // Calculate the expected signature
        $signingSecret = config('services.slack.signing_secret');
        $requestBody = $request->getContent();
        $sigBasestring = "v0:{$timestamp}:{$requestBody}";
        $mySignature = 'v0=' . hash_hmac('sha256', $sigBasestring, $signingSecret);
        
        // Compare signatures
        if (!hash_equals($mySignature, $signature)) {
            return response()->json([
                'error' => 'Invalid request signature',
            ], 401);
        }
        
        return $next($request);
    }
}