{{-- Auth gate failure (not in login/admin group): no usable app session,
     so render the chrome-free guest layout instead of <x-layout::error>. --}}
<x-layout::guest>
    <flux:card class="w-full max-w-md text-center">
        <x-logo class="mx-auto h-14 w-auto"/>
        <flux:badge color="red" size="sm" class="mt-6">Fehler 401</flux:badge>
        <flux:heading size="xl" class="mt-3">{{ __('errors.401.title') }}</flux:heading>
        <flux:text class="mt-2">{{ __('errors.401.subtitle') }}</flux:text>
        <flux:button :href="route('logout')" variant="primary" icon="arrow-right-start-on-rectangle" class="mt-8 w-full">
            {{ __('errors.logout') }}
        </flux:button>
    </flux:card>
</x-layout::guest>
