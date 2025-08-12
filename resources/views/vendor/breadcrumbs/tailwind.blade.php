{{-- used by the breadcrumbs libary --}}
@unless ($breadcrumbs->isEmpty())
    <flux:breadcrumbs>
        @foreach ($breadcrumbs as $breadcrumb)
            @if($loop->first)
                <flux:breadcrumbs.item :href="$breadcrumb->url" icon="home" icon:variant="outline">
                    {{ $breadcrumb->title }}
                </flux:breadcrumbs.item>
            @else
                <flux:breadcrumbs.item :href="$breadcrumb->url">
                    {{ $breadcrumb->title }}
                </flux:breadcrumbs.item>
            @endif
        @endforeach
    </flux:breadcrumbs>
@endunless
