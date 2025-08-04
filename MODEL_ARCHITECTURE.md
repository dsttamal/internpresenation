# PHP Backend Model Architecture

## Class Diagram

```
                           Form Builder PHP Backend
                              Model Architecture

    ┌─────────────────────────────────────────────────────────────────────────┐
    │                           Core Framework                                │
    └─────────────────────────────────────────────────────────────────────────┘
                                       │
           ┌─────────────────┬─────────┼─────────┬─────────────────┐
           │                 │                   │                 │
    ┌─────────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
    │ Application │   │   Router    │   │   Request   │   │  Response   │
    └─────────────┘   └─────────────┘   └─────────────┘   └─────────────┘
           │                 │                   │                 │
           └─────────────────┼─────────┬─────────┼─────────────────┘
                             │         │         │
    ┌─────────────────────────────────────────────────────────────────────────┐
    │                          Middleware Layer                               │
    └─────────────────────────────────────────────────────────────────────────┘
           │                 │         │         │                 │
    ┌─────────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
    │    CORS     │   │ RateLimit   │   │    Auth     │   │   Security  │
    │ Middleware  │   │ Middleware  │   │ Middleware  │   │ Middleware  │
    └─────────────┘   └─────────────┘   └─────────────┘   └─────────────┘
                                       │
    ┌─────────────────────────────────────────────────────────────────────────┐
    │                        Controller Layer                                 │
    └─────────────────────────────────────────────────────────────────────────┘
           │                 │                   │                 │
    ┌─────────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
    │    Auth     │   │    Form     │   │ Submission  │   │   Admin     │
    │ Controller  │   │ Controller  │   │ Controller  │   │ Controller  │
    └─────────────┘   └─────────────┘   └─────────────┘   └─────────────┘
           │                 │                   │                 │
    ┌─────────────────────────────────────────────────────────────────────────┐
    │                         Service Layer                                   │
    └─────────────────────────────────────────────────────────────────────────┘
           │                 │                   │                 │
    ┌─────────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
    │    Auth     │   │    Form     │   │ Submission  │   │   Payment   │
    │   Service   │   │   Service   │   │   Service   │   │   Service   │
    └─────────────┘   └─────────────┘   └─────────────┘   └─────────────┘
           │                 │                   │                 │
    ┌─────────────────────────────────────────────────────────────────────────┐
    │                          Model Layer                                    │
    └─────────────────────────────────────────────────────────────────────────┘
           │                 │                   │                 │
    ┌─────────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
    │    User     │   │    Form     │   │ Submission  │   │   Setting   │
    │    Model    │   │    Model    │   │    Model    │   │    Model    │
    └─────────────┘   └─────────────┘   └─────────────┘   └─────────────┘
```

## Model Classes Detail

### 🧑 User Model
```php
namespace App\Models;

class User extends Model {
    // Properties
    - id: int (PK)
    - username: string (unique)
    - email: string (unique) 
    - password: string (hashed)
    - role: enum
    - permissions: array (JSON)
    - isActive: boolean
    - createdAt: DateTime
    - updatedAt: DateTime
    
    // Relationships
    + forms(): HasMany
    
    // Role Constants
    const ROLE_USER = 'user'
    const ROLE_ADMIN = 'admin'
    const ROLE_SUPER_ADMIN = 'super_admin'
    const ROLE_FORM_MANAGER = 'form_manager'
    const ROLE_PAYMENT_APPROVER = 'payment_approver'
    const ROLE_SUBMISSION_VIEWER = 'submission_viewer'
    const ROLE_SUBMISSION_EDITOR = 'submission_editor'
    const ROLE_NOTIFICATION_MANAGER = 'notification_manager'
    
    // Methods
    + hasRole(role: string): boolean
    + hasAnyRole(roles: array): boolean
    + hasPermission(permission: string): boolean
    + isActive(): boolean
    + isAdmin(): boolean
    + canManageForms(): boolean
    + canApprovePayments(): boolean
    + canViewSubmissions(): boolean
    + canEditSubmissions(): boolean
    
    // Scopes
    + scopeActive(query)
    + scopeByRole(query, role: string)
}
```

