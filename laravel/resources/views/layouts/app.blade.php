<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GibPotato') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/css/vue-app.css', 'resources/js/app.js'])
</head>
<body 
    class="font-sans antialiased bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-50"
    @auth
        data-username="{{ auth()->user()->slack_name ?? '' }}"
        data-sentry-frontend-dsn="{{ config('sentry.frontend_dsn') }}"
        data-sentry-environment="{{ config('app.env') }}"
        data-sentry-release="{{ config('sentry.release') }}"
    @endauth
>
    <div id="app">
        @yield('content')
    </div>
</body>
</html>