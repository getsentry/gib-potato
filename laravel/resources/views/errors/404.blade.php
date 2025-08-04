@extends('layouts.error')

@section('title', '404 | Not Found')

@section('content')
<div class="h-full flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-2xl font-semibold">
            404 | Not Found
        </h1>
        @if(app('sentry') && method_exists(app('sentry'), 'getLastEventId') && app('sentry')->getLastEventId())
            <p class="mt-4 text-sm text-gray-600">
                {{ app('sentry')->getLastEventId() }}
            </p>
        @endif
    </div>
</div>
@endsection