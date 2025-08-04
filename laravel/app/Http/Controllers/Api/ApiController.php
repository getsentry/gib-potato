<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // All API routes already have auth:sanctum middleware applied via routes/api.php
        // Additional middleware can be added here if needed
    }
}