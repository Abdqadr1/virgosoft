<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function show(Request $request)
    {
        $user = $request->user();
        $user->loadMissing(['assets']);

        return response()->json([
            'balance' => $user->balance,
            'assets' => $user->assets,
        ]);
    }
}
