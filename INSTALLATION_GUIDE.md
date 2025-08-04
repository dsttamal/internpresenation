# 🚀 PHP Backend Installation & Setup Guide

## Quick Start Commands

```bash
# Windows
cd phpbackend
install.bat

# Linux/Mac
cd phpbackend
chmod +x install.sh
./install.sh
```

## Detailed Installation Instructions

### 📋 Prerequisites

Before installing the PHP backend, ensure you have the following:

#### Required Software
- **PHP 8.1 or higher** with extensions:
  - PDO (for database)
  - OpenSSL (for JWT tokens)
  - Mbstring (for string handling)
  - JSON (for API responses)
  - Curl (for HTTP requests)
  - BCMath (for precise calculations)
- **Composer** (PHP dependency manager)
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Web Server** (Apache/Nginx) for production

#### Check PHP Version
```bash
php --version
# Should show PHP 8.1.0 or higher

php -m | grep -E "(pdo|openssl|mbstring|json|curl|bcmath)"
# Should show all required extensions
```

#### Install Composer
```bash
# Download and install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Verify installation
composer --version
```

### 🛠️ Step-by-Step Installation

#### Step 1: Clone/Download the Backend
```bash
# If using git
git clone <repository-url>
cd phpbackend

# Or if you have the files already
cd path/to/phpbackend
```

#### Step 2: Install Dependencies
```bash
# Install PHP packages
composer install

# For production (excludes dev dependencies)
composer install --no-dev --optimize-autoloader
```

#### Step 3: Environment Configuration
```bash
# Copy environment template
cp .env.example .env

# Edit configuration file
nano .env
# or
notepad .env  # Windows
```

#### Step 4: Configure Environment Variables
Edit the `.env` file with your settings:

```env
# Application Settings
APP_NAME="Form Builder API"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:5000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=form_builder
DB_USERNAME=root
DB_PASSWORD=your_password

# JWT Security
JWT_SECRET=your-super-secret-jwt-key-change-this-in-production
JWT_EXPIRATION=7d

# CORS Configuration
FRONTEND_URL=http://localhost:3000
ALLOWED_ORIGINS=http://localhost:3000,https://forms.bsmmupathalumni.org

# File Upload Settings
UPLOAD_PATH=uploads
MAX_FILE_SIZE=10485760
ALLOWED_FILE_TYPES=jpg,jpeg,png,pdf,doc,docx

# Payment Configuration (Optional)
STRIPE_SECRET_KEY=sk_test_your_stripe_secret_key
STRIPE_PUBLISHABLE_KEY=pk_test_your_stripe_publishable_key

# bKash Configuration (Optional)
BKASH_APP_KEY=your_bkash_app_key
BKASH_APP_SECRET=your_bkash_app_secret
BKASH_USERNAME=your_bkash_username
BKASH_PASSWORD=your_bkash_password

# Email Configuration (Optional)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=noreply@bsmmupathalumni.org

# Rate Limiting
RATE_LIMIT_REQUESTS=1000
RATE_LIMIT_WINDOW=900
```

#### Step 5: Database Setup
```bash
# Create MySQL database
mysql -u root -p
```

```sql
-- In MySQL console
CREATE DATABASE form_builder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'form_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON form_builder.* TO 'form_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import database schema
mysql -u form_user -p form_builder < database/schema.sql
```

#### Step 6: Set Permissions
```bash
# Linux/Mac
chmod 755 storage uploads logs
chmod -R 755 storage/rate_limits

# Windows (run as administrator)
icacls storage /grant Everyone:F /T
icacls uploads /grant Everyone:F /T
icacls logs /grant Everyone:F /T
```

#### Step 7: Test Installation
```bash
# Test database connection
composer inspect-db

# Test PHP syntax
php -l public/index.php
```

### 🏃‍♂️ Running the Backend

#### Development Server
```bash
# Start built-in PHP server
composer start

# Or manually
php -S localhost:5000 public/index.php
```

Access the API at: `http://localhost:5000`

#### Test API Endpoints
```bash
# Health check
curl http://localhost:5000/api/health

# CORS test
curl http://localhost:5000/api/test-cors

# Rate limit status
curl http://localhost:5000/api/rate-limit-status
```

### 🌐 Production Deployment

#### Apache Configuration
Create virtual host file `/etc/apache2/sites-available/form-builder.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/phpbackend/public
    
    <Directory /path/to/phpbackend/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/form-builder-error.log
    CustomLog ${APACHE_LOG_DIR}/form-builder-access.log combined
</VirtualHost>
```

```bash
# Enable site and rewrite module
sudo a2enmod rewrite
sudo a2ensite form-builder
sudo systemctl reload apache2
```

