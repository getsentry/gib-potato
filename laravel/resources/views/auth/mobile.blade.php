@extends('layouts.app')

@section('content')
<div class="h-full flex items-center justify-center">
    <div class="max-w-sm text-center">
        <img src="{{ asset('img/logo.png') }}" class="w-32 mb-4 mx-auto rounded-full" alt="GibPotato Logo">

        <h1 class="text-center text-3xl font-bold mb-16">
            GibPotato
        </h1>

        <div id="token" class="hidden">
            {{ $token }}
        </div>
    </div>
</div>
@endsection