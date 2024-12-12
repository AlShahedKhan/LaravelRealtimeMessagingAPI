<?php

namespace App\Http\Controllers;

use App\Services\AuthClass;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    protected $auth;

    public function __construct(AuthClass $authClass)
    {
        $this->auth = $authClass;
    }

    public function register(RegisterRequest $request)
    {
        return $this->auth->register($request);
    }

    public function login(LoginRequest $request)
    {
        return $this->auth->login($request);
    }
}
