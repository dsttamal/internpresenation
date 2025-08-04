<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Form Model
 * 
 * Represents a dynamic form with fields and settings.
 */
class Form extends Model
{
    protected $table = 'forms';

    protected $fillable = [
        'title',
        'description',
        'fields',
        'isActive',
        'allowEditing',
        'createdBy',
        'settings',
        'submissionCount',
        'analytics',
        'customUrl'
    ];

    protected $casts = [
        'fields' => 'array',
        'settings' => 'array',
        'analytics' => 'array',
        'isActive' => 'boolean',
        'allowEditing' => 'boolean',
        'submissionCount' => 'integer',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime'
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createdBy');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'formId');
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->isActive ?? true;
    }

    public function allowsEditing(): bool
    {
        return $this->allowEditing ?? true;
    }

    public function getFieldTypes(): array
    {
        $fields = $this->fields ?? [];
        return array_unique(array_column($fields, 'type'));
    }

    public function hasRequiredFields(): bool
    {
        $fields = $this->fields ?? [];
        foreach ($fields as $field) {
            if ($field['required'] ?? false) {
                return true;
            }
        }
        return false;
    }

    public function getRequiredFields(): array
    {
        $fields = $this->fields ?? [];
        return array_filter($fields, function($field) {
            return $field['required'] ?? false;
        });
    }

    public function incrementSubmissionCount(): void
    {
        $this->increment('submissionCount');
    }

    public function updateAnalytics(array $data): void
    {
        $analytics = $this->analytics ?? [];
        $analytics = array_merge($analytics, $data);
        $this->update(['analytics' => $analytics]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('isActive', true);
    }

    public function scopeByCreator($query, int $userId)
    {
        return $query->where('createdBy', $userId);
    }

    public function scopeByCustomUrl($query, string $customUrl)
    {
        return $query->where('customUrl', $customUrl);
    }

    public function scopeWithSubmissionCount($query)
    {
        return $query->withCount('submissions');
    }
}
