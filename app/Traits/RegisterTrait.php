<?php

namespace App\Traits;

use App\Models\User;
use App\Http\Requests\RegisterRequest;

trait RegisterTrait
{
    public function createRegister(RegisterRequest $request)
    {
        $fields = $request->validated();
        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password'])
        ]);
        $token = $user->createToken('API Token')->accessToken;
        return [
            'token' => $token,
            'user' => $user
        ];
    }
}
