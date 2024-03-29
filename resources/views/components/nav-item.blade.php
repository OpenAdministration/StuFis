@props([
    'mobile' => false,
    'href' => '/',
    'icon',
    'active' => false,
])

<a href="{{ $href }}"
   {{ $attributes->class([
        "group rounded-md flex rounded-md flex items-center font-medium",
        "text-indigo-100 hover:bg-indigo-800 hover:text-white" => !$active,
        "bg-indigo-800 text-white" => $active,
        "w-full p-3 flex-col text-xs " => !$mobile,
        "py-2 px-3 text-sm font-medium" => $mobile,

   ]) }}
>
    @isset($icon)
    <x-dynamic-component :component="$icon"
        @class([
            'w-6 h-6',
            'mr-3' => $mobile,
            'text-indigo-300 group-hover:text-white' => !$active,
            'text-white' => $active
        ])
    />
    @endisset
    <span @class([ 'mt-2' => !$mobile ])>
        {{ $slot }}
    </span>
</a>
