@props([
    'user' => Auth::user(),
    'size' => "md"
])

<flux:avatar :name="$user->name" color="auto" circle :src="$user->picture_url" :size="$size" />


