{{--
    Shared, event-driven preview modal for stored attachments.

    Place ONE of these per page and let the file cards drive it: <x-file-card>
    dispatches a `file-preview` window event when a previewable file is clicked,
    and this component renders whatever that event carries. Keeping a single
    modal (rather than one per attachment) matters: a modal per file would put an
    <iframe> per attachment in the DOM and make the browser fetch every PDF on
    page load.

    Only pdf and image survive as preview kinds — see the `$previewKind` derivation
    in <x-file-card>. Office formats can't render in a frame; those cards keep
    their plain download/new-tab behaviour and never dispatch.

    <iframe>, not <embed>/<object>: our CSP allows `frame-src 'self'` but sets
    `object-src 'none'` (see config/csp.php).

    There is deliberately no download button in here: it collided with the modal's
    own close button in the top-right corner. Every card already carries one.
--}}
@props([
    'name' => 'file-preview',
])

<div
    x-data="{ file: null }"
    x-on:file-preview.window="file = $event.detail; $flux.modal(@js($name)).show()"
>
    {{--
        Flux's own modal classes are `[:where(&)]:max-w-xl` / `[:where(&)]:min-w-xs`.
        `:where()` carries zero specificity, so a plain `max-w-*` here overrides it --
        but only a `max-width` does. Setting `w-...` alone leaves the 36rem cap in
        place and the modal stays narrow, which is no use for reading a PDF.
    --}}
    <flux:modal :name="$name" class="w-full max-w-[95vw] lg:max-w-[80rem]" x-on:close="file = null">
        <div class="space-y-4">
            {{-- pe-10 keeps the filename clear of the modal's own close button. --}}
            <flux:heading size="lg" class="truncate pe-10" x-text="file?.name"></flux:heading>

            <template x-if="file?.kind === 'pdf'">
                <iframe
                    x-bind:src="file.src"
                    x-bind:title="file.name"
                    class="w-full h-[80vh] rounded-lg border border-zinc-200 dark:border-white/10"
                ></iframe>
            </template>

            <template x-if="file?.kind === 'image'">
                <img
                    x-bind:src="file.src"
                    x-bind:alt="file.name"
                    class="max-h-[80vh] mx-auto rounded-lg"
                >
            </template>
        </div>
    </flux:modal>
</div>
