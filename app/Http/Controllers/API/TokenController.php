<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Models\Trade;
use Illuminate\Http\Request;

class TokenController extends Controller
{

    /**
     * List all tokens
     */
    public function list(Request $request)
    {
        return response()->json(
            Token::all()
        );
    }

    /**
     * Add a new token
     */
    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:tokens,name',
            'symbol' => 'required|string|unique:tokens,symbol',
            'price_usd' => 'required|numeric',
        ]);

        $token = Token::create([
            'name' => $request->input('name'),
            'symbol' => $request->input('symbol'),
            'price_usd' => $request->input('price_usd'),
        ]);

        return response()->json($token, 201);
    }
}
