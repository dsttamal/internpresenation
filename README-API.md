# Form Builder PHP Backend - API Documentation

This document provides comprehensive information about the Form Builder PHP backend API, including all controllers, models, and endpoints.

## 🚀 Quick Start

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL/PostgreSQL database
- Web server (Apache/Nginx) or PHP built-in server

### Installation
1. Install dependencies:
   ```bash
   composer install
   ```

2. Fix dependencies (if needed):
   ```bash
   # Linux/Mac
   ./fix-dependencies.sh
   
   # Windows
   fix-dependencies.bat
   ```

3. Set up environment variables (copy `.env.example` to `.env`)

4. Start the server:
   ```bash
   # Development server
   php -S localhost:5000 -t public
   
   # Or use web server pointing to /public directory
   ```

### API Documentation
- **Swagger UI**: http://localhost:5000/api/docs
- **OpenAPI JSON**: http://localhost:5000/api/docs/json
- **OpenAPI YAML**: http://localhost:5000/api/docs/yaml

## 📁 Project Structure

```
phpbackend/
├── config/                 # Configuration files
│   └── Database.php       # Database configuration
├── public/                # Public directory (document root)
│   └── index.php          # Application entry point
├── routes/                # Route definitions
│   └── api.php           # API routes
├── src/                   # Source code
│   ├── Controllers/       # API controllers
│   ├── Models/           # Data models
│   ├── Core/             # Core framework classes
│   └── Middleware/       # Middleware classes
├── exports/              # Generated export files
├── uploads/              # File uploads
├── composer.json         # Dependencies
└── README.md            # This file
```

## 🎯 Controllers

### 1. AuthController
Handles user authentication and authorization.

**Endpoints:**
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `GET /api/auth/profile` - Get user profile
- `POST /api/auth/refresh` - Refresh JWT token
- `POST /api/auth/change-password` - Change password
- `POST /api/auth/logout` - User logout

### 2. FormController
Manages dynamic forms and their configuration.

**Endpoints:**
- `GET /api/forms` - List all forms
- `POST /api/forms` - Create new form
- `GET /api/forms/my` - Get user's forms
- `GET /api/forms/{id}` - Get specific form
- `PUT /api/forms/{id}` - Update form
- `DELETE /api/forms/{id}` - Delete form
- `POST /api/forms/{id}/duplicate` - Duplicate form
- `GET /api/forms/{id}/analytics` - Form analytics
- `PATCH /api/forms/{id}/toggle-status` - Toggle form status
- `GET /api/forms/public/{customUrl}` - Get form by custom URL

### 3. SubmissionController
Handles form submissions and their management.

**Endpoints:**
- `GET /api/submissions` - List submissions
- `POST /api/submissions` - Create submission
- `GET /api/submissions/{id}` - Get specific submission
- `PUT /api/submissions/{id}` - Update submission
- `DELETE /api/submissions/{id}` - Delete submission
- `PATCH /api/submissions/{id}/status` - Update submission status
- `GET /api/submissions/form/{formId}` - Get submissions by form
- `GET /api/submissions/public/{uniqueId}` - Get public submission
- `PUT /api/submissions/public/{uniqueId}` - Update public submission
- `POST /api/submissions/public/{uniqueId}/verify-edit-code` - Verify edit code

### 4. PaymentController
Manages payment processing and methods.

**Endpoints:**
- `POST /api/payment/stripe/create-intent` - Create Stripe payment intent
- `POST /api/payment/stripe/confirm` - Confirm Stripe payment
- `POST /api/payment/stripe/webhook` - Stripe webhook
- `POST /api/payment/bank-transfer` - Bank transfer payment
- `PATCH /api/payment/bank-transfer/{id}/approve` - Approve bank transfer
- `PATCH /api/payment/bank-transfer/{id}/reject` - Reject bank transfer
- `POST /api/bkash/create` - Create bKash payment
- `POST /api/bkash/execute` - Execute bKash payment
- `POST /api/bkash/query` - Query bKash payment
- `POST /api/bkash/refund` - Refund bKash payment
- `POST /api/upload/payment-receipt` - Upload payment receipt
- `GET /api/upload/files/{filename}` - Get uploaded file
- `GET /api/settings/payment-methods` - Get payment methods

### 5. AdminController
Administrative operations and user management.

