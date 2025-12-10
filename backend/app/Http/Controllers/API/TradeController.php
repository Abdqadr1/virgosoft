<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Trade;

class TradeController extends Controller
{

    /**
     * Get all-time commission from trades
     */
    public function allTimeCommission()
    {
        return response()->json([
            'commission' => Trade::sum('commission'),
        ]);
    }
}
