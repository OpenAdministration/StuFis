<?php

use App\Models\Legacy\Project;
use App\Models\LegalBasis;
use App\Models\Setting;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layout.app', ['size' => 'sm'])] class extends Component
{
    public string $mailDomain = '';

    public int $descMin = 50;

    public int $descMax = -1;

    public bool $protocolActive = false;

    public string $protocolLabel = '';

    public string $committeeMode = 'filter';

    /** Committee list, one entry per line (textarea-friendly view of user.committees.data). */
    public string $committeesData = '';

    public bool $taxActive = false;

    public bool $datev = false;

    /**
     * Editable rows of the legal_bases table (project legal grounds / Rechtsgrundlagen).
     * `_key` is a stable client-side id for wire:key / wire:sort (rows may be unsaved).
     *
     * @var list<array{_key:string, id:?int, slug:string, label:string, label_additional:?string, hint_text:?string, placeholder:?string, is_active:bool}>
     */
    public array $legalBases = [];

    public function mount(): void
    {
        $this->authorize('view-app-configuration', User::class);

        // Keys present in Setting::defaults() resolve their fallback there; passing a
        // default here would mask it. committees.mode/data have no default, so seed one.
        $this->mailDomain = (string) Setting::get('mail_domain');
        $this->descMin = (int) Setting::get('project.description.min_length');
        $this->descMax = (int) Setting::get('project.description.max_length');
        $this->protocolActive = (bool) Setting::get('project.protocol_url.active');
        $this->protocolLabel = (string) Setting::get('project.protocol_url.label');
        $this->committeeMode = (string) Setting::get('user.committees.mode', 'filter');
        $this->committeesData = implode("\n", Setting::get('user.committees.data', []) ?? []);
        $this->taxActive = (bool) Setting::get('tax.active');
        $this->datev = (bool) Setting::get('datev');

        $this->legalBases = LegalBasis::ordered()->get()
            ->map(fn (LegalBasis $basis): array => [
                '_key' => (string) Str::uuid(),
                'id' => $basis->id,
                'slug' => $basis->slug,
                'label' => $basis->label,
                'label_additional' => $basis->label_additional,
                'hint_text' => $basis->hint_text,
                'placeholder' => $basis->placeholder,
                'is_active' => $basis->is_active,
            ])
            ->all();
    }

    public function addLegalBasis(): void
    {
        $this->legalBases[] = [
            '_key' => (string) Str::uuid(),
            'id' => null,
            'slug' => '',
            'label' => '',
            'label_additional' => null,
            'hint_text' => null,
            'placeholder' => null,
            'is_active' => true,
        ];
    }

    /** Reorder rows when one is dragged to a new position; persisted as sort_order on save. */
    public function sortLegalBases(string $key, int $position): void
    {
        $rows = $this->legalBases;
        $from = collect($rows)->search(fn (array $row): bool => $row['_key'] === $key);

        if ($from === false) {
            return;
        }

        $moved = $rows[$from];
        unset($rows[$from]);
        $rows = array_values($rows);
        array_splice($rows, $position, 0, [$moved]);

        $this->legalBases = $rows;
    }

    /**
     * Drop a legal basis row. A stored basis still referenced by a project is kept
     * (its slug lives on those projects) — the admin should deactivate it instead.
     */
    public function removeLegalBasis(int $index): void
    {
        $row = $this->legalBases[$index] ?? null;

        if ($row === null) {
            return;
        }

        if ($row['id'] !== null && Project::where('recht', $row['slug'])->exists()) {
            Flux::toast(__('settings.legal-bases.in-use'), variant: 'warning');

            return;
        }

        unset($this->legalBases[$index]);
        $this->legalBases = array_values($this->legalBases);
    }

    protected function rules(): array
    {
        return [
            'mailDomain' => ['required', 'string'],
            'descMin' => ['required', 'integer', 'min:0'],
            // -1 disables the upper bound; any other value must be >= the minimum.
            'descMax' => ['required', 'integer', 'min:-1', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== -1 && $value < $this->descMin) {
                    $fail(__('settings.description-max.invalid'));
                }
            }],
            'protocolActive' => ['boolean'],
            'protocolLabel' => ['nullable', 'string'],
            'committeeMode' => ['required', Rule::in(['filter', 'all', 'raw'])],
            'committeesData' => ['nullable', 'string'],
            'taxActive' => ['boolean'],
            'datev' => ['boolean'],
            'legalBases' => ['array'],
            'legalBases.*.slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/', 'distinct:ignore_case'],
            'legalBases.*.label' => ['required', 'string', 'max:255'],
            'legalBases.*.label_additional' => ['nullable', 'string', 'max:255'],
            'legalBases.*.hint_text' => ['nullable', 'string', 'max:1000'],
            'legalBases.*.placeholder' => ['nullable', 'string', 'max:255'],
            'legalBases.*.is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $this->authorize('update-app-configuration', User::class);

        $this->validate();

        Setting::set('mail_domain', $this->mailDomain);
        Setting::set('project.description.min_length', $this->descMin);
        Setting::set('project.description.max_length', $this->descMax);
        Setting::set('project.protocol_url.active', $this->protocolActive);
        Setting::set('project.protocol_url.label', $this->protocolLabel);
        Setting::set('user.committees.mode', $this->committeeMode);
        Setting::set('user.committees.data', $this->committeeList());
        Setting::set('tax.active', $this->taxActive);
        Setting::set('datev', $this->datev);

        $this->syncLegalBases();

        Flux::toast(__('settings.saved'), variant: 'success');
    }

    /**
     * Upsert the edited legal bases (sort_order follows row order) and delete the rows
     * the admin removed — but never one still referenced by a project, to keep those
     * projects' legal-ground lookups intact.
     */
    private function syncLegalBases(): void
    {
        $keptIds = [];

        foreach ($this->legalBases as $index => $row) {
            $basis = LegalBasis::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'label' => $row['label'],
                    'label_additional' => $row['label_additional'] ?: null,
                    'hint_text' => $row['hint_text'] ?: null,
                    'placeholder' => $row['placeholder'] ?: null,
                    'sort_order' => $index,
                    'is_active' => (bool) $row['is_active'],
                ]
            );

            $keptIds[] = $basis->id;
        }

        LegalBasis::whereNotIn('id', $keptIds)
            ->get()
            ->each(function (LegalBasis $basis): void {
                if (! Project::where('recht', $basis->slug)->exists()) {
                    $basis->delete();
                }
            });
    }

    /**
     * The textarea content as a clean list: trimmed, blank lines dropped, re-indexed.
     *
     * @return list<string>
     */
    private function committeeList(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $this->committeesData))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }
};
