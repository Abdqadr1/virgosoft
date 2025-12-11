<?php

use App\Jobs\MatchingJob;
use Illuminate\Support\Facades\Artisan;

Artisan::command('simulate-match', function () {
    // Create two orders that can be matched
    $order1 = \App\Models\Order::create([
        'user_id' => 1,
        'side' => 'buy',
        'symbol' => 'ETH',
        'amount' => 0.001,
        'price' => 50,
        'usd_amount' => 5000,
        'status' => 1,
    ]);

    $order2 = \App\Models\Order::create([
        'user_id' => 2,
        'side' => 'sell',
        'symbol' => 'ETH',
        'amount' => 0.001,
        'price' => 50,
        'usd_amount' => 5000,
        'status' => 1,
    ]);


    // Dispatch matched event
    MatchingJob::dispatch($order2)->afterCommit();

    $this->info('Two orders have been created and matched.');
});
