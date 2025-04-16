<flux:main class="space-y-10">
    <div>
        <flux:heading size="lg">New Budget Plan</flux:heading>
    </div>

    <flux:fieldset>
        <flux:legend>Meta Daten</flux:legend>
        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="organization" label="Organisation" type="text" description="Just type Stura"/>
            <flux:select wire:model="fiscal_year_id" label="Fiscal Year" placeholder="nothing to see here :(" description="Add it somewhere else">
                @foreach($fiscal_years as $fiscal_year)
                    <flux:select.option :wire:key="$fiscal_year->id">{{ $fiscal_year->start_date->format('d.m.y') }} - {{ $fiscal_year->end_date->format('d.m.y') }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="resolution_date" badge="Optional" label="Resolution Date" type="date"/>
            <flux:input wire:model="approval_date" badge="Optional" label="Approval Date" type="date"/>
        </div>
    </flux:fieldset>

    <flux:fieldset>
        <flux:legend>Budget Items</flux:legend>
        <flux:table>
            <flux:table.columns>
                <flux:table.column></flux:table.column><!--Level-->
                <flux:table.column></flux:table.column><!--Arrows-->
                <flux:table.column>Group</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Number</flux:table.column>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Value</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                <livewire:budgetplan.item is_group="true"/>
                <livewire:budgetplan.item level="1"/>
                <livewire:budgetplan.item/>
            </flux:table.rows>
        </flux:table>

    </flux:fieldset>
</flux:main>
