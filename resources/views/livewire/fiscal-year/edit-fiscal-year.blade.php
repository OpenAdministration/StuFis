<div class="p-8">
    <x-intro>
        Test
        <x-slot:subHeadline>
            Lorem ipsum
        </x-slot:subHeadline>
    </x-intro>
    <div class="max-w-64 space-y-4">
        <flux:input wire:model="start_date" label="Start" type="date"/>
        <flux:input wire:model="end_date" label="Ende" type="date" badge="Optional"/>
    </div>
    <div class="mt-6">
        <flux:button variant="primary" wire:click="save">
            Speichern
        </flux:button>
    </div>

</div>
