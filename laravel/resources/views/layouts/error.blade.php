<!DOCTYPE html>
<html class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @yield('meta')
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>
        @yield('title', 'Error')
    </title>
    @include('partials.assets')
    @yield('css')
</head>
<body class="h-full bg-gray-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-50 font-mono">
    @yield('content')
</body>
</html>