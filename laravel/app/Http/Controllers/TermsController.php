<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TermsController extends Controller
{
    /**
     * Display the terms page
     *
     * @return View
     */
    public function index(): View
    {
        return view('terms');
    }
}