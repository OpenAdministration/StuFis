<x-layout::app flush>
    @isset ($sectionTabs)
        <x-slot:tabs>
            <flux:tabs class="px-4 sm:px-6 lg:px-8">
                @foreach ($sectionTabs as $tab)
                    <flux:tab :href="$tab['href']" :icon="$tab['icon']" :selected="$tab['active']" wire:navigate>
                        {{ $tab['label'] }}
                    </flux:tab>
                @endforeach
            </flux:tabs>
        </x-slot:tabs>
    @endisset

    <iframe srcdoc="{!! htmlspecialchars($content) !!}"
            width="100%" height="100%" id="legacy-iframe"></iframe>
</x-layout::app>
