<?php

it('runs with the correct db connection', function () {
    expect(DB::getDefaultConnection())->toBe('mariadb-testing');
});
