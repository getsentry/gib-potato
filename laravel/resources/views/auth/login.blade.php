@extends('layouts.app')

@section('content')
<div class="h-full flex items-center justify-center">
    <div class="max-w-sm text-center">
        <img src="{{ asset('img/logo.png') }}" class="w-32 mb-4 mx-auto rounded-full" alt="GibPotato Logo">

        <h1 class="text-center text-3xl font-bold mb-16">
            GibPotato
        </h1>

        <a href="{{ route('slack.redirect') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-semibold rounded-md text-zinc-900 bg-amber-200">
            Sign in with Slack
        </a>
    </div>
</div>
@endsection