<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * User Model
 * 
 * Represents a user in the system with authentication and role management.
 */
class User extends Model
{
    protected $table = 'users';
    
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'permissions',
        'isActive'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'permissions' => 'array',
        'isActive' => 'boolean',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime'
    ];

    // Relationships
    public function forms(): HasMany
    {
        return $this->hasMany(Form::class, 'createdBy');
    }

    // Role constants
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_FORM_MANAGER = 'form_manager';
    const ROLE_PAYMENT_APPROVER = 'payment_approver';
    const ROLE_SUBMISSION_VIEWER = 'submission_viewer';
    const ROLE_SUBMISSION_EDITOR = 'submission_editor';
    const ROLE_NOTIFICATION_MANAGER = 'notification_manager';

    // Helper methods
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    public function isActive(): bool
    {
        return $this->isActive ?? true;
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN
        ]);
    }

    public function canManageForms(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
            self::ROLE_FORM_MANAGER
        ]);
    }

    public function canApprovePayments(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
            self::ROLE_PAYMENT_APPROVER
        ]);
    }

    public function canViewSubmissions(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
            self::ROLE_SUBMISSION_VIEWER,
            self::ROLE_SUBMISSION_EDITOR,
            self::ROLE_FORM_MANAGER
        ]);
    }

    public function canEditSubmissions(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
            self::ROLE_SUBMISSION_EDITOR
        ]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('isActive', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
