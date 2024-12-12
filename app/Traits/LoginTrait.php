<?php

namespace App\Traits;

use App\Models\User;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;

trait LoginTrait
{
    public function authenticateUser($email, $password, LoginRequest $request)
    {
        // Find the user by email
        $user = User::where('email', $email)->first();

        // Check if the user exists and the password matches
        if (!$user || !Hash::check($password, $user->password)) {
            return false; // Return false if authentication fails
        }

        // Extract email and password from the request
        $email = $request->input('email');
        $password = $request->input('password');
        // Generate a token for the authenticated user
        $token = $user->createToken('API Token')->accessToken;

        // Return the user and token
        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
