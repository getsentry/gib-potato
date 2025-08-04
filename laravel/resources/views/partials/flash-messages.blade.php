@if(session('success'))
    @include('partials.flash.success', ['message' => session('success')])
@endif

@if(session('error'))
    @include('partials.flash.error', ['message' => session('error')])
@endif

@if(session('info'))
    @include('partials.flash.default', ['message' => session('info')])
@endif

@if(session('status'))
    @include('partials.flash.default', ['message' => session('status')])
@endif