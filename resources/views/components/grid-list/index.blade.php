<ul {{ $attributes->merge([
    'role' => 'list',
    'class' => "py-3 grid grid-cols-1 gap-x-2 gap-y-8 md:gap-y-4 md:grid-cols-2 lg:gap-y-6 xl:grid-cols-3 xl:gap-x-8"
]) }}>
<!--ul role="list" class="py-3 flex flex-wrap gap-x-2 gap-y-8 md:gap-y-4 lg:gap-y-6 xl:gap-x-8"> -->
    {{ $slot }}
</ul>
