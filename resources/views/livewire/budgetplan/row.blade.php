
<div>
    <!-- first position of this category -->
    <x-antrag.table.row.posten name="einnahmen" id="{{ $topic }}.{{ $position }}" wire:model="values.0"/>

    @for($position = 2,$iMax = count($values)+1;$position < $iMax; $position++)
        <x-antrag.table.row.posten name="einnahmen" id="{{ $topic }}.{{ $position }}" wire:model="values.{{ $position-1 }}"/>
        <!-- delete button -->
    @endfor
    <!-- add new position button -->
    <x-antrag.table.row.link href="#" wire:click="addValue()">Posten hinzufügen</x-antrag.table.row.link>
</div>
