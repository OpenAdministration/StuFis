<div>
{{-- OP#638: the same checklist-style grammar as ⚡plan-view's delete-plan-modal (red warning
     circle, heading, consequences, ghost cancel + danger confirm), but the consequence here is a
     LIST: deleting a group takes its whole subtree with it, so every doomed title is named rather
     than counted. See App\Livewire\BudgetPlan\DeleteSubtreeModal for why this is a component.

     @close maps to wire:close, so Abbrechen, Escape and the backdrop disarm $itemId as well.
     Without it the dialog stays armed and every later render re-runs the subtree query and
     rebuilds this whole list for a modal nobody is looking at. --}}
<flux:modal name="delete-item-modal" class="md:w-[36rem]" @close="cancelDelete">
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <div class="shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <x-fas-triangle-exclamation class="h-6 w-6 text-red-600"/>
            </div>
            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white">
                {{ __('budget-plan.edit.delete-modal.heading') }}
            </h3>
        </div>

        @if($rows !== null)
            @php($blockers = $rows->filter(fn (\App\Models\BudgetItem $item): bool => $item->blocksDeletion()))
            <div class="space-y-4">
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
                                         as if the group carried a value of its own. Precomputed in
                                         the component: calling effectiveValue() here lazy-loaded a
                                         group's children once per rendered row. --}}
                                    @if($item->is_group)Σ @endif{{ $values[$item->id]->format() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex items-baseline justify-between text-sm">
                        <span class="text-gray-500">{{ __('budget-plan.edit.delete-modal.total') }}</span>
                        <span class="font-semibold tabular-nums text-gray-700 dark:text-gray-300">{{ $total->format() }}</span>
                    </div>

                    {{-- in an amendment nothing is removed yet, so the warning is replaced by
                         what drafting the deletion actually means --}}
                    <p class="text-sm text-gray-500">
                        {{ $amendmentId === null
                            ? __('budget-plan.edit.delete-modal.warning')
                            : __('budget-plan.edit.delete-modal.amendment-note') }}
                    </p>
                @endif

                <div class="flex gap-3">
                    <flux:spacer/>
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('budget-plan.edit.delete-modal.cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="deleteItem" variant="danger" :disabled="$blockers->isNotEmpty()">
                        {{ __('budget-plan.edit.delete-modal.confirm') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
</flux:modal>
</div>
