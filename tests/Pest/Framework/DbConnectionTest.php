<?php

it('runs with the correct db connection', function (): void {
    expect(DB::getDefaultConnection())->toBe('mariadb-testing');
});
