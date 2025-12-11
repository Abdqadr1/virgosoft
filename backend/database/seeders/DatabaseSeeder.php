<?php

namespace Database\Seeders;

use App\Models\Token;
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
        $tokens = Token::all(['symbol']);
        
        $first = User::updateOrCreate(
            ['email' => 'platform@exchange.local'],
            [
                'name' => 'Platform Account',
                'password' => Hash::make('secret'),
                'email_verified_at' => now(),
                'balance' => 200
            ]
        );


        $second = User::updateOrCreate(
            ['email' => 'second@exchange.local'],
            [
                'name' => 'Second Account',
                'password' => Hash::make('secret'),
                'email_verified_at' => now(),
                'balance' => 200
            ]
        );

        $tokens->each(function ($token) use($first, $second) {
            $first->assets()->updateOrCreate(
                ['symbol' => $token->symbol],
                ['amount' => 100, 'locked_amount' => 0]
            );

            $second->assets()->updateOrCreate(
                ['symbol' => $token->symbol],
                ['amount' => 100, 'locked_amount' => 0]
            );

        });
    }
}
