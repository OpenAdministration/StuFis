<?php

use App\Models\Setting;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layout.app', ['size' => 'sm'])] class extends Component
{
    public string $financeMail = '';

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

    public function mount(): void
    {
        $this->authorize('view-app-configuration', User::class);

        // Keys present in Setting::defaults() resolve their fallback there; passing a
        // default here would mask it. committees.mode/data have no default, so seed one.
        $this->financeMail = (string) Setting::get('finance_mail');
        $this->mailDomain = (string) Setting::get('mail_domain');
        $this->descMin = (int) Setting::get('project.description.min_length');
        $this->descMax = (int) Setting::get('project.description.max_length');
        $this->protocolActive = (bool) Setting::get('project.protocol_url.active');
        $this->protocolLabel = (string) Setting::get('project.protocol_url.label');
        $this->committeeMode = (string) Setting::get('user.committees.mode', 'filter');
        $this->committeesData = implode("\n", Setting::get('user.committees.data', []) ?? []);
        $this->taxActive = (bool) Setting::get('tax.active');
        $this->datev = (bool) Setting::get('datev');
    }

    protected function rules(): array
    {
        return [
            'financeMail' => ['required', 'email'],
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
        ];
    }

    public function save(): void
    {
        $this->authorize('update-app-configuration', User::class);

        $this->validate();

        Setting::set('finance_mail', $this->financeMail);
        Setting::set('mail_domain', $this->mailDomain);
        Setting::set('project.description.min_length', $this->descMin);
        Setting::set('project.description.max_length', $this->descMax);
        Setting::set('project.protocol_url.active', $this->protocolActive);
        Setting::set('project.protocol_url.label', $this->protocolLabel);
        Setting::set('user.committees.mode', $this->committeeMode);
        Setting::set('user.committees.data', $this->committeeList());
        Setting::set('tax.active', $this->taxActive);
        Setting::set('datev', $this->datev);

        Flux::toast(__('settings.saved'), variant: 'success');
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
