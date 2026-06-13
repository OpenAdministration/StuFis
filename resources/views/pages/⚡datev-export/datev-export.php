<?php

use App\Exports\Datev\DatevExport;
use App\Exports\Datev\DatevExportDateField;
use App\Exports\Datev\DatevExportPreviewRow;
use App\Models\Legacy\LegacyBudgetPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layout.app', ['size' => 'lg'])] class extends Component
{
    #[Url]
    public ?int $hhpId = null;

    public array $dateRange = [];

    public bool $exportPdfs = true;

    public string $dateField = DatevExportDateField::BookingDate->value;

    /** Set once the detailed preview has been pulled (lazy — not loaded on first paint). */
    public bool $previewLoaded = false;

    /** A filter changed since the preview was last pulled — the shown list is out of date. */
    public bool $previewStale = false;

    public function mount(): void
    {
        $this->authorize('download', DatevExport::class);

        $this->hhpId = LegacyBudgetPlan::latest()?->id;
    }

    #[Computed]
    public function budgetPlans(): Collection
    {
        return LegacyBudgetPlan::orderBy('id', 'desc')->get();
    }

    /**
     * Date fields a user may filter the export on.
     *
     * @return array<string, string> value => translated label
     */
    #[Computed]
    public function dateFields(): array
    {
        $fields = [
            DatevExportDateField::BookingDate,
            DatevExportDateField::ExpenseCreatedDate,
            // DatevExportDateField::EarliestReceiptDate,
            // DatevExportDateField::EarliestPaymentDate,
        ];

        return collect($fields)
            ->mapWithKeys(fn (DatevExportDateField $field) => [
                $field->value => __("datev-export.date-field.options.{$field->value}"),
            ])
            ->all();
    }

    #[Computed]
    public function exportCount(): int
    {
        if (! $this->isExportable()) {
            return 0;
        }

        return $this->makeExport()->count();
    }

    /**
     * Detailed rows of what will be exported. Heavy (walks the full receipt/booking
     * graph), so the view only accesses it once the user has loaded the preview.
     *
     * @return Collection<int, DatevExportPreviewRow>
     */
    #[Computed]
    public function previewRows(): Collection
    {
        if (! $this->isExportable()) {
            return collect();
        }

        return $this->makeExport()->preview();
    }

    /** Pull (or refresh) the detailed preview list. */
    public function loadPreview(): void
    {
        unset($this->previewRows);
        $this->previewLoaded = true;
        $this->previewStale = false;
    }

    /** When a filter affecting the result set changes, flag the loaded preview as stale. */
    public function updated(string $name): void
    {
        if ($this->previewLoaded && in_array($name, ['hhpId', 'dateField', 'dateRange', 'dateRange.start', 'dateRange.end'], true)) {
            $this->previewStale = true;
        }
    }

    /** Whether the current selection is complete enough to run an export. */
    public function isExportable(): bool
    {
        return $this->hhpId
            && ! empty($this->dateRange['start'] ?? null)
            && ! empty($this->dateRange['end'] ?? null);
    }

    private function makeExport(bool $withPdfs = false): DatevExport
    {
        return new DatevExport(
            hhpId: $this->hhpId,
            dateRangeStart: Carbon::parse($this->dateRange['start']),
            dateRangeEnd: Carbon::parse($this->dateRange['end']),
            exportPdfs: $withPdfs,
            dateField: DatevExportDateField::from($this->dateField),
        );
    }

    protected function rules(): array
    {
        return [
            'hhpId' => ['required', 'int', new Exists(LegacyBudgetPlan::class, 'id')],
            'dateRange.start' => 'required|date',
            'dateRange.end' => 'required|date|after:dateRange.start',
            'exportPdfs' => 'required|bool',
            'dateField' => ['required', Rule::in(array_keys($this->dateFields))],
        ];
    }

    public function export()
    {
        $this->authorize('download', DatevExport::class);

        $this->validate();

        $path = $this->makeExport(withPdfs: $this->exportPdfs)->export();

        if ($path === false) {
            $this->addError('export', __('datev-export.export-failed'));

            return null;
        }

        // Redirect to a plain (non-Livewire) route to stream the file: Livewire
        // base64-encodes any file returned from a component action in memory, which
        // exhausts the memory limit for large (PDF-laden) archives. A temporary signed
        // URL hands the finished zip off statelessly — no session, safe across tabs, and
        // the link self-expires.
        return $this->redirect(url()->temporarySignedRoute('datev.export.download', now()->addMinutes(10), [
            'file' => basename($path),
            'name' => sprintf(
                'datev-export_%s_%s.zip',
                Carbon::parse($this->dateRange['start'])->format('Y-m-d'),
                Carbon::parse($this->dateRange['end'])->format('Y-m-d')
            ),
        ]));
    }
};
