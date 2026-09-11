@props([
    /* the flux:modal name the row menus open */
    'name' => 'delete-item-modal',
    /* @var \Illuminate\Support\Collection<int, \App\Models\BudgetItem>|null the doomed items in
       display order, each carrying `depth` and the withCount blocker counts; null until armed */
    'rows' => null,
    /* the component action that performs the deletion */
    'action' => 'deleteItem',
    /* extra sentence explaining what deleting means in this editor (the amendment note) */
    'note' => null,
])

{{-- OP#638: the same checklist-style grammar as ⚡plan-view's delete-plan-modal (red warning
     circle, heading, consequences, ghost cancel + danger confirm), but the consequence here is a
     LIST: deleting a group takes its whole subtree with it, so every doomed title is named rather
     than counted. Shared by the plan and the amendment editor, which differ only in what
     "delete" writes — see the $action / $note props. --}}
{{-- @close maps to wire:close: closing the dialog (Abbrechen, Escape, backdrop) disarms the
     server state too. Without it $delete_item_id survives a cancel, and every later render of the
     editor re-runs the subtree query and re-renders this whole list for a modal nobody is looking
     at. Costs one small request on close; saves one recursive query per render after that. --}}
<flux:modal :name="$name" class="md:w-[36rem]" @close="cancelDelete">
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <div class="shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <x-fas-triangle-exclamation class="h-6 w-6 text-red-600"/>
            </div>
            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white">
                {{ __('budget-plan.edit.delete-modal.heading') }}
            </h3>
        </div>

        {{-- the menu opens the modal client-side (instant), so the subtree is still loading here --}}
        <div wire:loading.flex wire:target="confirmDelete" class="flex-col gap-3">
            <div class="h-4 w-3/4 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
            <div class="h-24 w-full animate-pulse rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
            <div class="h-9 w-32 self-end animate-pulse rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
        </div>

        @if($rows !== null)
            @php($blockers = $rows->filter(fn (\App\Models\BudgetItem $item): bool => $item->blocksDeletion()))
            <div wire:loading.remove wire:target="confirmDelete" class="space-y-4">
                @if($blockers->isNotEmpty())
                    {{-- refused: name the titles that hold the reference, per AC 3 --}}
                    <p class="text-sm text-gray-500">{{ __('budget-plan.edit.delete-modal.blocked-intro') }}</p>
                    <ul class="max-h-72 space-y-1 overflow-y-auto text-sm">
                        @foreach($blockers as $blocker)
                            <li class="flex items-start gap-2">
                                <x-fas-circle-xmark class="mt-0.5 h-4 w-4 shrink-0 fill-red-600"/>
                                <span class="text-gray-700 dark:text-gray-300">
                                    <span class="font-mono">{{ $blocker->short_name }}</span>
                                    {{ $blocker->name }}
                                    <span class="text-gray-500">
                                        —
                                        @if($blocker->bookings_count > 0)
                                            {{ trans_choice('budget-plan.edit.delete-modal.blocked-bookings', $blocker->bookings_count) }}
                                        @endif
                                        @if($blocker->bookings_count > 0 && $blocker->project_posts_count > 0), @endif
                                        @if($blocker->project_posts_count > 0)
                                            {{ trans_choice('budget-plan.edit.delete-modal.blocked-posts', $blocker->project_posts_count) }}
                                        @endif
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">
                        {{ trans_choice('budget-plan.edit.delete-modal.intro', $rows->count(), ['count' => $rows->count()]) }}
                    </p>

                    {{-- the doomed titles, indented the way the editor nests them --}}
                    <ul class="max-h-72 divide-y divide-zinc-100 overflow-y-auto rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                        @foreach($rows as $item)
                            <li class="flex items-center gap-2 px-3 py-1.5 text-sm"
                                style="padding-left: {{ 0.75 + $item->depth * 1.25 }}rem">
                                @if($item->isMount())
                                    <x-fas-link class="h-4 w-4 shrink-0 fill-indigo-500"/>
                                @elseif($item->is_group)
                                    <x-fas-wallet class="h-4 w-4 shrink-0 fill-zinc-600"/>
                                @else
                                    <x-fas-money-bill class="h-4 w-4 shrink-0 fill-zinc-400"/>
                                @endif
                                <span class="font-mono text-gray-500">{{ $item->short_name }}</span>
                                <span class="truncate text-gray-700 dark:text-gray-300">{{ $item->name }}</span>
                                <flux:spacer/>
                                <span class="shrink-0 tabular-nums text-gray-500">
                                    {{-- a group's figure is a sum of the rows below it, marked the
                                         same way the editor marks it, so the column doesn't read
                                         as if the group carried a value of its own --}}
                                    @if($item->is_group)Σ @endif{{ $item->effectiveValue()->format() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex items-baseline justify-between text-sm">
                        <span class="text-gray-500">{{ __('budget-plan.edit.delete-modal.total') }}</span>
                        <span class="font-semibold tabular-nums text-gray-700 dark:text-gray-300">
                            {{-- summed over the leaves only, so a group is not counted on top of
                                 the children it merely rolls up --}}
                            {{ $rows->reject->is_group->reduce(
                                   fn (?\Cknow\Money\Money $carry, \App\Models\BudgetItem $item) => $carry->add($item->effectiveValue()),
                                   \Cknow\Money\Money::EUR(0),
                               )->format() }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-500">{{ $note ?? __('budget-plan.edit.delete-modal.warning') }}</p>
                @endif

                <div class="flex gap-3">
                    <flux:spacer/>
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('budget-plan.edit.delete-modal.cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button :wire:click="$action" variant="danger" :disabled="$blockers->isNotEmpty()">
                        {{ __('budget-plan.edit.delete-modal.confirm') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
</flux:modal>