**Endpoints:**
- `GET /api/admin/users` - List users
- `POST /api/admin/users` - Create user
- `GET /api/admin/users/{id}` - Get user
- `PUT /api/admin/users/{id}` - Update user
- `DELETE /api/admin/users/{id}` - Delete user
- `PATCH /api/admin/users/{id}/toggle-status` - Toggle user status
- `POST /api/admin/users/{id}/reset-password` - Reset user password
- `GET /api/admin/settings` - Get system settings
- `PUT /api/admin/settings` - Update system settings
- `GET /api/admin/stats` - Get system statistics
- `GET /api/admin/dashboard` - Get dashboard data
- `GET /api/settings` - Get public settings

### 6. ExportController
Data export functionality (CSV, PDF).

**Endpoints:**
- `POST /api/export/csv` - Export data as CSV
- `POST /api/export/pdf` - Export data as PDF
- `GET /api/export/download/{filename}` - Download exported file

### 7. SimpleSwaggerController
API documentation and Swagger UI.

**Endpoints:**
- `GET /api/docs` - Swagger UI interface
- `GET /api/docs/json` - OpenAPI JSON specification
- `GET /api/docs/yaml` - OpenAPI YAML specification

## 📊 Models

### 1. User
Represents system users with authentication and role management.

**Fields:**
- `id` - Primary key
- `username` - Unique username
- `email` - Email address
- `password` - Hashed password
- `role` - User role (user, admin, super_admin, etc.)
- `permissions` - Array of permissions
- `isActive` - Account status
- `createdAt`, `updatedAt` - Timestamps

**Roles:**
- `user` - Regular user
- `admin` - Administrator
- `super_admin` - Super administrator
- `form_manager` - Form management permissions
- `payment_approver` - Payment approval permissions
- `submission_viewer` - View submissions
- `submission_editor` - Edit submissions

### 2. Form
Represents dynamic forms with fields and configuration.

**Fields:**
- `id` - Primary key
- `title` - Form title
- `description` - Form description
- `fields` - Array of form fields (JSON)
- `isActive` - Form status
- `allowEditing` - Allow submission editing
- `createdBy` - User who created the form
- `settings` - Form settings (JSON)
- `submissionCount` - Number of submissions
- `analytics` - Form analytics data (JSON)
- `customUrl` - Custom URL slug
- `createdAt`, `updatedAt` - Timestamps

### 3. Submission
Represents form submissions with data and payment information.

**Fields:**
- `id` - Primary key
- `uniqueId` - Unique identifier for public access
- `editCode` - Code for editing submissions
- `formId` - Related form ID
- `data` - Submission data (JSON)
- `submitterInfo` - Submitter information (JSON)
- `paymentInfo` - Payment details (JSON)
- `status` - Submission status
- `files` - Attached files (JSON)
- `adminNotes` - Administrative notes
- `editHistory` - Edit history (JSON)
- `paymentMethod` - Payment method used
- `createdAt`, `updatedAt` - Timestamps

**Status Values:**
- `pending` - Awaiting review
- `completed` - Approved/completed
- `failed` - Failed/rejected

### 4. Payment
Represents payment transactions and details.

**Fields:**
- `id` - Primary key
- `submissionId` - Related submission ID
- `paymentMethod` - Payment method (stripe, bkash, bank_transfer)
- `paymentId` - External payment ID
- `amount` - Payment amount
- `currency` - Payment currency
- `status` - Payment status
- `metadata` - Additional payment data (JSON)
- `receiptUrl` - Receipt URL
- `refundedAmount` - Refunded amount
- `failureReason` - Failure reason (if failed)
- `processedAt`, `refundedAt` - Processing timestamps
- `createdAt`, `updatedAt` - Timestamps

**Payment Methods:**
- `stripe` - Stripe payments
- `bkash` - bKash mobile payments
- `bank_transfer` - Bank transfers
- `card` - Direct card payments

**Status Values:**
- `pending` - Payment pending
- `processing` - Being processed
- `completed` - Successfully completed
- `failed` - Payment failed
- `cancelled` - Payment cancelled
- `refunded` - Fully refunded
- `partially_refunded` - Partially refunded

### 5. Settings
System configuration settings.

**Fields:**
- `id` - Primary key
- `key` - Setting key
- `value` - Setting value
- `type` - Value type (string, boolean, integer, json)
- `description` - Setting description
- `category` - Setting category
- `isPublic` - Whether publicly accessible
- `createdAt`, `updatedAt` - Timestamps

**Categories:**
- `general` - General settings
- `payment` - Payment settings
- `email` - Email configuration
- `security` - Security settings
- `ui` - User interface settings

