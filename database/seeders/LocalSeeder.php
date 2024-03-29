<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LocalSeeder extends Seeder
{
    public function run(): void
    {
        \DB::table('user')->insert([
            'name' => 'Demo User',
            'username' => 'user',
            'email' => 'user@example.com',
            'provider' => 'local',
            'provider_uid' => 'user',
        ]);
        \DB::table('user')->insert([
            'name' => 'Demo Cash Officer',
            'username' => 'kv',
            'email' => 'kv@example.com',
            'provider' => 'local',
            'provider_uid' => 'kv',
        ]);
        \DB::table('user')->insert([
            'name' => 'Demo Budget Officer',
            'username' => 'hhv',
            'email' => 'hhv@example.com',
            'provider' => 'local',
            'provider_uid' => 'hhv',
        ]);


    }
}
