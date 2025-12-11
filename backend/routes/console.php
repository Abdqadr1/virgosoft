<?php

use App\Jobs\MatchingJob;
use Illuminate\Support\Facades\Artisan;

Artisan::command('simulate-match', function () {
    // Create two orders that can be matched
    $order1 = \App\Models\Order::create([
        'user_id' => 1,
        'side' => 'buy',
        'symbol' => 'BTC',
        'amount' => 100,
        'price' => 50,
        'usd_amount' => 5000,
        'status' => 1,
    ]);

    $asset = \App\Models\Asset::firstOrCreate([
        'user_id' => 2,
        'symbol' => 'BTC',
    ],[
        'amount' => 200,
        'locked_amount' => 100,
    ]);

    $order2 = \App\Models\Order::create([
        'user_id' => 2,
        'side' => 'sell',
        'symbol' => 'BTC',
        'amount' => 100,
        'price' => 50,
        'usd_amount' => 5000,
        'status' => 1,
    ]);


    // Dispatch matched event
    MatchingJob::dispatch($order2)->afterCommit();

    $this->info('Two orders have been created and matched.');
});