## 🔐 Authentication

The API uses JWT (JSON Web Tokens) for authentication. Include the token in the Authorization header:

```
Authorization: Bearer <your-jwt-token>
```

### User Roles and Permissions

The system supports role-based access control with the following roles:

- **User**: Basic form submission access
- **Admin**: Full administrative access
- **Super Admin**: Complete system access
- **Form Manager**: Form creation and management
- **Payment Approver**: Payment approval permissions
- **Submission Viewer**: View submissions only
- **Submission Editor**: Edit submissions

## 💳 Payment Integration

### Supported Payment Methods

1. **Stripe**: Credit/debit card payments
2. **bKash**: Bangladesh mobile wallet
3. **Bank Transfer**: Direct bank transfers

### Payment Flow

1. Create payment intent
2. Process payment with chosen method
3. Handle webhooks/callbacks
4. Update submission status
5. Generate receipt

## 📤 Export Features

### CSV Export
- Export form submissions as CSV
- Filter by date range, status, form
- Include/exclude payment information
- Downloadable files

### PDF Export
- Generate PDF reports
- Multiple template options
- Professional formatting
- Print-ready documents

## 🧪 Testing

### Manual Testing
Use the provided test scripts:

```bash
# Linux/Mac
./test-api.sh

# Windows
test-api.bat
```

### API Testing Tools
- **Swagger UI**: Interactive API testing at `/api/docs`
- **Postman**: Import OpenAPI spec from `/api/docs/json`
- **curl**: Command-line testing examples in test scripts

### Test Endpoints
- Health checks: `/api/health`, `/api/test-cors`
- Debug info: `/api/debug-swagger`
- Rate limiting: `/api/rate-limit-status`

## 🛠️ Development

### Adding New Endpoints

1. **Create Controller Method**:
   ```php
   /**
    * @OA\Get(
    *     path="/api/your-endpoint",
    *     tags={"YourTag"},
    *     summary="Your endpoint description"
    * )
    */
   public function yourMethod($request) {
       // Implementation
   }
   ```

2. **Add Route**:
   ```php
   // In routes/api.php
   $router->get('/your-endpoint', [YourController::class, 'yourMethod']);
   ```

3. **Update Documentation**:
   - Add OpenAPI annotations
   - Update Swagger schemas if needed

### Database Migrations

The project uses Eloquent models. Create migrations for new tables:

```php
// Example migration structure
Schema::create('your_table', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

## 📚 Dependencies

### Core Dependencies
- **illuminate/database**: Eloquent ORM
- **firebase/php-jwt**: JWT token handling
- **guzzlehttp/guzzle**: HTTP client
- **vlucas/phpdotenv**: Environment configuration

### Development Dependencies
- **phpunit/phpunit**: Testing framework
- **psr/log**: Logging interface

## 🚨 Error Handling

The API returns consistent error responses:

```json
{
    "success": false,
    "message": "Error description",
    "code": "ERROR_CODE",
    "data": null
}
```

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## 📈 Monitoring

### Health Checks
- `/api/health` - Basic health check
- `/api/test-cors` - CORS functionality
- `/api/rate-limit-status` - Rate limiting status

### Logging
The application logs errors and important events. Configure logging in your environment.

## 🔒 Security

### Best Practices
- Input validation on all endpoints
- SQL injection protection via Eloquent ORM
- XSS protection in responses
- CSRF protection for state-changing operations
- Rate limiting for API endpoints
- File upload security checks

### Environment Variables
Store sensitive data in environment variables:
- Database credentials
- JWT secrets
- Payment API keys
- Email configuration

## 📞 Support

For issues and questions:
1. Check the API documentation at `/api/docs`
2. Test endpoints with provided scripts
3. Review error logs
4. Check database connections and migrations

## 🚀 Deployment

### Production Checklist
- [ ] Set up proper web server (Apache/Nginx)
- [ ] Configure SSL/HTTPS
- [ ] Set up database with proper credentials
- [ ] Configure environment variables
- [ ] Set up file upload directories with proper permissions
- [ ] Configure email settings
- [ ] Set up monitoring and logging
- [ ] Test all API endpoints
- [ ] Verify payment integrations
- [ ] Set up backup procedures

### Performance Optimization
- Enable PHP OPcache
- Configure database indexing
- Implement API rate limiting
- Use CDN for static files
- Enable GZIP compression
- Monitor API performance
