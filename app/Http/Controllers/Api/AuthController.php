<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponseTrait;
    
    /**
     * @var AuthService
     */
    protected $authService;
    
    /**
     * Create a new AuthController instance.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        try {
            // Validation is automatically handled by the FormRequest
            $result = $this->authService->register($request->validated());
            
            return $this->successResponse([
                'user' => $result['user'],
                'token' => $result['token']
            ], 'User registered successfully', 201);
            
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return $this->errorResponse('Registration failed', 500, $e->getMessage());
        }
    }

    /**
     * Login user and create token
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            // Validation is automatically handled by the FormRequest
            $result = $this->authService->login($request->validated());
            
            return $this->successResponse([
                'user' => $result['user'],
                'token' => $result['token']
            ], 'Login successful');
            
        } catch (ValidationException $e) {
            return $this->errorResponse('Invalid credentials', 401, $e->errors());
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return $this->errorResponse('Login failed', 500, $e->getMessage());
        }
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request->user());
            
            return $this->successResponse(null, 'Successfully logged out');
            
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return $this->errorResponse('Logout failed', 500, $e->getMessage());
        }
    }

    /**
     * Get the authenticated User
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return $this->successResponse([
            'user' => $request->user()
        ], 'User profile retrieved successfully');
    }
}
