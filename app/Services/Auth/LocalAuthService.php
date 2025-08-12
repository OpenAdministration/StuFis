<?php

namespace App\Services\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

class LocalAuthService extends AuthService
{
    #[\Override]
    public function prepareLogin(): Response|RedirectResponse
    {
        return redirect()->route('login.callback');
    }

    #[\Override]
    public function userFromCallback(Request $request): array
    {
        return [
            ['username' => config('local.auth.username', 'user')],
            [],
        ];
    }

    #[\Override]
    public function userCommittees(): Collection
    {
        return match (\Auth::user()->username) {
            'user' => collect(['Students Council']),
            'hhv', 'kv' => collect(['Financial Department']),
            'revision' => collect(),
            'external' => collect(),
            default => collect(),
        };
    }

    #[\Override]
    public function allCommittees(): Collection
    {
        return collect([
            'Students',
            'Financial Department']
        );
    }

    #[\Override]
    public function userGroupsRaw(): Collection
    {
        return match (\Auth::user()->username) {
            'user-no-login' => collect(),
            'user', 'external' => collect(['login']),
            'hhv' => collect(['login', 'ref-finanzen', 'ref-finanzen-hv', 'ref-finanzen-belege']),
            'kv' => collect(['login', 'ref-finanzen', 'ref-finanzen-kv', 'ref-finanzen-belege']),
            'revision' => collect(['login', 'ref-finanzen']),
            'admin' => collect(['admin']),
        };
    }

    #[\Override]
    public function afterLogout()
    {
        return redirect()->route('login.callback');
    }
}
