# Admin Account Creation Scripts

This directory contains independent PHP scripts to create admin accounts for the BSMMU Alumni Form Builder system.

## Available Scripts

### 1. `create-admin.php` - Interactive Admin Creator
Full-featured script that works both in command line and web browser.

**Features:**
- Interactive prompts for all user details
- Input validation
- Support for all user roles
- Web interface with HTML form
- Comprehensive error handling

**Usage:**
```bash
# Command line
php create-admin.php

# Or access via web browser
http://your-domain.com/create-admin.php
```

### 2. `quick-admin.php` - Quick Admin Creator
Simple script with predefined credentials for quick setup.

**Features:**
- Fast account creation
- Predefined credentials (edit the file to change)
- Command line only
- Minimal interaction required

**Usage:**
```bash
# Edit the credentials in the file first
php quick-admin.php
```

**Default Credentials (change these in the file):**
- Username: `admin2`
- Email: `admin2@bsmmupathalumni.org`
- Password: `SecurePass123!`
- Role: `super_admin`

### 3. `create-admin.bat` - Windows Batch Helper
Windows batch file that provides a menu to choose between the scripts.

**Usage:**
```cmd
create-admin.bat
```

## Available User Roles

| Role | Description |
|------|-------------|
| `user` | Regular user with basic permissions |
| `admin` | General administrative access |
| `super_admin` | Full system access and control |
| `form_manager` | Can create and manage forms |
| `payment_approver` | Can approve payment submissions |
| `submission_viewer` | Can view form submissions |
| `submission_editor` | Can edit form submissions |
| `notification_manager` | Can manage system notifications |

## Database Configuration

The scripts automatically read database configuration from:
1. Environment variables (`$_ENV`)
2. `.env` file (if present)
3. Default values

**Default Configuration:**
```php
$config = [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'form_builder',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];
```

## Environment Variables

Set these environment variables or create a `.env` file:

```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=form_builder
DB_USERNAME=root
DB_PASSWORD=your_password
```

## Security Considerations

⚠️ **Important Security Notes:**

1. **Remove from Production**: Delete these scripts from production servers after use
2. **Secure Access**: Only run in secure environments
3. **Strong Passwords**: Use strong passwords for admin accounts
4. **Change Default Passwords**: Always change default passwords after first login
5. **Limited Access**: Restrict file permissions if keeping scripts

## Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB database
- PDO MySQL extension enabled
- Database connection configured

## Troubleshooting

### Common Issues

**Database Connection Failed:**
- Check database credentials
- Ensure database server is running
- Verify database exists
- Check network connectivity

**User Already Exists:**
- Username or email is already in use
- Check existing users in the database
- Use different credentials

**Permission Denied:**
- Check file permissions
- Ensure PHP has write access to logs
- Verify database user permissions

**PHP Not Found (Windows):**
- Install PHP or add to system PATH
- Use full path to PHP executable

### Manual Database Check

To check existing users:
```sql
SELECT id, username, email, role, isActive FROM users;
```

To manually delete a user (if needed):
```sql
DELETE FROM users WHERE username = 'admin2';
```

## Example Usage

### Creating a Super Admin
1. Run `php create-admin.php`
2. Enter username: `superadmin`
3. Enter email: `super@bsmmupathalumni.org`
4. Enter password: `VerySecurePassword123!`
5. Enter role: `super_admin`

### Creating Multiple Admins
For multiple admins, run the script multiple times with different credentials.

## Files Created

After running the scripts, new user records will be created in the `users` table with:
- Hashed passwords (using PHP's `password_hash()`)
- Timestamps for creation and updates
- Active status set to 1
- Specified role and permissions

## Support

If you encounter issues:
1. Check the database connection
2. Verify user permissions
3. Review PHP error logs
4. Ensure all prerequisites are met

For system-specific issues, consult the main project documentation.
