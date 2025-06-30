<?php

namespace App\Livewire;

use App\Models\Legacy\BankAccount;
use Livewire\Attributes\Validate;
use Livewire\Component;

class NewBankingAccount extends Component
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

    public function render()
    {
        return view('livewire.new-banking-account');
    }

    public function store(): void
    {
        $data = $this->validate();
        BankAccount::create($data);
        $this->redirectRoute('legacy.konto');
    }
}
