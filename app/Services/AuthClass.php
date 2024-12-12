<?php

namespace App\Services;

use App\Traits\LoginTrait;
use App\Traits\RegisterTrait;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;


class AuthClass
{
    use RegisterTrait, LoginTrait;

    public function register(RegisterRequest $request)
    {
        $result = $this->createRegister($request);
        $data = [
            'user' => $result['user'],
            'token' => $result['token'],
        ];
        return response()->json([
            'message' => 'User created successfully',
            'data' => $data
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authenticateUser($request->email, $request->password, $request);

        if (!$result) {
            return response()->json([
                'message' => 'Invalid email or password'
            ], 401);
        }

        return response()->json([
            'message' => 'User logged in successfully',
            'data' => [
                'user' => $result['user'],
                'token' => $result['token'],
            ],
        ], 200);
    }
}
