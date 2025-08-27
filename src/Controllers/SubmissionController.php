<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Submission;
use App\Models\Form;

/**
 * @OA\Tag(
 *     name="Submissions",
 *     description="Form submission management"
 * )
 */
class SubmissionController
{
    /**
     * @OA\Get(
     *     path="/api/submissions",
     *     tags={"Submissions"},
     *     summary="Get all submissions",
     *     description="Retrieve paginated list of form submissions",
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
     *         name="formId",
     *         in="query",
     *         description="Filter by form ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"pending", "completed", "approved", "rejected"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submissions retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index($request)
    {
        try {
            $page = (int)($request->getParam('page') ?? 1);
            $limit = (int)($request->getParam('limit') ?? 20);
            $formId = $request->getParam('formId');
            $status = $request->getParam('status');
            $startDate = $request->getParam('startDate');
            $endDate = $request->getParam('endDate');

            $query = Submission::with(['form']);

            // Apply filters
            if ($formId) {
                $query->where('form_id', $formId);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }

            // Pagination
            $total = $query->count();
            $submissions = $query->orderBy('created_at', 'desc')
                               ->skip(($page - 1) * $limit)
                               ->take($limit)
                               ->get();

            // Calculate summary stats
            $summary = [
                'totalSubmissions' => $total,
                'completedPayments' => Submission::where('payment_status', 'completed')->count(),
                'pendingPayments' => Submission::where('payment_status', 'pending')->count(),
                'totalRevenue' => Submission::where('payment_status', 'completed')->sum('payment_amount') ?? 0
            ];

            return Response::success([
                'submissions' => $submissions,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ],
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve submissions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/submissions",
     *     tags={"Submissions"},
     *     summary="Submit form data",
     *     description="Create a new form submission (public endpoint)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"formId", "data"},
     *             @OA\Property(property="formId", type="integer", example=42),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 example={"fullName": "Jane Smith", "email": "jane@example.com", "phone": "+1234567890"}
     *             ),
     *             @OA\Property(
     *                 property="payment",
     *                 type="object",
     *                 @OA\Property(property="method", type="string", example="stripe"),
     *                 @OA\Property(property="amount", type="number", example=99.99),
     *                 @OA\Property(property="currency", type="string", example="USD")
     *             ),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 example={"source": "website", "referrer": "google"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Form submitted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Form not found"
     *     )
     * )
     */
    public function store($request)
    {
        try {
            $data = $request->getParsedBody();

            // Validate required fields
            if (!isset($data['formId']) || !isset($data['data'])) {
                return Response::error('Form ID and data are required', 400);
            }

            // Check if form exists and is active
            $form = Form::find($data['formId']);
            if (!$form) {
                return Response::error('Form not found', 404);
            }

            if ($form->status !== 'active') {
                return Response::error('Form is not active', 400);
            }

            // Validate form data against form fields
            $validationResult = $this->validateSubmissionData($form, $data['data']);
            if (!$validationResult['valid']) {
                return Response::error('Validation failed', 400, $validationResult['errors']);
            }

            // Generate unique ID and edit code
            $uniqueId = $this->generateUniqueId($form->title);
            $editCode = $this->generateEditCode();

            // Create submission
            $submission = new Submission();
            $submission->form_id = $data['formId'];
            $submission->unique_id = $uniqueId;
            $submission->data = json_encode($data['data']);
            $submission->edit_code = $editCode;
            $submission->status = 'pending';
            $submission->ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $submission->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Handle payment data
            if (isset($data['payment'])) {
                $submission->payment_method = $data['payment']['method'] ?? null;
                $submission->payment_amount = $data['payment']['amount'] ?? 0;
                $submission->payment_currency = $data['payment']['currency'] ?? 'USD';
                $submission->payment_status = 'pending';
            }

            // Handle metadata
            if (isset($data['metadata'])) {
                $submission->metadata = json_encode($data['metadata']);
            }

            $submission->save();

            // Generate URLs
            $baseUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();
            $editUrl = $baseUrl . '/submissions/' . $submission->unique_id . '?code=' . $editCode;
            $paymentUrl = isset($data['payment']) ? $baseUrl . '/payment/' . $submission->unique_id : null;

            return Response::success([
                'submissionId' => $submission->unique_id,
                'uniqueId' => $uniqueId,
                'status' => $submission->status,
                'editCode' => $editCode,
                'editUrl' => $editUrl,
                'paymentUrl' => $paymentUrl,
                'submittedAt' => $submission->created_at
            ], 'Form submitted successfully', 201);

        } catch (\Exception $e) {
            return Response::error('Failed to submit form: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/submissions/{id}",
     *     tags={"Submissions"},
     *     summary="Get submission by ID",
     *     description="Retrieve a specific submission",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Submission ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Submission not found"
     *     )
     * )
     */
    public function show($request)
    {
        try {
            $id = $request->getParam('id');
            
            $submission = Submission::with(['form'])->where('unique_id', $id)->first();
            
            if (!$submission) {
                return Response::error('Submission not found', 404);
            }

            return Response::success($submission);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/submissions/{id}/status",
     *     tags={"Submissions"},
     *     summary="Update submission status",
     *     description="Update the status of a submission",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Submission ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected", "completed"}),
     *             @OA\Property(property="adminNotes", type="string", example="Verified payment and documentation"),
     *             @OA\Property(property="notifyUser", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Submission not found"
     *     )
     * )
     */
    public function updateStatus($request)
    {
        try {
            $id = $request->getParam('id');
            $data = $request->getParsedBody();

            $submission = Submission::where('unique_id', $id)->first();
            
            if (!$submission) {
                return Response::error('Submission not found', 404);
            }

            if (!isset($data['status'])) {
                return Response::error('Status is required', 400);
            }

            $validStatuses = ['pending', 'approved', 'rejected', 'completed'];
            if (!in_array($data['status'], $validStatuses)) {
                return Response::error('Invalid status', 400);
            }

            $submission->status = $data['status'];
            
            if (isset($data['adminNotes'])) {
                $submission->admin_notes = $data['adminNotes'];
            }

            $submission->updated_at = date('Y-m-d H:i:s');
            $submission->save();

            // TODO: Send notification if notifyUser is true

            return Response::success([
                'id' => $submission->unique_id,
                'status' => $submission->status,
                'updatedAt' => $submission->updated_at
            ], 'Submission status updated successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to update submission status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get submissions by form ID
     */
    public function getByForm($request)
    {
        try {
            $formId = $request->getParam('formId');
            $page = (int)($request->getParam('page') ?? 1);
            $limit = (int)($request->getParam('limit') ?? 20);

            $submissions = Submission::where('form_id', $formId)
                                   ->orderBy('created_at', 'desc')
                                   ->skip(($page - 1) * $limit)
                                   ->take($limit)
                                   ->get();

            $total = Submission::where('form_id', $formId)->count();

            return Response::success([
                'submissions' => $submissions,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve submissions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get public submission (with edit code)
     */
    public function showPublic($request)
    {
        try {
            $uniqueId = $request->getParam('uniqueId');
            $editCode = $request->getParam('code');

            $submission = Submission::with(['form'])->where('unique_id', $uniqueId)->first();
            
            if (!$submission) {
                return Response::error('Submission not found', 404);
            }

            if ($editCode && $submission->edit_code !== $editCode) {
                return Response::error('Invalid edit code', 403);
            }

            // Remove sensitive data for public view
            $publicData = $submission->toArray();
            unset($publicData['edit_code']);
            unset($publicData['ip_address']);
            unset($publicData['user_agent']);

            return Response::success($publicData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update public submission (with edit code)
     */
    public function updatePublic($request)
    {
        try {
            $uniqueId = $request->getParam('uniqueId');
            $data = $request->getParsedBody();

            if (!isset($data['editCode'])) {
                return Response::error('Edit code is required', 400);
            }

            $submission = Submission::with(['form'])->where('unique_id', $uniqueId)->first();
            
            if (!$submission) {
                return Response::error('Submission not found', 404);
            }

            if ($submission->edit_code !== $data['editCode']) {
                return Response::error('Invalid edit code', 403);
            }

            // Check if form allows editing
            if (!$submission->form || !$submission->form->allow_edit) {
                return Response::error('Editing is not allowed for this form', 403);
            }

            // Validate new data
            if (isset($data['data'])) {
                $validationResult = $this->validateSubmissionData($submission->form, $data['data']);
                if (!$validationResult['valid']) {
                    return Response::error('Validation failed', 400, $validationResult['errors']);
                }

                $submission->data = json_encode($data['data']);
            }

            $submission->updated_at = date('Y-m-d H:i:s');
            $submission->save();

            return Response::success($submission, 'Submission updated successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to update submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify edit code
     */
    public function verifyEditCode($request)
    {
        try {
            $uniqueId = $request->getParam('uniqueId');
            $data = $request->getParsedBody();

            if (!isset($data['editCode'])) {
                return Response::error('Edit code is required', 400);
            }

            $submission = Submission::where('unique_id', $uniqueId)->first();
            
            if (!$submission) {
                return Response::error('Submission not found', 404);
            }

            $isValid = $submission->edit_code === $data['editCode'];

            return Response::success([
                'valid' => $isValid,
                'canEdit' => $isValid && $submission->form && $submission->form->allow_edit
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to verify edit code: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update submission
     */
    public function update($request)
    {
        try {
            $id = $request->getParam('id');
            $data = $request->getParsedBody();

            $submission = Submission::where('unique_id', $id)->first();
            
            if (!$submission) {
                return Response::error('Submission not found', 404);
            }

            // Update allowed fields
            if (isset($data['status'])) {
                $submission->status = $data['status'];
            }

            if (isset($data['adminNotes'])) {
                $submission->admin_notes = $data['adminNotes'];
            }

            if (isset($data['paymentStatus'])) {
                $submission->payment_status = $data['paymentStatus'];
            }

            $submission->updated_at = date('Y-m-d H:i:s');
            $submission->save();

            return Response::success($submission, 'Submission updated successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to update submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete submission
     */
    public function destroy($request)
    {
        try {
            $id = $request->getParam('id');
            
            $submission = Submission::where('unique_id', $id)->first();
            
            if (!$submission) {
                return Response::error('Submission not found', 404);
            }

            $submission->delete();

            return Response::success([], 'Submission deleted successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to delete submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate submission data against form fields
     */
    private function validateSubmissionData($form, $data)
    {
        $errors = [];
        $fields = json_decode($form->fields, true) ?? [];

        foreach ($fields as $field) {
            $fieldId = $field['id'] ?? $field['name'];
            $isRequired = $field['required'] ?? false;
            $fieldType = $field['type'] ?? 'text';

            // Check required fields
            if ($isRequired && (!isset($data[$fieldId]) || empty($data[$fieldId]))) {
                $errors[$fieldId] = 'This field is required';
                continue;
            }

            // Skip validation if field is not provided and not required
            if (!isset($data[$fieldId])) {
                continue;
            }

            $value = $data[$fieldId];

            // Type-specific validation
            switch ($fieldType) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$fieldId] = 'Invalid email format';
                    }
                    break;

                case 'number':
                    if (!is_numeric($value)) {
                        $errors[$fieldId] = 'Must be a number';
                    }
                    break;

                case 'tel':
                    if (!preg_match('/^[\+]?[\d\s\-\(\)]{10,}$/', $value)) {
                        $errors[$fieldId] = 'Invalid phone number format';
                    }
                    break;

                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[$fieldId] = 'Invalid URL format';
                    }
                    break;
            }

            // Length validation
            if (isset($field['validation'])) {
                $validation = $field['validation'];
                
                if (isset($validation['minLength']) && strlen($value) < $validation['minLength']) {
                    $errors[$fieldId] = "Must be at least {$validation['minLength']} characters";
                }
                
                if (isset($validation['maxLength']) && strlen($value) > $validation['maxLength']) {
                    $errors[$fieldId] = "Must not exceed {$validation['maxLength']} characters";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Generate unique submission ID
     */
    private function generateUniqueId($formTitle)
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $formTitle), 0, 4));
        $year = date('Y');
        $sequence = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        return $prefix . $year . '-' . $sequence;
    }

    /**
     * Generate edit code
     */
    private function generateEditCode()
    {
        return strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
    }
}
