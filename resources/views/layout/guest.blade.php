@props([
    'title' => 'StuFiS - Finanzen',
])

{{-- Minimal shell with no app chrome: safe for users without a usable app session
     (e.g. authenticated but not in the login/admin group). Do not add nav, profile
     menu, @can checks or anything that assumes a fully authorized Auth::user(). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <title>{{ $title }}</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="{{ asset('img/logo.svg') }}">
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-gray-50">
    <main class="flex min-h-screen items-center justify-center p-6">
        {{ $slot }}
    </main>
</body>
</html>
