<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use Respect\Validation\Validator as v;

/**
 * Authentication Controller
 * 
 * Handles user authentication endpoints including
 * registration, login, token refresh, and profile management.
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization endpoints"
 * )
 */
class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Create a new user account with email and password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=6, example="password123"),
     *             @OA\Property(property="role", type="string", enum={"user", "admin"}, example="user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Email already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email already exists")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ServerError")
     *     )
     * )
     */
    public function register(Request $request): Response
    {
        try {
            $data = $request->getBody();

            // Validate input
            $this->validateRegistrationData($data);

            $result = $this->authService->register($data);

            return Response::created($result, 'User registered successfully');

        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return Response::serverError('Registration failed');
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Authentication"},
     *     summary="User login",
     *     description="Authenticate user with email/username and password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="john@example.com", description="Email or username"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ServerError")
     *     )
     * )
     */
    public function login(Request $request): Response
    {
        try {
            $data = $request->getBody();

            // Validate input
            $this->validateLoginData($data);

            $result = $this->authService->login(
                $data['identifier'],
                $data['password']
            );

            return Response::success($result, 'Login successful');

        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 401);
        } catch (\Exception $e) {
            return Response::serverError('Login failed');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Authentication"},
     *     summary="Get current user profile",
     *     description="Get the authenticated user's profile information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile retrieved"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ServerError")
     *     )
     * )
     */
    public function profile(Request $request): Response
    {
        try {
            $token = $request->getBearerToken();
            
            if (!$token) {
                return Response::unauthorized('Token required');
            }

            $user = $this->authService->validateToken($token);

            return Response::success([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'permissions' => $user->permissions,
                    'isActive' => $user->isActive,
                    'createdAt' => $user->createdAt->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return Response::unauthorized('Invalid token');
        }
    }

    /**
     * Refresh JWT token
     */
    public function refresh(Request $request): Response
    {
        try {
            $token = $request->getBearerToken();
            
            if (!$token) {
                return Response::unauthorized('Token required');
            }

            $newToken = $this->authService->refreshToken($token);

            return Response::success([
                'token' => $newToken
            ], 'Token refreshed successfully');

        } catch (\Exception $e) {
            return Response::unauthorized('Token refresh failed');
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): Response
    {
        try {
            $token = $request->getBearerToken();
            
            if (!$token) {
                return Response::unauthorized('Token required');
            }

            $user = $this->authService->validateToken($token);
            $data = $request->getBody();

            // Validate input
            $this->validatePasswordChangeData($data);

            $this->authService->changePassword(
                $user,
                $data['currentPassword'],
                $data['newPassword']
            );

            return Response::success([], 'Password changed successfully');

        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return Response::serverError('Password change failed');
        }
    }

    /**
     * Logout (client-side token invalidation)
     */
    public function logout(Request $request): Response
    {
        // Since JWTs are stateless, logout is handled client-side
        // by removing the token from storage
        return Response::success([], 'Logout successful');
    }

    /**
     * Validate registration data
     */
    private function validateRegistrationData(array $data): void
    {
        $errors = [];

        // Username validation
        if (!v::stringType()->length(3, 30)->validate($data['username'] ?? '')) {
            $errors[] = 'Username must be 3-30 characters long';
        }

        // Email validation
        if (!v::email()->validate($data['email'] ?? '')) {
            $errors[] = 'Valid email is required';
        }

        // Password validation
        if (!v::stringType()->length(6)->validate($data['password'] ?? '')) {
            $errors[] = 'Password must be at least 6 characters long';
        }

        // Role validation (if provided)
        if (isset($data['role'])) {
            $allowedRoles = [
                'user', 'admin', 'super_admin', 'form_manager',
                'payment_approver', 'submission_viewer', 'submission_editor',
                'notification_manager'
            ];
            
            if (!in_array($data['role'], $allowedRoles)) {
                $errors[] = 'Invalid role specified';
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
    }

    /**
     * Validate login data
     */
    private function validateLoginData(array $data): void
    {
        $errors = [];

        if (empty($data['identifier'])) {
            $errors[] = 'Email or username is required';
        }

        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
    }

    /**
     * Validate password change data
     */
    private function validatePasswordChangeData(array $data): void
    {
        $errors = [];

        if (empty($data['currentPassword'])) {
            $errors[] = 'Current password is required';
        }

        if (!v::stringType()->length(6)->validate($data['newPassword'] ?? '')) {
            $errors[] = 'New password must be at least 6 characters long';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
    }
}
