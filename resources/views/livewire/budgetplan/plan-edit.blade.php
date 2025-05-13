<flux:main class="space-y-10">
    <div>
        <flux:heading size="lg">New Budget Plan</flux:heading>
        <flux:text class="mt-2">Some explanatory Text</flux:text>
    </div>

    <flux:fieldset class="max-w-3xl">
        <div class="grid grid-cols-2 gap-x-16 gap-y-6">
            <flux:input wire:model.blur="organization" label="Organisation" type="text" description="Just type Stura"/>
            <flux:select wire:model.blur="fiscal_year_id" label="Fiscal Year" placeholder="nothing to see here :(" description="Add it somewhere else">
                @foreach($fiscal_years as $fiscal_year)
                    <flux:select.option :wire:key="$fiscal_year->id">{{ $fiscal_year->start_date->format('d.m.y') }} - {{ $fiscal_year->end_date->format('d.m.y') }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.blur="resolution_date" badge="Optional" label="Resolution Date" type="date"/>
            <flux:input wire:model.blur="approval_date" badge="Optional" label="Approval Date" type="date"/>
        </div>
    </flux:fieldset>

    <flux:fieldset>
        <flux:legend>Einnahmen</flux:legend>
        <div class="grid grid-cols-10 gap-4">
            <div class="col-span-8 grid grid-cols-subgrid gap-4">
                <div class="col-start-2">Shortname</div>
                <div class="col-start-4">Name</div>
                <div class="col-start-6">Value</div>
            </div>
            <div class="col-span-8 grid grid-cols-subgrid gap-4" x-sort="$wire.sort($item, $position)">
                @foreach($items_in as $key => $item)
                    <livewire:budgetplan.item :item_id="$item->id" :wire:key="$item->id"/>
                @endforeach
            </div>
            <div class="col-start-1 col-span-4 grid grid-cols-subgrid gap-4">
                <flux:button wire:click="addGroup(1)" variant="subtle">+ New Group</flux:button>
            </div>
        </div>
    </flux:fieldset>

    <flux:fieldset>
        <flux:legend>Ausgaben</flux:legend>
        <div class="grid grid-cols-10 gap-4" x-sort>
            <div class="col-span-8 grid grid-cols-subgrid gap-4">
                <div class="col-start-2">Shortname</div>
                <div class="col-start-4">Name</div>
                <div class="col-start-6">Value</div>
            </div>
            <div class="col-span-8 grid grid-cols-subgrid gap-4" x-sort>
                @foreach($items_out as $key => $item)
                    <livewire:budgetplan.item :item_id="$item->id" :wire:key="$item->id"/>
                @endforeach
            </div>
            <div class="col-start-1 col-span-4 grid grid-cols-subgrid gap-4">
                <flux:button wire:click="addGroup(-1)" variant="subtle">+ New Group</flux:button>
            </div>
        </div>
    </flux:fieldset>
    <div>
        <flux:button wire:click="save" variant="primary">Save</flux:button>
        <flux:text variant="subtle">
            Last saved yesterday
        </flux:text>
    </div>
</flux:main>
