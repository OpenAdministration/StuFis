<li class="grow lg:last:grow-0 overflow-hidden rounded-xl border border-gray-200" {{ $attributes->merge() }}>
    <div class="w-full items-center justify-between gap-x-4 border-b border-gray-900/5 bg-gray-50 p-6 text-sm font-medium leading-6 text-gray-900">
        {{ $slot }}
    </div>
    @if($rows->hasActualContent())
        <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">
            {{ $rows }}
        </dl>
    @endif
</li>
