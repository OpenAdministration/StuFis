@props([
    'key' => null,
])

<tr @if ($key) wire:key="table-{{ $key }}" @endif {{ $attributes->merge(['class' => 'bg-zinc-200']) }} data-flux-row>
    <th scope="colgroup" colspan="5" class="bg-zinc-200 pl-3 py-1.5 text-left text-sm font-semibold text-zinc-900 ">{{ $slot }}</th>
</tr>