### 📝 Form Model
```php
namespace App\Models;

class Form extends Model {
    // Properties
    - id: int (PK)
    - title: string
    - description: string|null
    - fields: array (JSON)
    - isActive: boolean
    - allowEditing: boolean
    - createdBy: int (FK -> users.id)
    - settings: array (JSON)
    - submissionCount: int
    - analytics: array (JSON)
    - customUrl: string|null (unique)
    - createdAt: DateTime
    - updatedAt: DateTime
    
    // Relationships
    + creator(): BelongsTo
    + submissions(): HasMany
    
    // Methods
    + isActive(): boolean
    + allowsEditing(): boolean
    + getFieldTypes(): array
    + hasRequiredFields(): boolean
    + getRequiredFields(): array
    + incrementSubmissionCount(): void
    + updateAnalytics(data: array): void
    
    // Scopes
    + scopeActive(query)
    + scopeByCreator(query, userId: int)
    + scopeByCustomUrl(query, customUrl: string)
    + scopeWithSubmissionCount(query)
}
```

### 📊 Submission Model
```php
namespace App\Models;

class Submission extends Model {
    // Properties
    - id: int (PK)
    - uniqueId: string (unique)
    - editCode: string
    - formId: int (FK -> forms.id)
    - data: array (JSON)
    - submitterInfo: array (JSON)
    - paymentInfo: array (JSON)
    - status: enum
    - files: array (JSON)
    - adminNotes: string|null
    - editHistory: array (JSON)
    - paymentMethod: enum
    - createdAt: DateTime
    - updatedAt: DateTime
    
    // Status Constants
    const STATUS_PENDING = 'pending'
    const STATUS_COMPLETED = 'completed'
    const STATUS_FAILED = 'failed'
    
    // Payment Method Constants
    const PAYMENT_CARD = 'card'
    const PAYMENT_STRIPE = 'stripe'
    const PAYMENT_BKASH = 'bkash'
    const PAYMENT_BANK_TRANSFER = 'bank_transfer'
    
    // Relationships
    + form(): BelongsTo
    
    // Methods
    + isPending(): boolean
    + isCompleted(): boolean
    + isFailed(): boolean
    + hasPaymentInfo(): boolean
    + getPaymentAmount(): float|null
    + getPaymentStatus(): string|null
    + addToEditHistory(changes: array, userId: int|null): void
    + updateStatus(status: string, notes: string|null): void
    + generateEditCode(): string
    
    // Scopes
    + scopeByStatus(query, status: string)
    + scopePending(query)
    + scopeCompleted(query)
    + scopeFailed(query)
    + scopeByForm(query, formId: int)
    + scopeByPaymentMethod(query, method: string)
    + scopeWithPayment(query)
    + scopeByDateRange(query, startDate: string, endDate: string)
}
```

## Service Classes Detail

### 🔐 AuthService
```php
namespace App\Services;

class AuthService {
    // Properties
    - jwtSecret: string
    - jwtExpiration: string
    
    // Methods
    + register(data: array): array
    + login(identifier: string, password: string): array
    + validateToken(token: string): User
    + generateToken(user: User): string
    + refreshToken(token: string): string
    + changePassword(user: User, currentPassword: string, newPassword: string): boolean
    + resetPassword(user: User, newPassword: string): boolean
    - formatUserResponse(user: User): array
    - parseExpiration(): int
}
```

### 📋 FormService
```php
namespace App\Services;

class FormService {
    // Methods
    + getAllForms(filters: array): array
    + getFormById(id: int, user: User|null): Form
    + getFormByCustomUrl(customUrl: string): Form
    + createForm(data: array, creator: User): Form
    + updateForm(id: int, data: array, user: User): Form
    + deleteForm(id: int, user: User): boolean
    + duplicateForm(id: int, user: User): Form
    + getFormAnalytics(id: int, user: User): array
    - validateFormData(data: array): void
    - validateCustomUrl(customUrl: string): void
    - canUserAccessForm(user: User, form: Form): boolean
    - canUserEditForm(user: User, form: Form): boolean
    - canUserDeleteForm(user: User, form: Form): boolean
    - calculateAverageSubmissionsPerDay(form: Form): float
    - getFieldUsageStats(form: Form): array
    - formatFormResponse(form: Form): array
}
```

## Controller Classes Detail

### 🔑 AuthController
```php
namespace App\Controllers;

class AuthController {
    // Properties
    - authService: AuthService
    
    // Methods
    + register(request: Request): Response
    + login(request: Request): Response
    + profile(request: Request): Response
    + refresh(request: Request): Response
    + changePassword(request: Request): Response
    + logout(request: Request): Response
    - validateRegistrationData(data: array): void
    - validateLoginData(data: array): void
    - validatePasswordChangeData(data: array): void
}
```

