<x-layout class="p-8">
    <div class="max-w-3xl">
        <flux:heading size="xl">{{ __('general.changelog.headline') }}</flux:heading>
        <flux:text class="mb-4">{{ __('general.changelog.sub-headline') }}</flux:text>
        <flux:button variant="primary" :href="route('git-repo')" icon="code-bracket" target="_blank">View Github page</flux:button>
        {!! markdownToHtml($changelogs) !!}
    </div>
</x-layout>
