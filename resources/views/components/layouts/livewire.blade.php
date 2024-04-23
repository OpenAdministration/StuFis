<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale())}}" class="h-full bg-gray-50">
<head>
    <title>{{ $title ?? 'StuRa Finanzen' }}</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="{{ asset('img/logo.svg') }}">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    @livewireScripts
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body class="h-full overflow-hidden">
    {{ $slot }}
</body>
</html>
