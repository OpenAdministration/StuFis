<?php

use function Pest\Laravel\actingAs;

it('should forward to login if not logged in', function (): void {
    $response = $this->get(route('home'));
    $response->assertRedirectToRoute('login');
});

it('should not allow to login without login permission', function (): void {
    actingAs(userNoLogin())
        ->get(route('home'))
        ->assertUnauthorized();
});

it('should allow login as user', function (): void {
    actingAs(user())
        ->get(route('home'))
        ->assertRedirect();
});

it('should allow login admin', function (): void {
    actingAs(adminUser())
        ->get(route('home'))
        ->assertRedirect();
});
