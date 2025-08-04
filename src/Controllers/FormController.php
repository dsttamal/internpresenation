<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\FormService;
use App\Services\AuthService;
use Respect\Validation\Validator as v;

/**
 * Form Controller
 * 
 * Handles form management endpoints including
 * CRUD operations, analytics, and form publishing.
 * 
 * @OA\Tag(
 *     name="Forms",
 *     description="Form creation, management, and publishing endpoints"
 * )
 */
class FormController
{
    private FormService $formService;
    private AuthService $authService;

    public function __construct()
    {
        $this->formService = new FormService();
        $this->authService = new AuthService();
    }

    /**
     * @OA\Get(
     *     path="/api/forms",
     *     tags={"Forms"},
     *     summary="Get all forms",
     *     description="Retrieve a paginated list of forms with optional filtering",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for form title or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="isActive",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="createdBy",
     *         in="query",
     *         description="Filter by creator user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Forms retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Forms retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="forms",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Form")
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=45),
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=20),
     *                 @OA\Property(property="totalPages", type="integer", example=3)
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
    public function index(Request $request): Response
    {
        try {
            $user = $this->authenticateUser($request);
            
            $filters = [
                'page' => (int) ($request->getQuery('page') ?? 1),
                'limit' => (int) ($request->getQuery('limit') ?? 20),
                'search' => $request->getQuery('search'),
                'isActive' => $request->getQuery('isActive'),
                'createdBy' => $request->getQuery('createdBy')
            ];

            // Non-admin users can only see their own forms
            if (!$user->isAdmin() && !$user->canManageForms()) {
                $filters['createdBy'] = $user->id;
            }

            $result = $this->formService->getAllForms($filters);

            return Response::success($result);

        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 401);
        }
    }

    /**
     * Get form by ID
     */
    public function show(Request $request): Response
    {
        try {
            $user = $this->authenticateUser($request);
            $id = (int) $request->getParam('id');

            $form = $this->formService->getFormById($id, $user);

            return Response::success(['form' => $form]);

        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 
                strpos($e->getMessage(), 'not found') !== false ? 404 : 401
            );
        }
    }

    /**
     * Get form by custom URL (public endpoint)
     */
    public function showByUrl(Request $request): Response
    {
        try {
            $customUrl = $request->getParam('customUrl');

            $form = $this->formService->getFormByCustomUrl($customUrl);

            return Response::success(['form' => $form]);

        } catch (\Exception $e) {
            return Response::notFound('Form not found');
        }
    }

    /**
     * Create new form
     */
    public function store(Request $request): Response
    {
        try {
            $user = $this->authenticateUser($request);
            $data = $request->getBody();

            // Validate input
            $this->validateFormData($data);

            $form = $this->formService->createForm($data, $user);

            return Response::created(['form' => $form], 'Form created successfully');

        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 401);
        }
    }

    /**
     * Update form
     */
    public function update(Request $request): Response
    {
        try {
            $user = $this->authenticateUser($request);
            $id = (int) $request->getParam('id');
            $data = $request->getBody();

            $form = $this->formService->updateForm($id, $data, $user);

            return Response::success(['form' => $form], 'Form updated successfully');

        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 
                strpos($e->getMessage(), 'not found') !== false ? 404 : 401
            );
        }
    }

    /**
     * Delete form
     */
    public function destroy(Request $request): Response
    {
        try {
            $user = $this->authenticateUser($request);
            $id = (int) $request->getParam('id');

            $this->formService->deleteForm($id, $user);

            return Response::success([], 'Form deleted successfully');

        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 
                strpos($e->getMessage(), 'not found') !== false ? 404 : 401
            );
        }
    }

    /**
     * Duplicate form
     */
    public function duplicate(Request $request): Response
    {
        try {
            $user = $this->authenticateUser($request);
            $id = (int) $request->getParam('id');

            $form = $this->formService->duplicateForm($id, $user);

            return Response::created(['form' => $form], 'Form duplicated successfully');

        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 
                strpos($e->getMessage(), 'not found') !== false ? 404 : 401
            );
        }
    }

    /**
     * Get form analytics
     */
    public function analytics(Request $request): Response
    {
        try {
            $user = $this->authenticateUser($request);
            $id = (int) $request->getParam('id');

            $analytics = $this->formService->getFormAnalytics($id, $user);

            return Response::success(['analytics' => $analytics]);

        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 
                strpos($e->getMessage(), 'not found') !== false ? 404 : 401
            );
        }
    }

    /**
     * Toggle form active status
     */
    public function toggleStatus(Request $request): Response
    {
        try {
            $user = $this->authenticateUser($request);
            $id = (int) $request->getParam('id');

            $form = $this->formService->getFormById($id, $user);
            
            $form = $this->formService->updateForm($id, [
                'isActive' => !$form->isActive
            ], $user);

            $status = $form->isActive ? 'activated' : 'deactivated';

            return Response::success(['form' => $form], "Form {$status} successfully");

        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 
                strpos($e->getMessage(), 'not found') !== false ? 404 : 401
            );
        }
    }

    /**
     * Get forms created by current user
     */
    public function myForms(Request $request): Response
    {
        try {
            $user = $this->authenticateUser($request);
            
            $filters = [
                'page' => (int) ($request->getQuery('page') ?? 1),
                'limit' => (int) ($request->getQuery('limit') ?? 20),
                'search' => $request->getQuery('search'),
                'isActive' => $request->getQuery('isActive'),
                'createdBy' => $user->id
            ];

            $result = $this->formService->getAllForms($filters);

            return Response::success($result);

        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 401);
        }
    }

    /**
     * Authenticate user from token
     */
    private function authenticateUser(Request $request)
    {
        $token = $request->getBearerToken();
        
        if (!$token) {
            throw new \Exception('Authentication required');
        }

        return $this->authService->validateToken($token);
    }

    /**
     * Validate form data
     */
    private function validateFormData(array $data): void
    {
        $errors = [];

        // Title validation
        if (!v::stringType()->notEmpty()->length(1, 255)->validate($data['title'] ?? '')) {
            $errors[] = 'Form title is required (max 255 characters)';
        }

        // Description validation (optional)
        if (isset($data['description']) && !v::stringType()->length(null, 1000)->validate($data['description'])) {
            $errors[] = 'Description must be less than 1000 characters';
        }

        // Fields validation
        if (!isset($data['fields']) || !is_array($data['fields']) || empty($data['fields'])) {
            $errors[] = 'At least one form field is required';
        } else {
            foreach ($data['fields'] as $index => $field) {
                if (!isset($field['type']) || !isset($field['label'])) {
                    $errors[] = "Field {$index}: type and label are required";
                }
                
                if (isset($field['label']) && !v::stringType()->notEmpty()->validate($field['label'])) {
                    $errors[] = "Field {$index}: label cannot be empty";
                }
            }
        }

        // Custom URL validation (optional)
        if (isset($data['customUrl'])) {
            if (!v::stringType()->regex('/^[a-zA-Z0-9-_]+$/')->length(3, 50)->validate($data['customUrl'])) {
                $errors[] = 'Custom URL must be 3-50 characters and contain only letters, numbers, hyphens, and underscores';
            }
        }

        // Settings validation (optional)
        if (isset($data['settings']) && !is_array($data['settings'])) {
            $errors[] = 'Settings must be an array';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
    }
}
