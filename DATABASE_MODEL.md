# Database Model Documentation

## Overview
The Form Builder application uses a MySQL database with the following core entities:
- **Users** - System users with role-based permissions
- **Forms** - Dynamic form definitions
- **Submissions** - Form submission data with payment info
- **BkashTokens** - bKash payment service tokens
- **Settings** - Application configuration

## Entity Relationship Diagram (ERD)

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│     USERS       │       │     FORMS       │       │   SUBMISSIONS   │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ id (PK)         │──────<│ createdBy (FK)  │>─────<│ formId (FK)     │
│ username        │       │ id (PK)         │       │ id (PK)         │
│ email           │       │ title           │       │ uniqueId        │
│ password        │       │ description     │       │ editCode        │
│ role            │       │ fields (JSON)   │       │ data (JSON)     │
│ permissions     │       │ isActive        │       │ submitterInfo   │
│ isActive        │       │ allowEditing    │       │ paymentInfo     │
│ createdAt       │       │ settings (JSON) │       │ status          │
│ updatedAt       │       │ submissionCount │       │ files (JSON)    │
└─────────────────┘       │ analytics       │       │ adminNotes      │
                          │ customUrl       │       │ editHistory     │
                          │ createdAt       │       │ paymentMethod   │
                          │ updatedAt       │       │ createdAt       │
                          └─────────────────┘       │ updatedAt       │
                                                    └─────────────────┘

┌─────────────────┐       ┌─────────────────┐
│  BKASH_TOKENS   │       │    SETTINGS     │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │       │ id (PK)         │
│ service         │       │ key             │
│ authToken       │       │ value (JSON)    │
│ refreshToken    │       │ category        │
│ tokenExpiresAt  │       │ isActive        │
│ lastTokenCall   │       │ createdAt       │
│ tokenCallCount  │       │ updatedAt       │
│ isActive        │       └─────────────────┘
│ createdAt       │
│ updatedAt       │
└─────────────────┘
```

## Table Details

### 1. Users Table
**Purpose**: Store user accounts and authentication information

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) PK | Auto-incrementing primary key |
| username | VARCHAR(30) | Unique username (3-30 chars) |
| email | VARCHAR(255) | Unique email address |
| password | VARCHAR(255) | Bcrypt hashed password |
| role | ENUM | User role (see roles below) |
| permissions | JSON | Additional permissions array |
| isActive | TINYINT(1) | Account active status |
| createdAt | DATETIME | Account creation timestamp |
| updatedAt | DATETIME | Last update timestamp |

**Roles Available**:
- `user` - Regular user
- `admin` - Administrator
- `super_admin` - Super administrator
- `form_manager` - Can manage all forms
- `payment_approver` - Can approve payments
- `submission_viewer` - Can view submissions
- `submission_editor` - Can edit submissions
- `notification_manager` - Manages notifications

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE KEY (`username`)
- UNIQUE KEY (`email`)

### 2. Forms Table
**Purpose**: Store dynamic form definitions and metadata

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) PK | Auto-incrementing primary key |
| title | VARCHAR(255) | Form title |
| description | TEXT | Optional form description |
| fields | JSON | Form field definitions array |
| isActive | TINYINT(1) | Form active status |
| allowEditing | TINYINT(1) | Allow submission editing |
| createdBy | INT(11) FK | User who created the form |
| settings | JSON | Form-specific settings |
| submissionCount | INT(11) | Cached submission count |
| analytics | JSON | Form analytics data |
| customUrl | VARCHAR(255) | Custom public URL slug |
| createdAt | DATETIME | Form creation timestamp |
| updatedAt | DATETIME | Last update timestamp |

**Field Definition Structure** (JSON):
```json
[
  {
    "id": "field_1",
    "type": "text",
    "label": "Full Name",
    "required": true,
    "placeholder": "Enter your full name",
    "validation": {
      "minLength": 2,
      "maxLength": 100
    }
  },
  {
    "id": "field_2",
    "type": "email",
    "label": "Email Address",
    "required": true
  }
]
```

**Settings Structure** (JSON):
```json
{
  "submitRedirect": "https://example.com/thank-you",
  "emailNotifications": true,
  "requirePayment": true,
  "paymentAmount": 100.00,
  "currency": "BDT"
}
```

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE KEY (`customUrl`)
- KEY (`createdAt`)
- KEY (`createdBy`, `isActive`)

### 3. Submissions Table
**Purpose**: Store form submission data and payment information

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) PK | Auto-incrementing primary key |
| uniqueId | VARCHAR(32) | Unique submission identifier |
| editCode | VARCHAR(6) | 6-digit edit code for users |
| formId | INT(11) FK | Reference to form |
| data | JSON | Submitted form data |
| submitterInfo | JSON | Submitter metadata |
| paymentInfo | JSON | Payment transaction details |
| status | ENUM | Submission status |
| files | JSON | Uploaded file references |
| adminNotes | TEXT | Admin notes and comments |
| editHistory | JSON | Edit history log |
| paymentMethod | ENUM | Payment method used |
| createdAt | DATETIME | Submission timestamp |
| updatedAt | DATETIME | Last update timestamp |

**Status Values**:
- `pending` - Awaiting processing
- `completed` - Successfully processed
- `failed` - Processing failed

**Payment Methods**:
- `card` - Credit/Debit card
- `stripe` - Stripe payment
- `bkash` - bKash mobile payment
- `bank_transfer` - Bank transfer

**Data Structure** (JSON):
```json
{
  "field_1": "John Doe",
  "field_2": "john@example.com",
  "field_3": "1990-01-01"
}
```

**Payment Info Structure** (JSON):
```json
{
  "amount": 100.00,
  "currency": "BDT",
  "transactionId": "txn_123456",
  "status": "completed",
  "gateway": "stripe",
  "gatewayResponse": {
    "paymentIntentId": "pi_123456",
    "chargeId": "ch_123456"
  }
}
```

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE KEY (`uniqueId`)
- KEY (`formId`, `createdAt`)
- KEY (`status`)
- KEY (`editCode`)

### 4. BkashTokens Table
**Purpose**: Store bKash API authentication tokens

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) PK | Auto-incrementing primary key |
| service | VARCHAR(50) | Service identifier |
| authToken | TEXT | bKash authentication token |
| refreshToken | TEXT | bKash refresh token |
| tokenExpiresAt | DATETIME | Token expiration time |
| lastTokenCall | DATETIME | Last API call timestamp |
| tokenCallCount | INT(11) | API call count in current hour |
| isActive | TINYINT(1) | Token active status |
| createdAt | DATETIME | Token creation timestamp |
| updatedAt | DATETIME | Last update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE KEY (`service`)

### 5. Settings Table
**Purpose**: Store application configuration settings

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) PK | Auto-incrementing primary key |
| key | VARCHAR(100) | Setting key identifier |
| value | JSON | Setting value (flexible format) |
| category | VARCHAR(50) | Setting category |
| isActive | TINYINT(1) | Setting active status |
| createdAt | DATETIME | Setting creation timestamp |
| updatedAt | DATETIME | Last update timestamp |

**Common Settings**:
```json
{
  "key": "app_name",
  "value": "BSMMU Alumni Form Builder",
  "category": "general"
}

