<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'platform@exchange.local'],
            [
                'name' => 'Platform Account',
                'password' => Hash::make('secret'),
                'email_verified_at' => now(),
                'balance' => 1000
            ]
        );

        User::updateOrCreate(
            ['email' => 'second@exchange.local'],
            [
                'name' => 'Second Account',
                'password' => Hash::make('secret'),
                'email_verified_at' => now(),
                'balance' => 550
            ]
        );
    }
}
