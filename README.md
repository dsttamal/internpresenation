# PHP Form Builder Backend

A modern PHP backend for the Form Builder application, built with clean architecture principles and modern PHP features.

## Features

- **RESTful API** - Clean, documented API endpoints
- **JWT Authentication** - Secure token-based authentication
- **Role-based Access Control** - Fine-grained permissions system
- **Form Management** - Dynamic form creation and management
- **Payment Integration** - Stripe and bKash payment support
- **File Uploads** - Secure file upload handling
- **Export Functionality** - CSV and PDF export capabilities
- **Rate Limiting** - API abuse protection
- **CORS Support** - Cross-origin resource sharing
- **Security Headers** - Protection against common attacks
- **Database Abstraction** - Eloquent ORM integration
- **📚 Swagger Documentation** - Interactive API documentation with testing capabilities

## Requirements

- PHP >= 8.1
- MySQL >= 5.7 or MariaDB >= 10.3
- Composer
- Extensions: PDO, OpenSSL, Mbstring, JSON, Curl

## Quick Start

1. **Setup (first time only):**
   ```bash
   cd phpbackend
   composer install
   cp .env.example .env
   # Edit .env with your database credentials
   ```

2. **Run the server:**
   ```bash
   # Option 1: Using start script (recommended)
   ./start.sh          # Linux/Mac
   start.bat           # Windows
   
   # Option 2: Direct command
   php -S localhost:5000 -t public
   
   # Option 3: Using Composer
   composer run serve
   ```

3. **Test the API:**
   ```bash
   curl http://localhost:5000/api/health
   ```

4. **View API Documentation:**
   ```bash
   # Open in browser
   http://localhost:5000/api/docs
   ```

## 📚 API Documentation

The backend includes comprehensive Swagger/OpenAPI documentation:

- **🌐 Interactive Docs**: `http://localhost:5000/api/docs`
- **📄 JSON Spec**: `http://localhost:5000/api/docs/json`
- **📝 YAML Spec**: `http://localhost:5000/api/docs/yaml`

### Features:
- ✅ Interactive testing interface
- ✅ Complete endpoint documentation
- ✅ Request/response examples
- ✅ Authentication support
- ✅ Schema definitions
- ✅ Try-it-out functionality

## Detailed Instructions

- **📋 Setup Guide**: See [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)
- **🏃‍♂️ Running Instructions**: See [RUN_INSTRUCTIONS.md](RUN_INSTRUCTIONS.md)  
- **🧪 API Testing**: See [API_TESTING.md](API_TESTING.md)
- **📚 Swagger Guide**: See [SWAGGER_GUIDE.md](SWAGGER_GUIDE.md)
- **⚡ Quick Reference**: See [QUICK_REFERENCE.md](QUICK_REFERENCE.md)

## Configuration

### Environment Variables

Key environment variables to configure:

```env
# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:5000

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=form_builder
DB_USERNAME=root
DB_PASSWORD=

# JWT
JWT_SECRET=your-super-secret-jwt-key
JWT_EXPIRATION=7d

# CORS
FRONTEND_URL=http://localhost:3000
ALLOWED_ORIGINS=http://localhost:3000,https://forms.bsmmupathalumni.org

# Payments
STRIPE_SECRET_KEY=sk_test_...
BKASH_APP_KEY=your_bkash_key
```

## Running the Server

### Development Server
```bash
composer start
# or
php -S localhost:5000 public/index.php
```

### Production Deployment
- Configure your web server (Apache/Nginx) to point to the `public/` directory
- Ensure proper SSL certificate setup
- Set `APP_ENV=production` and `APP_DEBUG=false`

## API Documentation

### Authentication Endpoints

```
POST /api/auth/register      - Register new user
POST /api/auth/login         - User login
GET  /api/auth/profile       - Get user profile
POST /api/auth/refresh       - Refresh JWT token
POST /api/auth/change-password - Change password
POST /api/auth/logout        - Logout
```

### Form Management

```
GET    /api/forms            - List forms
POST   /api/forms            - Create form
GET    /api/forms/{id}       - Get form details
PUT    /api/forms/{id}       - Update form
DELETE /api/forms/{id}       - Delete form
GET    /api/forms/public/{customUrl} - Get public form
```

### Submissions

```
GET  /api/submissions        - List submissions
POST /api/submissions        - Create submission
GET  /api/submissions/{id}   - Get submission
PUT  /api/submissions/{id}   - Update submission
```

### Payment Integration

```
POST /api/payment/stripe/create-intent - Create Stripe payment
POST /api/bkash/create      - Create bKash payment
POST /api/payment/bank-transfer - Bank transfer payment
```

## Architecture

### Directory Structure

```
phpbackend/
├── public/           # Web server document root
├── src/
│   ├── Core/         # Core application classes
│   ├── Controllers/  # HTTP controllers
│   ├── Models/       # Database models
│   ├── Services/     # Business logic services
│   └── Middleware/   # HTTP middleware
├── config/           # Configuration files
├── routes/           # Route definitions
├── storage/          # Application storage
├── uploads/          # File uploads
└── logs/            # Application logs
```

### Core Components

- **Application**: Main application container
- **Router**: HTTP routing with parameter matching
- **Request/Response**: HTTP request/response handling
- **Middleware**: Chainable request processors
- **Services**: Business logic layer
- **Models**: Database entity representations

## Security Features

- **JWT Authentication** with configurable expiration
- **Rate Limiting** to prevent API abuse
- **CORS Protection** with configurable origins
- **Security Headers** (CSP, HSTS, XSS protection)
- **Input Validation** using Respect/Validation
- **File Upload Security** with type and size restrictions
- **SQL Injection Protection** via Eloquent ORM

## Payment Integration

### Stripe
- Payment intent creation
- Webhook handling for payment confirmation
- Refund processing

### bKash
- Payment creation and execution
- Query payment status
- Refund support

### Bank Transfer
- Manual payment upload
- Admin approval workflow
- Receipt management

## File Upload Handling

- Configurable file size limits
- MIME type validation
- Secure file storage
- Protected file access

## Export Features

- CSV export with custom formatting
- PDF generation using DomPDF
- Date range filtering
- Asynchronous processing for large datasets

## Error Handling

- Centralized exception handling
- Detailed error logging
- Environment-aware error responses
- HTTP status code mapping

## Performance Considerations

- Database query optimization
- Efficient pagination
- File-based rate limiting (consider Redis for production)
- Lazy loading of relationships

## Testing

```bash
composer test
# or
vendor/bin/phpunit
```

## Contributing

1. Follow PSR-12 coding standards
2. Write unit tests for new features
3. Update documentation
4. Use meaningful commit messages

## License

This project is proprietary software for BSMMU Alumni Form Builder.

## Support

For technical support, please contact the development team.
