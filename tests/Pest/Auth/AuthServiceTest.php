<?php

use App\Services\Auth\AuthService;

it('should do no mapping if mapping is empty', function () {
    $mock = Mockery::mock(AuthService::class);
    $mock->shouldReceive('userGroups')->passthru();
    $mock->shouldReceive('groupMapping')->andReturn(collect());
    $mock->shouldReceive('userGroupsRaw')->andReturn(collect(['login', 'something-else']));
    expect($mock->userGroups())->toEqual(collect(['login', 'something-else']));
});

it('should omit non-mapped groups', function () {
    $mock = Mockery::mock(AuthService::class);
    $mock->shouldReceive('userGroups', 'groupMapping')->passthru();
    $mock->shouldReceive('userGroupsRaw')->andReturn(collect(['login', 'something-else']));
    expect($mock->userGroups())->toEqual(collect(['login']));
});

it('does not escalate permission if mapping has a empty value', function () {
    $mock = Mockery::mock(AuthService::class);
    $mock->shouldReceive('userGroups')->passthru();
    $mock->shouldReceive('userGroupsRaw')->andReturn(collect(['', 'something-else']));
    $mock->shouldReceive('groupMapping')->andReturn(
        collect(['login' => '']),
        collect(['login' => false]),
        collect(['login' => 0])
    );
    expect($mock->userGroups())->not->toEqual(collect(['login']));
    expect($mock->userGroups())->not->toEqual(collect(['login']));
    expect($mock->userGroups())->not->toEqual(collect(['login']));
});

it('gives default permissions if mapping has a true value', function () {
    $mock = Mockery::mock(AuthService::class);
    $mock->shouldReceive('userGroups')->passthru();
    $mock->shouldReceive('userGroupsRaw')->andReturn(collect());
    $mock->shouldReceive('groupMapping')->andReturn(collect(['login' => true]));

    expect($mock->userGroups())->toEqual(collect(['login']));
});
