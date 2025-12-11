<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\MatchingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MatchingJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order)
    { }

    public function uniqueId(): string
    {
        return $this->order->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        MatchingService::attempt($this->order);
    }
}