### 📝 FormController
```php
namespace App\Controllers;

class FormController {
    // Properties
    - formService: FormService
    - authService: AuthService
    
    // Methods
    + index(request: Request): Response
    + show(request: Request): Response
    + showByUrl(request: Request): Response
    + store(request: Request): Response
    + update(request: Request): Response
    + destroy(request: Request): Response
    + duplicate(request: Request): Response
    + analytics(request: Request): Response
    + toggleStatus(request: Request): Response
    + myForms(request: Request): Response
    - authenticateUser(request: Request): User
    - validateFormData(data: array): void
}
```

## Database Layer

### 🗄️ Database Configuration
```php
namespace Config;

class Database {
    // Properties
    - static capsule: Capsule|null
    - static initialized: boolean
    
    // Methods
    + static initialize(): void
    + static getConnection()
    + static getCapsule(): Capsule
    + static testConnection(): boolean
    + static getStats(): array
    + static raw(sql: string, bindings: array)
    + static beginTransaction(): void
    + static commit(): void
    + static rollback(): void
}
```

## Middleware Architecture

```
Request Flow Through Middleware Stack:

    [HTTP Request]
           │
           ▼
    ┌─────────────────┐
    │ ErrorHandler    │ ◄─── Catches all exceptions
    │ Middleware      │
    └─────────────────┘
           │
           ▼
    ┌─────────────────┐
    │ Security        │ ◄─── Adds security headers
    │ Middleware      │
    └─────────────────┘
           │
           ▼
    ┌─────────────────┐
    │ CORS            │ ◄─── Handles cross-origin requests
    │ Middleware      │
    └─────────────────┘
           │
           ▼
    ┌─────────────────┐
    │ RateLimit       │ ◄─── Prevents API abuse
    │ Middleware      │
    └─────────────────┘
           │
           ▼ (for protected routes)
    ┌─────────────────┐
    │ Auth            │ ◄─── Validates JWT tokens
    │ Middleware      │
    └─────────────────┘
           │
           ▼
    ┌─────────────────┐
    │ Route Handler   │ ◄─── Controller method
    │ (Controller)    │
    └─────────────────┘
           │
           ▼
    [HTTP Response]
```

## Data Validation Flow

```
Input Data Validation Process:

    [Client Input]
           │
           ▼
    ┌─────────────────┐
    │ Request Object  │ ◄─── Parse HTTP request
    └─────────────────┘
           │
           ▼
    ┌─────────────────┐
    │ Controller      │ ◄─── Initial validation
    │ Validation      │
    └─────────────────┘
           │
           ▼
    ┌─────────────────┐
    │ Service Layer   │ ◄─── Business logic validation
    │ Validation      │
    └─────────────────┘
           │
           ▼
    ┌─────────────────┐
    │ Model/ORM       │ ◄─── Database constraints
    │ Validation      │
    └─────────────────┘
           │
           ▼
    [Database Storage]
```

## File Structure Summary

```
phpbackend/
├── 📁 src/
│   ├── 📁 Core/              # Framework core classes
│   │   ├── Application.php   # Main app container
│   │   ├── Router.php        # HTTP routing
│   │   ├── Request.php       # HTTP request handling
│   │   └── Response.php      # HTTP response formatting
│   ├── 📁 Middleware/        # HTTP middleware
│   │   ├── AuthMiddleware.php
│   │   ├── CorsMiddleware.php
│   │   ├── SecurityMiddleware.php
│   │   └── RateLimitMiddleware.php
│   ├── 📁 Controllers/       # HTTP controllers
│   │   ├── AuthController.php
│   │   ├── FormController.php
│   │   └── AdminController.php
│   ├── 📁 Services/          # Business logic
│   │   ├── AuthService.php
│   │   ├── FormService.php
│   │   └── PaymentService.php
│   └── 📁 Models/            # Database models
│       ├── User.php
│       ├── Form.php
│       └── Submission.php
├── 📁 config/                # Configuration
│   └── Database.php
├── 📁 routes/                # Route definitions
│   └── api.php
├── 📁 database/              # Database files
│   └── schema.sql
└── 📁 public/                # Web root
    └── index.php
```

This comprehensive model architecture provides a solid foundation for the Form Builder application with clear separation of concerns, proper validation, and robust security measures.
