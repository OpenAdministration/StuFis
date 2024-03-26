<?php

namespace App\Services\Auth;

use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

class LocalAuthService extends AuthService
{

    private string $username;
    public function __construct()
    {
        $this->username = config('services.local.user');
    }

    public function prepareLogin(): Response|RedirectResponse
    {
        return redirect()->route('auth.callback');
    }

    public function userFromCallback(Request $request): array
    {
        return ['username' => $this->username, []];
    }

    public function userCommittees(): Collection
    {
        return match ($this->username){
            'user' => collect(['Students Council']),
            'hhv' => collect(['Financial Department']),
            'kv' => collect(['Financial Department']),
            'revision' => collect(),
            'external' => collect(),
        };
    }

    public function allCommittees(): Collection
    {
        return collect([
            'Students',
            'Financial Department']
        );
    }

    public function userGroupsRaw(): Collection
    {
        return match ($this->username){
            'user' => collect(['login']),
            'hhv' => collect(['login', 'ref-finanzen', 'ref-finanzen-hv', 'ref-finanzen-belege']),
            'kv' => collect(['login', 'ref-finanzen', 'ref-finanzen-kv', 'ref-finanzen-belege']),
            'revision' => collect(['login', 'ref-finanzen']),
            'external' => collect(['login']),
        };
    }

    public function afterLogout(){}


}
