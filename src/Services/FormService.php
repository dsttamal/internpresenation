<?php

namespace App\Services;

use App\Models\Form;
use App\Models\User;

/**
 * Form Service
 * 
 * Handles form management operations including CRUD operations
 * and form-specific business logic.
 */
class FormService
{
    /**
     * Get all forms with optional filtering
     */
    public function getAllForms(array $filters = []): array
    {
        $query = Form::with('creator:id,username,email');

        // Apply filters
        if (isset($filters['isActive'])) {
            $query->where('isActive', $filters['isActive']);
        }

        if (isset($filters['createdBy'])) {
            $query->where('createdBy', $filters['createdBy']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Order by creation date (newest first)
        $query->orderBy('createdAt', 'desc');

        // Pagination
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 20;
        $offset = ($page - 1) * $limit;

        $total = $query->count();
        $forms = $query->offset($offset)->limit($limit)->get();

        return [
            'forms' => $forms->map(function($form) {
                return $this->formatFormResponse($form);
            }),
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get form by ID
     */
    public function getFormById(int $id, ?User $user = null): Form
    {
        $form = Form::with('creator:id,username,email')->find($id);

        if (!$form) {
            throw new \Exception('Form not found');
        }

        // Check permissions
        if ($user && !$this->canUserAccessForm($user, $form)) {
            throw new \Exception('Unauthorized access to form');
        }

        return $form;
    }

    /**
     * Get form by custom URL
     */
    public function getFormByCustomUrl(string $customUrl): Form
    {
        $form = Form::where('customUrl', $customUrl)
            ->where('isActive', true)
            ->first();

        if (!$form) {
            throw new \Exception('Form not found');
        }

        return $form;
    }

    /**
     * Create new form
     */
    public function createForm(array $data, User $creator): Form
    {
        // Validate required fields
        $this->validateFormData($data);

        // Check if custom URL is unique
        if (isset($data['customUrl'])) {
            $this->validateCustomUrl($data['customUrl']);
        }

        $form = Form::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'fields' => $data['fields'],
            'isActive' => $data['isActive'] ?? true,
            'allowEditing' => $data['allowEditing'] ?? true,
            'createdBy' => $creator->id,
            'settings' => $data['settings'] ?? [],
            'customUrl' => $data['customUrl'] ?? null,
            'submissionCount' => 0,
            'analytics' => []
        ]);

        return $form;
    }

    /**
     * Update form
     */
    public function updateForm(int $id, array $data, User $user): Form
    {
        $form = $this->getFormById($id, $user);

        // Check if user can edit this form
        if (!$this->canUserEditForm($user, $form)) {
            throw new \Exception('Unauthorized to edit this form');
        }

        // Validate custom URL if being updated
        if (isset($data['customUrl']) && $data['customUrl'] !== $form->customUrl) {
            $this->validateCustomUrl($data['customUrl']);
        }

        // Update allowed fields
        $allowedFields = ['title', 'description', 'fields', 'isActive', 'allowEditing', 'settings', 'customUrl'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        $form->update($updateData);

        return $form->fresh();
    }

    /**
     * Delete form
     */
    public function deleteForm(int $id, User $user): bool
    {
        $form = $this->getFormById($id, $user);

        // Check if user can delete this form
        if (!$this->canUserDeleteForm($user, $form)) {
            throw new \Exception('Unauthorized to delete this form');
        }

        // Check if form has submissions
        if ($form->submissions()->exists()) {
            throw new \Exception('Cannot delete form with existing submissions');
        }

        return $form->delete();
    }

    /**
     * Duplicate form
     */
    public function duplicateForm(int $id, User $user): Form
    {
        $originalForm = $this->getFormById($id, $user);

        if (!$this->canUserAccessForm($user, $originalForm)) {
            throw new \Exception('Unauthorized to duplicate this form');
        }

        $newForm = Form::create([
            'title' => $originalForm->title . ' (Copy)',
            'description' => $originalForm->description,
            'fields' => $originalForm->fields,
            'isActive' => false, // Start as inactive
            'allowEditing' => $originalForm->allowEditing,
            'createdBy' => $user->id,
            'settings' => $originalForm->settings,
            'customUrl' => null, // Don't copy custom URL
            'submissionCount' => 0,
            'analytics' => []
        ]);

        return $newForm;
    }

    /**
     * Get form analytics
     */
    public function getFormAnalytics(int $id, User $user): array
    {
        $form = $this->getFormById($id, $user);

        if (!$this->canUserAccessForm($user, $form)) {
            throw new \Exception('Unauthorized access to form analytics');
        }

        $submissions = $form->submissions();

        return [
            'totalSubmissions' => $submissions->count(),
            'pendingSubmissions' => $submissions->pending()->count(),
            'completedSubmissions' => $submissions->completed()->count(),
            'failedSubmissions' => $submissions->failed()->count(),
            'submissionsToday' => $submissions->whereDate('createdAt', today())->count(),
            'submissionsThisWeek' => $submissions->whereBetween('createdAt', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'submissionsThisMonth' => $submissions->whereMonth('createdAt', now()->month)->count(),
            'averageSubmissionsPerDay' => $this->calculateAverageSubmissionsPerDay($form),
            'fieldUsage' => $this->getFieldUsageStats($form)
        ];
    }

    /**
     * Validate form data
     */
    private function validateFormData(array $data): void
    {
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Form title is required');
        }

        if (empty($data['fields']) || !is_array($data['fields'])) {
            throw new \InvalidArgumentException('Form fields are required');
        }

        // Validate field structure
        foreach ($data['fields'] as $field) {
            if (!isset($field['type']) || !isset($field['label'])) {
                throw new \InvalidArgumentException('Each field must have a type and label');
            }
        }
    }

    /**
     * Validate custom URL
     */
    private function validateCustomUrl(string $customUrl): void
    {
        if (Form::where('customUrl', $customUrl)->exists()) {
            throw new \InvalidArgumentException('Custom URL already exists');
        }

        // Validate URL format
        if (!preg_match('/^[a-zA-Z0-9-_]+$/', $customUrl)) {
            throw new \InvalidArgumentException('Custom URL can only contain letters, numbers, hyphens, and underscores');
        }
    }

    /**
     * Check if user can access form
     */
    private function canUserAccessForm(User $user, Form $form): bool
    {
        // Super admin and admin can access all forms
        if ($user->isAdmin()) {
            return true;
        }

        // Form managers can access all forms
        if ($user->canManageForms()) {
            return true;
        }

        // Users can access their own forms
        return $form->createdBy === $user->id;
    }

    /**
     * Check if user can edit form
     */
    private function canUserEditForm(User $user, Form $form): bool
    {
        // Super admin and admin can edit all forms
        if ($user->isAdmin()) {
            return true;
        }

        // Form managers can edit all forms
        if ($user->canManageForms()) {
            return true;
        }

        // Users can edit their own forms
        return $form->createdBy === $user->id;
    }

    /**
     * Check if user can delete form
     */
    private function canUserDeleteForm(User $user, Form $form): bool
    {
        // Only super admin, admin, and form creator can delete
        if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
            return true;
        }

        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }

        return $form->createdBy === $user->id;
    }

    /**
     * Calculate average submissions per day
     */
    private function calculateAverageSubmissionsPerDay(Form $form): float
    {
        $daysSinceCreation = $form->createdAt->diffInDays(now()) + 1;
        return round($form->submissionCount / $daysSinceCreation, 2);
    }

    /**
     * Get field usage statistics
     */
    private function getFieldUsageStats(Form $form): array
    {
        $fields = $form->fields ?? [];
        $stats = [];

        foreach ($fields as $field) {
            $stats[$field['label']] = [
                'type' => $field['type'],
                'required' => $field['required'] ?? false,
                'usage_count' => 0 // This would need to be calculated from submissions
            ];
        }

        return $stats;
    }

    /**
     * Format form response
     */
    private function formatFormResponse(Form $form): array
    {
        return [
            'id' => $form->id,
            'title' => $form->title,
            'description' => $form->description,
            'fields' => $form->fields,
            'isActive' => $form->isActive,
            'allowEditing' => $form->allowEditing,
            'settings' => $form->settings,
            'submissionCount' => $form->submissionCount,
            'customUrl' => $form->customUrl,
            'createdAt' => $form->createdAt->toISOString(),
            'updatedAt' => $form->updatedAt->toISOString(),
            'creator' => $form->creator ? [
                'id' => $form->creator->id,
                'username' => $form->creator->username,
                'email' => $form->creator->email
            ] : null
        ];
    }
}
