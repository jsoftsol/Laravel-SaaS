<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'User registered successfully',
            'data'    => $result,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'Login successful',
            'data'    => $result,
        ]);
    }

    public function logout()
    {
        $this->authService->logout(auth()->user());

        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
