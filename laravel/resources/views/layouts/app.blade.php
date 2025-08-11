<!DOCTYPE html>
<html class="h-full min-w-[320px]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Sentry tracing meta tags - can be added when distributed tracing is needed --}}
    {!! \Sentry\Laravel\Integration::sentryMeta() !!}
    
    @yield('meta')
    
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>
        @yield('title', config('app.name', 'GibPotato'))
    </title>
    
    @include('partials.assets')
    @yield('scripts')
    @yield('css')
</head>
<body
    class="h-full bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-50 font-mono"
    data-sentry-frontend-dsn="{{ config('sentry.frontend_dsn') }}"
    data-sentry-environment="{{ config('app.env') }}"
    data-sentry-release="{{ config('sentry.release') }}"
    @auth
        data-username="{{ auth()->user()->slack_name ?? '' }}"
    @endauth
>
    @yield('content')
    @include('partials.footer')
</body>
</html>