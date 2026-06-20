@props([
    'user' => Auth::user(),
    'size' => "md"
])

{{-- $user can be null: chat messages whose `creator` username no longer maps to a User,
     or an unauthenticated render. Fall back to a neutral avatar instead of 500ing. --}}
<flux:avatar :name="$user?->name ?? '?'" color="auto" circle :src="$user?->picture_url" :size="$size" />


