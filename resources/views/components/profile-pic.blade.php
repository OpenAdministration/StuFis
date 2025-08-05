@props([
    'user' => Auth::user(),
])

<flux:avatar :name="$user->name" color="auto" circle :src="$user->picture_url"/>


