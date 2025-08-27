<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\User;
use App\Models\Form;
use App\Models\Submission;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Administrative operations"
 * )
 */
class AdminController
{
    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     tags={"Admin"},
     *     summary="Get all users",
     *     description="Retrieve paginated list of users (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query", 
     *         description="Items per page",
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20)
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by user role",
     *         @OA\Schema(type="string", enum={"user", "admin", "super_admin"})
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by user status",
     *         @OA\Schema(type="string", enum={"active", "inactive"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function getUsers($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $page = (int)($request->getParam('page') ?? 1);
            $limit = (int)($request->getParam('limit') ?? 20);
            $role = $request->getParam('role');
            $status = $request->getParam('status');
            $search = $request->getParam('search');

            $query = User::query();

            // Apply filters
            if ($role) {
                $query->where('role', $role);
            }

            if ($status) {
                $isActive = $status === 'active';
                $query->where('is_active', $isActive);
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Pagination
            $total = $query->count();
            $users = $query->orderBy('created_at', 'desc')
                          ->skip(($page - 1) * $limit)
                          ->take($limit)
                          ->get();

            // Remove sensitive data
            $users = $users->map(function($user) {
                unset($user->password);
                return $user;
            });

            return Response::success([
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve users: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users",
     *     tags={"Admin"},
     *     summary="Create new user",
     *     description="Create a new user account (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=6),
     *             @OA\Property(property="role", type="string", enum={"user", "admin", "super_admin"}, example="user"),
     *             @OA\Property(property="isActive", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Email already exists"
     *     )
     * )
     */
    public function createUser($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $data = $request->getParsedBody();

            // Validate required fields
            $required = ['name', 'email', 'password', 'role'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return Response::error("Field '{$field}' is required", 400);
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return Response::error('Invalid email format', 400);
            }

            // Check if email already exists
            if (User::where('email', $data['email'])->exists()) {
                return Response::error('Email already exists', 409);
            }

            // Validate role
            $validRoles = ['user', 'admin', 'super_admin'];
            if (!in_array($data['role'], $validRoles)) {
                return Response::error('Invalid role', 400);
            }

            // Validate password
            if (strlen($data['password']) < 6) {
                return Response::error('Password must be at least 6 characters', 400);
            }

            // Create user
            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
            $user->role = $data['role'];
            $user->is_active = $data['isActive'] ?? true;
            $user->created_at = date('Y-m-d H:i:s');
            $user->save();

            // Remove password from response
            $userData = $user->toArray();
            unset($userData['password']);

            return Response::success($userData, 'User created successfully', 201);

        } catch (\Exception $e) {
            return Response::error('Failed to create user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users/{id}",
     *     tags={"Admin"},
     *     summary="Get user by ID",
     *     description="Retrieve a specific user (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function getUser($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $id = $request->getParam('id');
            $user = User::find($id);

            if (!$user) {
                return Response::error('User not found', 404);
            }

            // Remove password from response
            $userData = $user->toArray();
            unset($userData['password']);

            return Response::success($userData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{id}",
     *     tags={"Admin"},
     *     summary="Update user",
     *     description="Update user information (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="role", type="string", enum={"user", "admin", "super_admin"}),
     *             @OA\Property(property="isActive", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully"
     *     )
     * )
     */
    public function updateUser($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $id = $request->getParam('id');
            $data = $request->getParsedBody();

            $user = User::find($id);
            if (!$user) {
                return Response::error('User not found', 404);
            }

            // Update fields
            if (isset($data['name'])) {
                $user->name = $data['name'];
            }

            if (isset($data['email'])) {
                // Check if email is already taken by another user
                $existingUser = User::where('email', $data['email'])->where('id', '!=', $id)->first();
                if ($existingUser) {
                    return Response::error('Email already exists', 409);
                }
                $user->email = $data['email'];
            }

            if (isset($data['role'])) {
                $validRoles = ['user', 'admin', 'super_admin'];
                if (!in_array($data['role'], $validRoles)) {
                    return Response::error('Invalid role', 400);
                }
                $user->role = $data['role'];
            }

            if (isset($data['isActive'])) {
                $user->is_active = $data['isActive'];
            }

            $user->updated_at = date('Y-m-d H:i:s');
            $user->save();

            // Remove password from response
            $userData = $user->toArray();
            unset($userData['password']);

            return Response::success($userData, 'User updated successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     tags={"Admin"},
     *     summary="Delete user",
     *     description="Delete a user account (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully"
     *     )
     * )
     */
    public function deleteUser($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $id = $request->getParam('id');
            $user = User::find($id);

            if (!$user) {
                return Response::error('User not found', 404);
            }

            // Prevent deletion of super admin
            if ($user->role === 'super_admin') {
                return Response::error('Cannot delete super admin', 403);
            }

            $user->delete();

            return Response::success([], 'User deleted successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/users/{id}/toggle-status",
     *     tags={"Admin"},
     *     summary="Toggle user status",
     *     description="Toggle user active/inactive status (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User status toggled successfully"
     *     )
     * )
     */
    public function toggleUserStatus($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $id = $request->getParam('id');
            $user = User::find($id);

            if (!$user) {
                return Response::error('User not found', 404);
            }

            $user->is_active = !$user->is_active;
            $user->updated_at = date('Y-m-d H:i:s');
            $user->save();

            return Response::success([
                'id' => $user->id,
                'isActive' => $user->is_active
            ], 'User status toggled successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to toggle user status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users/{id}/reset-password",
     *     tags={"Admin"},
     *     summary="Reset user password",
     *     description="Reset user password (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"newPassword"},
     *             @OA\Property(property="newPassword", type="string", format="password", minLength=6)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully"
     *     )
     * )
     */
    public function resetUserPassword($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $id = $request->getParam('id');
            $data = $request->getParsedBody();

            if (!isset($data['newPassword']) || strlen($data['newPassword']) < 6) {
                return Response::error('New password must be at least 6 characters', 400);
            }

            $user = User::find($id);
            if (!$user) {
                return Response::error('User not found', 404);
            }

            $user->password = password_hash($data['newPassword'], PASSWORD_DEFAULT);
            $user->updated_at = date('Y-m-d H:i:s');
            $user->save();

            return Response::success([], 'Password reset successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to reset password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/stats",
     *     tags={"Admin"},
     *     summary="Get system statistics",
     *     description="Retrieve system statistics and metrics (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully"
     *     )
     * )
     */
    public function getSystemStats($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $stats = [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'admins' => User::where('role', 'admin')->count(),
                    'superAdmins' => User::where('role', 'super_admin')->count(),
                    'newThisMonth' => User::where('created_at', '>=', date('Y-m-01'))->count()
                ],
                'forms' => [
                    'total' => Form::count(),
                    'active' => Form::where('status', 'active')->count(),
                    'inactive' => Form::where('status', 'inactive')->count(),
                    'newThisMonth' => Form::where('created_at', '>=', date('Y-m-01'))->count()
                ],
                'submissions' => [
                    'total' => Submission::count(),
                    'pending' => Submission::where('status', 'pending')->count(),
                    'completed' => Submission::where('status', 'completed')->count(),
                    'approved' => Submission::where('status', 'approved')->count(),
                    'thisMonth' => Submission::where('created_at', '>=', date('Y-m-01'))->count(),
                    'today' => Submission::where('created_at', '>=', date('Y-m-d'))->count()
                ],
                'payments' => [
                    'totalRevenue' => Submission::where('payment_status', 'completed')->sum('payment_amount') ?? 0,
                    'completedPayments' => Submission::where('payment_status', 'completed')->count(),
                    'pendingPayments' => Submission::where('payment_status', 'pending')->count(),
                    'monthlyRevenue' => Submission::where('payment_status', 'completed')
                                                ->where('created_at', '>=', date('Y-m-01'))
                                                ->sum('payment_amount') ?? 0
                ]
            ];

            return Response::success($stats);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     tags={"Admin"},
     *     summary="Get dashboard data",
     *     description="Retrieve dashboard data with recent activities (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully"
     *     )
     * )
     */
    public function getDashboardData($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            // Get basic stats
            $stats = $this->getSystemStats($request)->getData()['data'];

            // Get recent activities
            $recentSubmissions = Submission::with(['form'])
                                          ->orderBy('created_at', 'desc')
                                          ->take(10)
                                          ->get();

            $recentUsers = User::orderBy('created_at', 'desc')
                              ->take(5)
                              ->get()
                              ->map(function($user) {
                                  unset($user->password);
                                  return $user;
                              });

            $recentForms = Form::orderBy('created_at', 'desc')
                              ->take(5)
                              ->get();

            return Response::success([
                'stats' => $stats,
                'recent' => [
                    'submissions' => $recentSubmissions,
                    'users' => $recentUsers,
                    'forms' => $recentForms
                ]
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve dashboard data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/settings",
     *     tags={"Admin"},
     *     summary="Get system settings",
     *     description="Retrieve system settings (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Settings retrieved successfully"
     *     )
     * )
     */
    public function getSettings($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            // Mock settings - in real app, these would come from a settings table or config
            $settings = [
                'site' => [
                    'name' => 'Form Builder',
                    'description' => 'Professional form building and submission management',
                    'logo' => null,
                    'favicon' => null
                ],
                'email' => [
                    'from_name' => 'Form Builder',
                    'from_email' => 'noreply@formbuilder.com',
                    'smtp_host' => null,
                    'smtp_port' => 587,
                    'smtp_encryption' => 'tls',
                    'smtp_username' => null,
                    'smtp_password' => null
                ],
                'payment' => [
                    'stripe_enabled' => true,
                    'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'] ?? null,
                    'stripe_webhook_secret' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null,
                    'bkash_enabled' => true,
                    'bank_transfer_enabled' => true
                ],
                'security' => [
                    'jwt_expiry' => 3600,
                    'rate_limit_requests' => 1000,
                    'rate_limit_window' => 900,
                    'max_file_size' => '10MB',
                    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']
                ]
            ];

            return Response::success($settings);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/settings",
     *     tags={"Admin"},
     *     summary="Update system settings",
     *     description="Update system settings (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="site", type="object"),
     *             @OA\Property(property="email", type="object"),
     *             @OA\Property(property="payment", type="object"),
     *             @OA\Property(property="security", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Settings updated successfully"
     *     )
     * )
     */
    public function updateSettings($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $data = $request->getParsedBody();

            // In a real application, you would save these to a settings table
            // For now, we'll just return success
            
            return Response::success($data, 'Settings updated successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to update settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get public settings (no authentication required)
     */
    public function getPublicSettings($request)
    {
        try {
            $settings = [
                'site' => [
                    'name' => 'Form Builder',
                    'description' => 'Professional form building and submission management'
                ],
                'features' => [
                    'allowRegistration' => true,
                    'allowGuestSubmissions' => true,
                    'maxFormsPerUser' => 10
                ]
            ];

            return Response::success($settings);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve public settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if user is admin
     */
    private function isAdmin($request)
    {
        // Get user from JWT token (this would be implemented in middleware)
        // For now, assume we have a way to get current user
        $user = $this->getCurrentUser($request);
        
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }

    /**
     * Get current user from request (mock implementation)
     */
    private function getCurrentUser($request)
    {
        // This would be implemented using JWT middleware
        // For now, return a mock admin user
        return (object)[
            'id' => 1,
            'role' => 'admin',
            'email' => 'admin@example.com'
        ];
    }
}
