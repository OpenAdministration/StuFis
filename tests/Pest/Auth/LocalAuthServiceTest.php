<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

it('should be all testing accounts in DB', function (): void {
    foreach (['user', 'hhv', 'kv', 'user-no-login', 'admin'] as $username) {
        $userExists = User::where(['username' => $username, 'provider' => 'local'])->exists();
        expect($userExists)->toBeTrue();
    }
});

it('all accounts have matching groups', function (): void {
    foreach (['user', 'hhv', 'kv', 'user-no-login', 'admin'] as $username) {
        $user = User::where(['username' => $username, 'provider' => 'local'])->first();
        actingAs($user);

        $groups = match (\Auth::user()->username) {
            'user-no-login' => collect([]),
            'user' => collect(['login']),
            'hv','hhv' => collect(['login', 'ref-finanzen', 'ref-finanzen-hv', 'ref-finanzen-belege']),
            'kv' => collect(['login', 'ref-finanzen', 'ref-finanzen-kv', 'ref-finanzen-belege']),
            'revision' => collect(['login', 'ref-finanzen']),
            'admin' => collect(['admin']),
        };
        expect(Auth::user()->getGroups())->toEqualCanonicalizing($groups);
    }
});
