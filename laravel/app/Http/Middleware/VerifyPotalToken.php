<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPotalToken
{
    /**
     * Handle an incoming request from the potal service.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');
        
        if (!$token) {
            return response()->json([
                'error' => 'Missing authorization token',
            ], 401);
        }
        
        // Remove 'Bearer ' prefix if present
        $token = str_replace('Bearer ', '', $token);
        
        // Verify the token matches the configured potal token
        if ($token !== config('services.potal.token')) {
            return response()->json([
                'error' => 'Invalid authorization token',
            ], 401);
        }
        
        // Optionally, you can add the service identifier to the request
        $request->merge(['service' => 'potal']);
        
        return $next($request);
    }
}