{
  "key": "payment_methods",
  "value": ["stripe", "bkash", "bank_transfer"],
  "category": "payment"
}

{
  "key": "file_upload_max_size",
  "value": 10485760,
  "category": "upload"
}
```

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE KEY (`key`)

## Relationships

### 1. User → Forms (One-to-Many)
- One user can create multiple forms
- Foreign Key: `forms.createdBy` → `users.id`
- Constraint: ON DELETE NO ACTION (preserve forms when user is deleted)

### 2. Form → Submissions (One-to-Many)
- One form can have multiple submissions
- Foreign Key: `submissions.formId` → `forms.id`
- Constraint: ON DELETE NO ACTION (preserve submissions when form is deleted)

## Data Types and Constraints

### JSON Fields
All JSON fields store structured data:
- **forms.fields**: Array of field definitions
- **forms.settings**: Form configuration object
- **forms.analytics**: Analytics data object
- **submissions.data**: Form submission data object
- **submissions.submitterInfo**: Submitter metadata
- **submissions.paymentInfo**: Payment transaction details
- **submissions.files**: File upload references
- **submissions.editHistory**: Edit log array
- **users.permissions**: Additional permissions array
- **settings.value**: Flexible setting value

### Validation Rules
- **Email**: Must be valid email format
- **Username**: 3-30 characters, alphanumeric + underscore
- **Password**: Minimum 6 characters (hashed with bcrypt)
- **Custom URL**: 3-50 characters, alphanumeric + hyphens/underscores
- **Edit Code**: Exactly 6 digits
- **Unique ID**: 32-character unique identifier

## Performance Considerations

### Indexes for Query Optimization
- **Users**: Primary key, unique constraints on email/username
- **Forms**: Composite index on (createdBy, isActive) for user's active forms
- **Submissions**: Composite index on (formId, createdAt) for form submissions by date
- **Submissions**: Index on status for filtering
- **Submissions**: Index on uniqueId for quick lookups

### Query Patterns
1. **Get user's forms**: `WHERE createdBy = ? AND isActive = 1`
2. **Get form submissions**: `WHERE formId = ? ORDER BY createdAt DESC`
3. **Find submission by unique ID**: `WHERE uniqueId = ?`
4. **Get pending submissions**: `WHERE status = 'pending'`
5. **Admin dashboard stats**: Aggregate queries on submissions table

### Storage Considerations
- JSON fields provide flexibility but require careful querying
- File uploads stored as references, actual files on filesystem
- Edit history can grow large over time - consider archiving
- Analytics data should be aggregated periodically

## Security Measures

### Data Protection
- **Password Hashing**: bcrypt with cost factor 10
- **Sensitive Data**: No plain text passwords stored
- **Access Control**: Role-based permissions system
- **Input Validation**: Server-side validation for all inputs

### File Security
- **Upload Validation**: MIME type and size checking
- **Path Security**: Prevent directory traversal
- **Access Control**: Protected file access through API

### API Security
- **JWT Tokens**: Stateless authentication
- **Rate Limiting**: Prevent API abuse
- **CORS**: Controlled cross-origin access
- **Input Sanitization**: Prevent SQL injection and XSS
