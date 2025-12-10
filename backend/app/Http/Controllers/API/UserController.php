<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{

    /**
     * Show authenticated user with balance and assets
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $user->loadMissing(['assets']);

        return response()->json([
            'balance' => $user->balance,
            'assets' => $user->assets,
        ]);
    }

    /**
     * Get api token
     * @unauthenticated
     */
    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (empty($user->email_verified_at)) {
            return response()->json(
                [
                    'message' => "You have not verified your email address",
                    'is_verified' => false
                ],
                Response::HTTP_FORBIDDEN
            );
        }


        if (! $user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token =  $user->createToken($request->device_name ?? $user->email)->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
