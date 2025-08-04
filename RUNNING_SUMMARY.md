# 📝 PHP Backend - Complete Running Guide Summary

## 🚀 Super Quick Start

```bash
cd phpbackend
start.bat        # Windows
./start.sh       # Linux/Mac
```

## 📋 What's Included

I've created comprehensive running instructions with the following files:

### 📖 Documentation Files Created:

1. **`GETTING_STARTED.md`** - Quick overview and first steps
2. **`RUN_INSTRUCTIONS.md`** - Detailed running instructions with troubleshooting
3. **`API_TESTING.md`** - Complete API testing examples and commands
4. **`QUICK_REFERENCE.md`** - Fast command reference for daily use

### 🛠️ Scripts Created:

1. **`start.sh`** - Linux/Mac startup script with automatic setup
2. **`start.bat`** - Windows startup script with automatic setup

### ⚙️ Enhanced Files:

1. **`composer.json`** - Added more scripts (serve, dev, start, test-api)
2. **`README.md`** - Updated with quick start and documentation links

## 🎯 Key Running Methods

### Method 1: Start Scripts (Recommended)
```bash
# Windows
start.bat

# Linux/Mac  
./start.sh
```
**Features:**
- Automatic dependency checking
- Environment file creation
- Database connection testing
- Port availability checking
- Helpful error messages and fixes

### Method 2: Composer Scripts
```bash
composer run serve      # Start server on port 5000
composer run serve:alt  # Start server on port 8080
composer run dev        # Development server
composer run start      # Standard start
composer run test-api   # Quick API health test
```

### Method 3: Direct PHP Command
```bash
php -S localhost:5000 -t public
```

## 🏥 Health Check & Testing

Once running, test with:
```bash
curl http://localhost:5000/api/health
```

Expected response:
```json
{
    "status": "ok",
    "message": "Form Builder API is running",
    "timestamp": "2024-01-20T10:30:00Z",
    "version": "1.0.0"
}
```

## 🔧 Setup Requirements

### Prerequisites:
- PHP 8.1+ with extensions (PDO, OpenSSL, Mbstring, JSON, Curl)
- Composer
- MySQL/MariaDB

### Quick Setup:
1. `composer install`
2. `cp .env.example .env`
3. Edit `.env` with database credentials
4. Create database: `CREATE DATABASE form_builder;`
5. Import schema: `mysql -u root -p form_builder < database/schema.sql`

## 🐛 Troubleshooting Tools

### Database Issues:
```bash
php inspect_db.php --test-connection
php inspect_db.php --stats
```

### View Logs:
```bash
tail -f logs/app.log
tail -f logs/error.log
```

### Port Issues:
The start scripts automatically find available ports starting from 5000.

## 📊 API Endpoints Available

- **Health**: `GET /api/health`
- **Auth**: `POST /api/auth/register`, `POST /api/auth/login`
- **Forms**: `GET|POST|PUT|DELETE /api/forms/*`
- **Submissions**: `GET|POST /api/forms/{id}/submit`
- **Uploads**: `POST /api/upload`
- **Payments**: `POST /api/payment/*`
- **Export**: `GET /api/export/csv`, `GET /api/export/pdf`
- **Admin**: `GET|POST|PUT|DELETE /api/admin/*`

## 🎉 Success Indicators

When everything is working correctly, you should see:
1. ✅ Server starts without errors
2. ✅ Health check returns JSON response
3. ✅ Database connection successful
4. ✅ All required PHP extensions loaded
5. ✅ Directories created with proper permissions

## 📚 Next Steps

1. **Start the server** using any method above
2. **Test the API** with examples from `API_TESTING.md`
3. **Connect frontend** to `http://localhost:5000/api`
4. **Import test data** using the database tools
5. **Configure payments** by adding Stripe/bKash keys to `.env`

## 🆘 Getting Help

If you encounter any issues:

1. **Check logs** in `logs/` directory
2. **Run database test**: `php inspect_db.php --test-connection`
3. **Verify environment**: Check `.env` file configuration
4. **Review documentation**: See the specific guide files listed above
5. **Check requirements**: Ensure PHP version and extensions are correct

---

The PHP backend is now fully documented with multiple ways to start and test it. The start scripts handle most common setup issues automatically, making it very easy to get up and running quickly! 🚀
