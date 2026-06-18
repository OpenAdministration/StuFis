<?php

use function Pest\Laravel\actingAs;

it('permanently forwards the legacy create URL to the new project create page', function (): void {
    actingAs(user())->get('/projekt/create')
        ->assertStatus(301)
        ->assertRedirect('/project/create');
});

it('views project create form without iframe wrapping and layout', function (): void {
    $response = actingAs(user())->get('/project/create');
    $response->assertStatus(200)->assertDontSee('<iframe>');
});

// pest cannot interact with forms or html well :(
