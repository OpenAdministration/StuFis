<x-layout::app size="xs">
    <flux:heading size="xl">{{ __('general.changelog.headline') }}</flux:heading>
    <flux:text class="mb-4">{{ __('general.changelog.sub-headline') }}</flux:text>
    <flux:button variant="primary" :href="route('git-repo')" icon="code-bracket" target="_blank">{{ __('general.changelog.github-button') }}</flux:button>
    {!! markdownToHtml($changelogs) !!}
</x-layout::app>
