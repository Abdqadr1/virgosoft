<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
                'password' => bcrypt('secret'),
                'balance' => 0
            ]
        );
    }
}
