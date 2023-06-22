<?php

namespace Tests;

use App\Models\User;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public $baseUrl = 'http://localhost:8000';

    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = true;

    protected function loginAsUser(): void
    {
        $this->loginAs('user');
    }

    protected function loginAsBudgetManager(): void
    {
        $this->loginAs('hv');
    }

    protected function loginAsCashManager(): void
    {
        $this->loginAs('kv');
    }

    protected function loginAs(string $username): void
    {
        $this->actingAs(User::where(['username' => $username])->first());
    }

}