#### Nginx Configuration
Create configuration file `/etc/nginx/sites-available/form-builder`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/phpbackend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/form-builder /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Production Environment
```bash
# Set production environment
sed -i 's/APP_ENV=development/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env

# Install production dependencies
composer install --no-dev --optimize-autoloader

# Set secure permissions
chmod 644 .env
chmod -R 755 storage uploads logs
chown -R www-data:www-data storage uploads logs
```

### 🔧 Configuration Options

#### Security Settings
```env
# Strong JWT secret (32+ characters)
JWT_SECRET=your-very-long-random-secret-key-here

# HTTPS in production
APP_URL=https://yourdomain.com

# Restrict CORS origins
ALLOWED_ORIGINS=https://yourdomain.com,https://forms.yourdomain.com
```

#### Performance Tuning
```env
# Increase for high traffic
RATE_LIMIT_REQUESTS=5000
RATE_LIMIT_WINDOW=900

# File upload limits
MAX_FILE_SIZE=52428800  # 50MB
```

#### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_forms_active ON forms(isActive, createdAt);
CREATE INDEX idx_submissions_form_date ON submissions(formId, createdAt);
```

### 🧪 Testing & Verification

#### API Testing
```bash
# Test user registration
curl -X POST http://localhost:5000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","email":"test@example.com","password":"password123"}'

# Test form creation (requires auth token)
curl -X POST http://localhost:5000/api/forms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{"title":"Test Form","fields":[{"type":"text","label":"Name","required":true}]}'
```

#### Database Inspection
```bash
# Run database inspector
composer inspect-db

# Check specific table
mysql -u form_user -p form_builder -e "SELECT COUNT(*) FROM users;"
```

### 🐛 Troubleshooting

#### Common Issues

**1. Database Connection Failed**
```bash
# Check MySQL service
sudo systemctl status mysql

# Test connection
mysql -u form_user -p form_builder -e "SELECT 1;"

# Check credentials in .env file
```

**2. Permission Denied Errors**
```bash
# Fix file permissions
sudo chown -R www-data:www-data /path/to/phpbackend
chmod -R 755 storage uploads logs
```

**3. PHP Extensions Missing**
```bash
# Install missing extensions (Ubuntu/Debian)
sudo apt-get install php8.1-mysql php8.1-mbstring php8.1-curl php8.1-bcmath

# Check loaded extensions
php -m | grep extension_name
```

**4. Composer Issues**
```bash
# Clear composer cache
composer clear-cache

# Update dependencies
composer update

# Check for conflicts
composer diagnose
```

**5. JWT Token Issues**
```bash
# Generate new secret
openssl rand -base64 32

# Update .env file with new secret
```

#### Debug Mode
```env
# Enable detailed error reporting
APP_DEBUG=true
APP_ENV=development
```

Check logs:
```bash
# PHP error log
tail -f /var/log/php_errors.log

# Application logs
tail -f logs/app.log
```

### 📊 Monitoring & Maintenance

#### Performance Monitoring
```bash
# Check database stats
composer inspect-db

# Monitor file sizes
du -sh storage uploads logs

# Check memory usage
ps aux | grep php
```

#### Regular Maintenance
```bash
# Clean rate limit files (weekly)
find storage/rate_limits -type f -mtime +7 -delete

# Backup database (daily)
mysqldump -u form_user -p form_builder > backup_$(date +%Y%m%d).sql

# Update dependencies (monthly)
composer update
```

### 🔒 Security Checklist

- [ ] Strong JWT secret (32+ characters)
- [ ] HTTPS enabled in production
- [ ] Database user with limited privileges
- [ ] File upload restrictions configured
- [ ] Rate limiting enabled
- [ ] CORS origins restricted
- [ ] Error reporting disabled in production
- [ ] File permissions properly set
- [ ] Regular security updates

### 📞 Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review log files for error messages
3. Verify all prerequisites are installed
4. Ensure environment variables are correctly set
5. Test database connectivity
6. Check file permissions

For additional help, refer to:
- `README.md` - General information
- `DATABASE_MODEL.md` - Database documentation
- `MODEL_ARCHITECTURE.md` - Code structure
- `SWAGGER_GUIDE.md` - API documentation guide
- `inspect_db.php` - Database inspection tool

## 📚 API Documentation

After installation, you can access comprehensive API documentation:

- **Interactive Swagger UI**: `http://localhost:5000/api/docs`
- **OpenAPI JSON**: `http://localhost:5000/api/docs/json`
- **OpenAPI YAML**: `http://localhost:5000/api/docs/yaml`

The documentation includes:
✅ All API endpoints with examples  
✅ Interactive testing interface  
✅ Authentication workflows  
✅ Request/response schemas  
✅ Error code documentation

---

🎉 **Congratulations!** Your Form Builder PHP backend should now be running successfully!
