<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('symbol', 10)->unique();
            $table->decimal('price_usd', 28, 8);
            $table->timestamps();
        });

        // seed initial tokens
        DB::table('tokens')->insert([
            ['name' => 'Bitcoin', 'symbol' => 'BTC', 'price_usd' => 30000.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ethereum', 'symbol' => 'ETH', 'price_usd' => 2000.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cardano', 'symbol' => 'ADA', 'price_usd' => 0.35, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Solana', 'symbol' => 'SOL', 'price_usd' => 25.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ripple', 'symbol' => 'XRP', 'price_usd' => 0.50, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Polkadot', 'symbol' => 'DOT', 'price_usd' => 6.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Litecoin', 'symbol' => 'LTC', 'price_usd' => 100.00, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
