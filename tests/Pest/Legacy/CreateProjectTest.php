<?php

use function Pest\Laravel\actingAs;

it('views project create form without iframe wrapping', function (): void {
    $response = actingAs(user())->get('/projekt/create');
    $response->assertStatus(200)->assertDontSee('<iframe>');
});

// pest cannot interact with forms or html well :(
