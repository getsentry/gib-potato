<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the home page (SPA entry point)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Add Document-Policy header for JS profiling
        return response()
            ->view('home')
            ->header('Document-Policy', 'js-profiling');
    }
}