<?php

use App\Models\Legacy\BankAccount;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layout.app', ['size' => 'md'])] class extends Component
{
    #[Validate]
    public $short;

    #[Validate]
    public $name;

    #[Validate]
    public $sync_from;

    #[Validate]
    public $sync_until;

    // The FinTS account list links here with ?iban=... prefilled, so a bank access hands
    // the account over to this page instead of carrying its own create form.
    #[Validate]
    #[Url]
    public $iban;

    #[Validate]
    public $manually_enterable = false;

    /**
     * Set when a FinTS bank access hands an account over. Its IBAN then comes from the
     * bank's own account list rather than from typing, and its transactions arrive by
     * synchronisation - so neither the IBAN nor the manual-entry switch may be changed here.
     */
    #[Url]
    public bool $bankSynced = false;

    /**
     * Where to go after saving, so the bank access gets its user back. Only same-origin
     * paths are honoured - see returnUrl().
     */
    #[Url]
    public ?string $returnTo = null;

    public function rules(): array
    {
        return [
            'short' => 'required|max:2|alpha|uppercase|unique:App\Models\Legacy\BankAccount,short',
            'name' => 'required|string|min:3|max:32',
            'sync_from' => 'required|date',
            'sync_until' => 'nullable|date|after:sync_from',
            'iban' => 'nullable|iban',
            'manually_enterable' => 'required|boolean',
        ];
    }

    public function store(): void
    {
        $data = $this->validate();

        if ($this->bankSynced) {
            // Switching manual entry on rules out automatic synchronisation (see the field's
            // own description), which is exactly what an account handed over by a bank access
            // is for. The switch is disabled in the form; this makes it hold for a tampered
            // request too.
            $data['manually_enterable'] = false;
        }

        BankAccount::create($data);
        $this->redirect($this->returnUrl());
    }

    private function returnUrl(): string
    {
        // Same-origin paths only. Anything absolute - and "//host", which a browser reads as
        // a protocol-relative URL - would turn this into an open redirect.
        if (is_string($this->returnTo)
            && str_starts_with($this->returnTo, '/')
            && ! str_starts_with($this->returnTo, '//')) {
            return $this->returnTo;
        }

        return route('legacy.konto');
    }
};
