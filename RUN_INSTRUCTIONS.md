# 🏃‍♂️ PHP Backend - Running Instructions

## Quick Start (TL;DR)

```bash
# 1. Setup (first time only)
cd phpbackend
composer install
cp .env.example .env
# Edit .env file with your database credentials

# 2. Run the server
php -S localhost:5000 -t public

# 3. Test the API
curl http://localhost:5000/api/health
```

## Detailed Running Instructions

### 🚀 Starting the Development Server

The PHP backend uses PHP's built-in development server for local development:

#### Option 1: Direct Command
```bash
cd phpbackend
php -S localhost:5000 -t public
```

#### Option 2: Using Composer Script
```bash
cd phpbackend
composer run serve
```

#### Option 3: Custom Port
```bash
cd phpbackend
php -S localhost:8080 -t public
# or
composer run serve:alt
```

### 🌐 Server Information

- **Default URL**: `http://localhost:5000`
- **API Base**: `http://localhost:5000/api`
- **Document Root**: `public/` directory
- **Entry Point**: `public/index.php`

### 📊 Health Check

Once the server is running, verify it's working:

```bash
# Basic health check
curl http://localhost:5000/api/health

# Expected response:
{
    "status": "ok",
    "message": "Form Builder API is running",
    "timestamp": "2024-01-20T10:30:00Z",
    "version": "1.0.0"
}
```

### 🔧 Environment Setup

#### 1. First Time Setup
```bash
# Navigate to backend directory
cd phpbackend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Edit environment variables
nano .env  # Linux/Mac
notepad .env  # Windows
```

#### 2. Configure Database
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=form_builder
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### 3. Create Database
```sql
-- Connect to MySQL and create database
CREATE DATABASE form_builder;
USE form_builder;

-- Import schema (from database/schema.sql)
SOURCE database/schema.sql;
```

### 🗄️ Database Setup

#### Method 1: Import SQL Schema
```bash
cd phpbackend
mysql -u root -p form_builder < database/schema.sql
```

#### Method 2: Using Database Tool
```bash
cd phpbackend
php inspect_db.php --setup
```

### 🧪 Testing the API

#### Test Authentication Endpoints
```bash
# Register a new user
curl -X POST http://localhost:5000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "role": "user"
  }'

# Login
curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

#### Test Form Endpoints
```bash
# Get all forms (requires authentication)
curl -X GET http://localhost:5000/api/forms \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Create a form
curl -X POST http://localhost:5000/api/forms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "title": "Test Form",
    "description": "A test form",
    "fields": [
      {
        "type": "text",
        "label": "Name",
        "name": "name",
        "required": true
      }
    ]
  }'
```

### 🔍 Debugging & Logs

#### View Logs
```bash
# Application logs
tail -f logs/app.log

# Error logs
tail -f logs/error.log

# Access logs
tail -f logs/access.log
```

#### Debug Mode
Set in `.env`:
```env
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

#### Database Connection Test
```bash
cd phpbackend
php inspect_db.php --test-connection
```

### 🛡️ Security Considerations

#### File Permissions (Linux/Mac)
```bash
chmod 755 storage/
chmod 755 uploads/
chmod 755 logs/
chmod 600 .env
```

#### Production Settings
For production, update `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_LOG_LEVEL=error
```

### 🐛 Troubleshooting

#### Common Issues

1. **Port Already in Use**
   ```bash
   # Use different port
   php -S localhost:5001 -t public
   ```

2. **Database Connection Failed**
   ```bash
   # Test database connection
   php inspect_db.php --test-connection
   
   # Check database credentials in .env
   # Ensure MySQL service is running
   ```

3. **Permission Denied**
   ```bash
   # Fix directory permissions
   chmod 755 storage/ uploads/ logs/
   ```

4. **Composer Dependencies**
   ```bash
   # Reinstall dependencies
   rm -rf vendor/
   composer install
   ```

5. **PHP Extensions Missing**
   ```bash
   # Check extensions
   php -m | grep -E "(pdo|openssl|mbstring|json|curl)"
   
   # Install missing extensions (Ubuntu/Debian)
   sudo apt-get install php-pdo php-openssl php-mbstring php-json php-curl
   ```

### 📈 Performance Monitoring

#### Check Server Status
```bash
# Server is running on port 5000
lsof -i :5000

# View active connections
netstat -an | grep :5000
```

#### Monitor Logs in Real-time
```bash
# Application logs
tail -f logs/app.log

# Combined monitoring
tail -f logs/*.log
```

### 🔄 Restarting the Server

#### Stop Server
- Press `Ctrl+C` in the terminal running the server

#### Restart Server
```bash
# Start again
php -S localhost:5000 -t public
```

#### Auto-restart on Changes (Optional)
```bash
# Install nodemon (if you have Node.js)
npm install -g nodemon

# Use nodemon to auto-restart
nodemon --exec "php -S localhost:5000 -t public" --watch . --ext php
```

### 🌍 Production Deployment

#### Using Apache
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/phpbackend/public
    
    <Directory /path/to/phpbackend/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Using Nginx
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
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 🎯 Next Steps

After getting the server running:

1. **Test all API endpoints** using the examples above
2. **Import test data** if available
3. **Set up frontend** to connect to this backend
4. **Configure payments** (Stripe/bKash) if needed
5. **Set up file uploads** for form attachments
6. **Configure email** for notifications

### 📞 Getting Help

If you encounter issues:

1. Check the logs in `logs/` directory
2. Verify database connection with `php inspect_db.php`
3. Ensure all PHP extensions are installed
4. Check file permissions on `storage/`, `uploads/`, and `logs/`
5. Verify `.env` configuration matches your environment

---

**Happy coding! 🚀**
