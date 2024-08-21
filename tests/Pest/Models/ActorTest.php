<?php

namespace Tests\Feature\Models;

use App\Models\Actor;
use App\Models\ActorMail;
use App\Models\ActorSocial;
use function Pest\Laravel\assertModelExists;

test('actor factory and relations', function () {
    $actor = Actor::factory()->create();
    assertModelExists($actor);
    $actor = Actor::factory()
        ->has(ActorSocial::factory()->count(3))
        ->has(ActorMail::factory()->count(3))
        ->create();
    expect($actor->socials->count())->toBe(3)
        ->and($actor->mails->count())->toBe(3);

    $actor = Actor::factory()->asOrganisation()->create();
    assertModelExists($actor);

});