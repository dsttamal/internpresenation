# ⚡ Quick Commands Reference

## Start Server
```bash
# Option 1: Using start script (recommended)
./start.sh          # Linux/Mac
start.bat           # Windows

# Option 2: Direct PHP command
php -S localhost:5000 -t public

# Option 3: Using Composer
composer run serve
composer run dev
composer run start
```

## Test API
```bash
# Health check
curl http://localhost:5000/api/health

# Or using composer
composer run test-api
```

## Setup Commands
```bash
# First time setup
composer install
cp .env.example .env
# Edit .env file with your database credentials

# Create database and import schema
mysql -u root -p -e "CREATE DATABASE form_builder;"
mysql -u root -p form_builder < database/schema.sql
```

## Database Tools
```bash
# Inspect database
php inspect_db.php
# Or
composer run inspect-db

# Test database connection
php inspect_db.php --test-connection
```

## Development
```bash
# Start with auto-restart (requires nodemon)
nodemon --exec "php -S localhost:5000 -t public" --watch . --ext php

# View logs
tail -f logs/app.log
tail -f logs/error.log
```

## Common Issues

### Port in use
```bash
# Use different port
php -S localhost:8080 -t public
# Or
composer run serve:alt
```

### Permission errors (Linux/Mac)
```bash
chmod +x start.sh
chmod 755 storage/ uploads/ logs/
```

### Missing extensions
```bash
# Check extensions
php -m | grep -E "(pdo|openssl|mbstring|json|curl)"

# Install on Ubuntu/Debian
sudo apt-get install php-pdo php-openssl php-mbstring php-json php-curl

# Install on CentOS/RHEL
sudo yum install php-pdo php-openssl php-mbstring php-json php-curl
```

### Database connection
```bash
# Test connection
php inspect_db.php --test-connection

# Common fixes:
# 1. Start MySQL: sudo systemctl start mysql
# 2. Create database: CREATE DATABASE form_builder;
# 3. Update .env credentials
# 4. Import schema: mysql -u root -p form_builder < database/schema.sql
```

## Quick API Tests
```bash
# Register user
curl -X POST http://localhost:5000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@test.com","password":"password123"}'

# Login
curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password123"}'

# Create form (replace TOKEN with actual JWT)
curl -X POST http://localhost:5000/api/forms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"title":"Test Form","description":"Test","fields":[]}'
```

## File Structure
```
phpbackend/
├── start.sh / start.bat    # Quick start scripts
├── composer.json           # Dependencies and scripts
├── .env.example           # Environment template
├── public/index.php       # Entry point
├── src/                   # Application code
├── config/                # Configuration
├── routes/                # API routes
├── database/schema.sql    # Database schema
├── storage/               # File storage
├── uploads/               # Uploaded files
├── logs/                  # Application logs
└── vendor/                # Dependencies (after composer install)
```

---
**Need help? Check RUN_INSTRUCTIONS.md for detailed setup guide**
