@vite(['resources/css/vue-app.css', 'resources/js/main.js'])
@if(config('app.production'))
    <script defer data-domain="gibpotato.app" src="https://plausible.io/js/script.js"></script>
@endif