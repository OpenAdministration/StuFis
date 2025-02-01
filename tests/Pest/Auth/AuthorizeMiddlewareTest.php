<?php

use function Pest\Laravel\actingAs;

it('should throw forward to login if not logged in', function () {
    $response = $this->get(route('home'));
    $response->assertRedirectToRoute('login');
});

it('should throw an error if logged in, but no login permission is granted', function () {
    actingAs(userNoLogin())
        ->get(route('home'))
        ->assertUnauthorized();

})->todo();
