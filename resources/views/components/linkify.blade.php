@foreach ($segments((string) $slot) as $segment)
    @if ($segment['type'] === 'link')
        <flux:link :href="$segment['href']" external>{!! $segment['html'] !!}</flux:link>
    @else{!! $segment['html'] !!}
    @endif
@endforeach
