<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale())}}">
<head>
    <title>{{ $title ?? 'StuRa Finanzen' }}</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="/css/app.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col flex-auto flex-shrink-0 antialiased bg-white dark:bg-gray-700 text-black dark:text-white">
<div class="dark">
    <livewire:nav/>
</div>
<div class="p-8 h-full ml-14 mt-14 mb-10 md:ml-64 antialiased bg-white dark:bg-gray-700 text-black dark:text-white">
    <h1 class="text-4xl mb-4">{{ $title }}</h1>
    {{ $slot }}
</div>
<script src="{{ mix('js/app.js') }}"></script>
@livewireScripts
</body>
</html>
