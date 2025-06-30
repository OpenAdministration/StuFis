<flux:main class="space-y-10">
    <div>
        <flux:heading size="lg">New Budget Plan</flux:heading>
        <flux:text class="mt-2">Some explanatory Text</flux:text>
    </div>

    <flux:fieldset class="max-w-3xl">
        <div class="grid grid-cols-2 gap-x-16 gap-y-6">
            <flux:input wire:model.blur="organization" label="Organisation" type="text" description="Just type Stura"/>
            <flux:select wire:model.change="fiscal_year_id" label="Fiscal Year" description="Add it somewhere else">
                <flux:select.option wire:key="fiscal-0">None</flux:select.option>
                @foreach($fiscal_years as $fiscal_year)
                    <flux:select.option wire:key="fiscal-{{$fiscal_year->id}}" value="{{$fiscal_year->id}}">{{ $fiscal_year->start_date->format('d.m.y') }} - {{ $fiscal_year->end_date->format('d.m.y') }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.blur="resolution_date" badge="Optional" label="Resolution Date" type="date"/>
            <flux:input wire:model.blur="approval_date" badge="Optional" label="Approval Date" type="date"/>
        </div>
        <div>SUM IN</div>
        <div>SUM OUT</div>
    </flux:fieldset>
    <flux:tab.group class="max-w-7xl">
        <flux:tabs >
            <flux:tab name="in">Einnahmen</flux:tab>
            <flux:tab name="out">Ausgaben</flux:tab>
        </flux:tabs>
        @foreach(\App\Models\Enums\BudgetType::cases() as $budgetType)
            <flux:tab.panel :name="$budgetType->slug()">
                <flux:fieldset>
                    <div class="grid grid-cols-8">
                        <div class="col-span-8 grid grid-cols-subgrid gap-4">
                            <div class="col-start-2 flex items-center gap-2">
                                Label
                                <flux:tooltip toggleable>
                                    <flux:button icon="information-circle" size="sm" variant="subtle" />
                                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                        Very nice content
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                            <div class="col-start-4 flex items-center gap-2">
                                Label
                                <flux:tooltip toggleable>
                                    <flux:button icon="information-circle" size="sm" variant="subtle" />
                                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                        Very nice content
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                            <div class="col-start-6 flex items-center gap-2">
                                Label
                                <flux:tooltip toggleable>
                                    <flux:button icon="information-circle" size="sm" variant="subtle" />
                                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                        Very nice content
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                        </div>
                        <div class="col-span-8 grid grid-cols-subgrid gap-x-4"
                             x-sort="$wire.sort($item, $position)">
                            @foreach($root_items[$budgetType->slug()] as $id)
                                <x-item-group :item="$all_items[$id]" :wire:key="$id" :category="$budgetType->slug()"/>
                            @endforeach
                        </div>
                        <div class="col-start-1 col-span-4 grid grid-cols-subgrid gap-4">
                            <flux:button wire:click="addGroup(1)" variant="subtle">+ New Group</flux:button>
                        </div>
                    </div>
                </flux:fieldset>
            </flux:tab.panel>
        @endforeach
    </flux:tab.group>
    <div>
        <flux:button wire:click="save" variant="primary">Save</flux:button>
        <flux:text variant="subtle">
            Last saved yesterday
        </flux:text>
    </div>
    @dump($all_items)
    @dump($items)
</flux:main>
