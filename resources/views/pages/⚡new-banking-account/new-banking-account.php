<?php

use App\Models\Legacy\BankAccount;
use Livewire\Attributes\Layout;
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

    #[Validate]
    public $iban;

    #[Validate]
    public $manually_enterable = false;

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
        BankAccount::create($data);
        $this->redirectRoute('legacy.konto');
    }
};
