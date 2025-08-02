<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Session;
use Livewire\Component;

class Message extends Component
{
    public function render()
    {
        return view('livewire.message');
    }

    public function closeNotification(): void
    {
        Session::forget('message');
    }
}
