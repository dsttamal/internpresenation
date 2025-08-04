<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Authentication Service
 * 
 * Handles user authentication, JWT token generation and validation.
 */
class AuthService
{
    private string $jwtSecret;
    private string $jwtExpiration;

    public function __construct()
    {
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'fallback_secret';
        $this->jwtExpiration = $_ENV['JWT_EXPIRATION'] ?? '7d';
    }

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        // Check if user exists
        $existingUser = User::where('email', $data['email'])
            ->orWhere('username', $data['username'])
            ->first();

        if ($existingUser) {
            throw new \InvalidArgumentException('User already exists with this email or username');
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Create user
        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'role' => $data['role'] ?? User::ROLE_USER,
            'isActive' => true
        ]);

        // Generate token
        $token = $this->generateToken($user);

        return [
            'user' => $this->formatUserResponse($user),
            'token' => $token
        ];
    }

    /**
     * Authenticate user login
     */
    public function login(string $identifier, string $password): array
    {
        // Find user by email or username
        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$user || !$user->isActive()) {
            throw new \InvalidArgumentException('Invalid credentials or account disabled');
        }

        // Verify password
        if (!password_verify($password, $user->password)) {
            throw new \InvalidArgumentException('Invalid credentials');
        }

        // Generate token
        $token = $this->generateToken($user);

        return [
            'user' => $this->formatUserResponse($user),
            'token' => $token
        ];
    }

    /**
     * Validate JWT token and return user
     */
    public function validateToken(string $token): User
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            $user = User::find($decoded->userId);
            
            if (!$user || !$user->isActive()) {
                throw new \Exception('User not found or inactive');
            }

            return $user;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Generate JWT token for user
     */
    public function generateToken(User $user): string
    {
        $payload = [
            'userId' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + $this->parseExpiration()
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * Refresh user token
     */
    public function refreshToken(string $token): string
    {
        $user = $this->validateToken($token);
        return $this->generateToken($user);
    }

    /**
     * Format user data for response (remove sensitive info)
     */
    private function formatUserResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'permissions' => $user->permissions,
            'isActive' => $user->isActive,
            'createdAt' => $user->createdAt->toISOString()
        ];
    }

    /**
     * Parse expiration string to seconds
     */
    private function parseExpiration(): int
    {
        $expiration = $this->jwtExpiration;
        
        if (is_numeric($expiration)) {
            return (int) $expiration;
        }

        // Parse strings like "7d", "24h", "60m"
        $unit = substr($expiration, -1);
        $value = (int) substr($expiration, 0, -1);

        switch ($unit) {
            case 'd':
                return $value * 24 * 60 * 60; // days to seconds
            case 'h':
                return $value * 60 * 60; // hours to seconds
            case 'm':
                return $value * 60; // minutes to seconds
            case 's':
                return $value; // seconds
            default:
                return 7 * 24 * 60 * 60; // default 7 days
        }
    }

    /**
     * Change user password
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!password_verify($currentPassword, $user->password)) {
            throw new \InvalidArgumentException('Current password is incorrect');
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->update(['password' => $hashedPassword]);

        return true;
    }

    /**
     * Reset password (admin function)
     */
    public function resetPassword(User $user, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->update(['password' => $hashedPassword]);

        return true;
    }
}
