# 🚀 Getting Started with PHP Backend

## TL;DR - Just Want to Run It?

```bash
cd phpbackend
./start.sh    # Linux/Mac
# OR
start.bat     # Windows
```

The script will handle everything automatically!

## Manual Setup (if needed)

### 1. Install Dependencies
```bash
cd phpbackend
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env
# Edit .env file with your database credentials
```

### 3. Start Server
```bash
php -S localhost:5000 -t public
```

### 4. Test API
```bash
curl http://localhost:5000/api/health
```

## What You Get

- **REST API** running on `http://localhost:5000`
- **Health endpoint** at `/api/health`
- **Authentication** endpoints (`/api/auth/*`)
- **Form management** endpoints (`/api/forms/*`)
- **File uploads** support
- **Payment integration** (Stripe/bKash)
- **Export functionality** (CSV/PDF)

## Next Steps

1. **Test the API** - Use the examples in `API_TESTING.md`
2. **Connect frontend** - Point your React app to `http://localhost:5000/api`
3. **Import data** - Use the database tools to set up test data
4. **Configure payments** - Add Stripe/bKash credentials to `.env`

## Files Overview

| File | Purpose |
|------|---------|
| `start.sh/.bat` | Quick start scripts |
| `RUN_INSTRUCTIONS.md` | Detailed running guide |
| `API_TESTING.md` | API testing examples |
| `QUICK_REFERENCE.md` | Command reference |
| `DATABASE_MODEL.md` | Database documentation |
| `composer.json` | Dependencies and scripts |
| `.env.example` | Environment template |

## Need Help?

- **Setup issues**: Check `INSTALLATION_GUIDE.md`
- **API questions**: Check `API_TESTING.md`
- **Database problems**: Run `php inspect_db.php`
- **Quick commands**: Check `QUICK_REFERENCE.md`

---

**Happy coding! 🎉**
