# 📋 API Testing Guide

## Quick API Tests

### Health Check
```bash
curl http://localhost:5000/api/health
```

### Authentication

#### Register a User
```bash
curl -X POST http://localhost:5000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "role": "user"
  }'
```

#### Login
```bash
curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

#### Get Current User (requires token)
```bash
curl -X GET http://localhost:5000/api/auth/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Forms Management

#### Get All Forms
```bash
curl -X GET http://localhost:5000/api/forms \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Create a Form
```bash
curl -X POST http://localhost:5000/api/forms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "title": "Contact Form",
    "description": "A simple contact form",
    "fields": [
      {
        "type": "text",
        "name": "name",
        "label": "Full Name",
        "required": true,
        "placeholder": "Enter your full name"
      },
      {
        "type": "email",
        "name": "email",
        "label": "Email Address",
        "required": true,
        "placeholder": "Enter your email"
      },
      {
        "type": "textarea",
        "name": "message",
        "label": "Message",
        "required": true,
        "placeholder": "Enter your message"
      }
    ],
    "settings": {
      "requirePayment": false,
      "allowMultipleSubmissions": true,
      "collectEmail": true
    }
  }'
```

#### Get Specific Form
```bash
curl -X GET http://localhost:5000/api/forms/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Update a Form
```bash
curl -X PUT http://localhost:5000/api/forms/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "title": "Updated Contact Form",
    "description": "An updated simple contact form"
  }'
```

#### Delete a Form
```bash
curl -X DELETE http://localhost:5000/api/forms/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Form Submissions

#### Submit to a Form
```bash
curl -X POST http://localhost:5000/api/forms/1/submit \
  -H "Content-Type: application/json" \
  -d '{
    "data": {
      "name": "Jane Smith",
      "email": "jane@example.com",
      "message": "Hello, this is a test message!"
    },
    "paymentInfo": {
      "method": "none"
    }
  }'
```

#### Get Form Submissions (admin only)
```bash
curl -X GET http://localhost:5000/api/forms/1/submissions \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN"
```

#### Get All Submissions (admin only)
```bash
curl -X GET http://localhost:5000/api/submissions \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN"
```

#### Get Specific Submission
```bash
curl -X GET http://localhost:5000/api/submissions/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Update Submission Status (admin only)
```bash
curl -X PUT http://localhost:5000/api/submissions/1/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN" \
  -d '{
    "status": "approved"
  }'
```

### File Uploads

#### Upload File
```bash
curl -X POST http://localhost:5000/api/upload \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "file=@/path/to/your/file.pdf" \
  -F "type=document"
```

### Payment Endpoints

#### Create Payment Intent (Stripe)
```bash
curl -X POST http://localhost:5000/api/payment/create-intent \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "amount": 5000,
    "currency": "usd",
    "formId": 1,
    "submissionId": 1
  }'
```

#### Process bKash Payment
```bash
curl -X POST http://localhost:5000/api/payment/bkash/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "amount": 100,
    "formId": 1,
    "submissionId": 1,
    "reference": "ORDER123"
  }'
```

### Export Data

#### Export Submissions as CSV
```bash
curl -X GET "http://localhost:5000/api/export/csv?formId=1&startDate=2024-01-01&endDate=2024-12-31" \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN" \
  --output submissions.csv
```

#### Export Submissions as PDF
```bash
curl -X GET "http://localhost:5000/api/export/pdf?formId=1&startDate=2024-01-01&endDate=2024-12-31" \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN" \
  --output submissions.pdf
```

### User Management (Admin Only)

#### Get All Users
```bash
curl -X GET http://localhost:5000/api/admin/users \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN"
```

#### Update User Role
```bash
curl -X PUT http://localhost:5000/api/admin/users/1/role \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN" \
  -d '{
    "role": "admin"
  }'
```

#### Delete User
```bash
curl -X DELETE http://localhost:5000/api/admin/users/1 \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN"
```

### Settings Management

#### Get Settings
```bash
curl -X GET http://localhost:5000/api/settings \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN"
```

#### Update Settings
```bash
curl -X PUT http://localhost:5000/api/settings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN" \
  -d '{
    "siteName": "My Form Builder",
    "adminEmail": "admin@example.com",
    "allowRegistration": true,
    "requireEmailVerification": false
  }'
```

## Response Examples

### Successful Login Response
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "user",
      "createdAt": "2024-01-20T10:30:00Z"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

### Form Creation Response
```json
{
  "success": true,
  "message": "Form created successfully",
  "data": {
    "id": 1,
    "title": "Contact Form",
    "description": "A simple contact form",
    "fields": [...],
    "settings": {...},
    "createdAt": "2024-01-20T10:30:00Z"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required"],
    "password": ["The password must be at least 6 characters"]
  }
}
```

## Testing with Postman

Import this collection into Postman:

1. Create a new collection
2. Add the base URL as a variable: `{{baseUrl}}` = `http://localhost:5000`
3. Add authorization header: `Bearer {{token}}`
4. Create requests for each endpoint above

## Debugging Tips

### Check Logs
```bash
# Application logs
tail -f logs/app.log

# Error logs
tail -f logs/error.log
```

### Common Issues

1. **401 Unauthorized**: Check if JWT token is valid and included in Authorization header
2. **403 Forbidden**: User doesn't have required permissions for the endpoint
3. **404 Not Found**: Check if the endpoint URL is correct
4. **500 Internal Server Error**: Check application logs for PHP errors

### Enable Debug Mode
In `.env`:
```env
APP_DEBUG=true
APP_LOG_LEVEL=debug
```
