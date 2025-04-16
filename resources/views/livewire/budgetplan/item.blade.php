<flux:table.row>
    <flux:table.cell>
        @for($i = 0; $i < $level; $i++)
            <div class="w-3 h-10 bg-zinc-700"></div>
        @endfor
    </flux:table.cell>
    <flux:table.cell class="flex flex-col content-evenly h-full">
        <flux:icon.arrow-up class="w-5 h-5"/>
        <flux:icon.arrow-down class="w-5 h-5"/>
    </flux:table.cell>
    <flux:table.cell>
        <flux:switch wire:model.live="is_group" />
    </flux:table.cell>
    <flux:table.cell>
        <flux:select wire:model="is_expense">
            <flux:select.option value="false" label="Einnahme" />
            <flux:select.option value="true" label="Ausgabe" />
        </flux:select>
    </flux:table.cell>
    <flux:table.cell>
        <flux:input wire:model="short_name"/>
    </flux:table.cell>
    <flux:table.cell>
        <flux:input wire:model="name"/>
    </flux:table.cell>
    <flux:table.cell>
        @if($is_group)
            Σ 5 €
        @else
            <flux:input type="number" wire:model="value"/>
        @endif
    </flux:table.cell>
</flux:table.row>
