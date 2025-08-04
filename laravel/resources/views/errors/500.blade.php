@extends('layouts.error')

@section('title', '500 | Internal Server Error')

@section('content')
<div class="h-full flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-2xl font-semibold">
            500 | Internal Server Error
        </h1>
        @if(app('sentry') && method_exists(app('sentry'), 'getLastEventId') && app('sentry')->getLastEventId())
            <p class="mt-4 text-sm text-gray-600">
                {{ app('sentry')->getLastEventId() }}
            </p>
        @endif
    </div>
</div>
@endsection