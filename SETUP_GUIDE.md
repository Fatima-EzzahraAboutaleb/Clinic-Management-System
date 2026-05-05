# 📖 Clinic Management System - Setup Guide

## Step 1: Prerequisites Installation

### For Windows Users

#### Install XAMPP (Easiest Method)
1. Download XAMPP from: https://www.apachefriends.org/
2. Run the installer and select:
   - Apache
   - MySQL
   - PHP
   - phpMyAdmin
3. Install to default location (C:\xampp)
4. Start Apache and MySQL from XAMPP Control Panel

#### Alternative: Install Individually
- **PHP**: https://www.php.net/downloads
- **MySQL**: https://dev.mysql.com/downloads/mysql/
- **Apache**: https://httpd.apache.org/

### For Mac Users

#### Using Homebrew (Recommended)
```bash
# Install Homebrew first (if not installed)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install PHP
brew install php mysql apache2

# Start services
brew services start mysql
brew services start apache2
```

#### Using MAMP
1. Download MAMP: https://www.mamp.info/
2. Install and follow the setup wizard
3. Start servers from MAMP interface

### For Linux Users

#### Ubuntu/Debian
```bash
# Install LAMP stack
sudo apt-get update
sudo apt-get install apache2 php php-mysql mysql-server

# Start services
sudo systemctl start apache2
sudo systemctl start mysql
```

#### Using Docker (Advanced)
```bash
docker run -d -p 3306:3306 -e MYSQL_ROOT_PASSWORD=root mysql:latest
docker run -d -p 80:80 -v /path/to/clinic:/var/www/html php:apache
```

## Step 2: Create Database

### Method 1: Using phpMyAdmin (Easiest)

1. Open phpMyAdmin in your browser: `http://localhost/phpmyadmin`
2. Log in with:
   - Username: `root`
   - Password: (leave blank or your MySQL password)
3. Click on "SQL" tab
4. Copy and paste the contents of `database/clinic_setup.sql`
5. Click "Go" to execute

### Method 2: Using MySQL Command Line

```bash
# Login to MySQL
mysql -u root -p

# If prompted for password, press Enter (default is no password)

# Paste the contents of database/clinic_setup.sql
# Or run:
mysql -u root < database/clinic_setup.sql
```

### Method 3: Using MySQL Workbench

1. Open MySQL Workbench
2. Create new connection (if needed)
3. Connect to your MySQL server
4. Go to File → Open SQL Script
5. Select `database/clinic_setup.sql`
6. Execute (Ctrl+Shift+Enter)

## Step 3: Configure PHP Application

### XAMPP Configuration

1. Copy all project files to: `C:\xampp\htdocs\clinic_management\`
2. Verify `config/database.php` settings:
   ```php
   $db_host = 'localhost';
   $db_user = 'root';
   $db_password = '';  // XAMPP default (empty)
   $db_name = 'clinic_management';
   ```

### MAMP Configuration

1. Copy project files to MAMP htdocs folder
2. Update `config/database.php`:
   ```php
   $db_host = 'localhost:3306';  // Include port number
   $db_user = 'root';
   $db_password = 'root';  // MAMP default password
   $db_name = 'clinic_management';
   ```

### Linux/Apache Configuration

1. Copy files to `/var/www/html/clinic_management/`
2. Set permissions:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/clinic_management/
   sudo chmod -R 755 /var/www/html/clinic_management/
   ```
3. Ensure MySQL service is running:
   ```bash
   sudo systemctl status mysql
   ```

## Step 4: Verify Installation

1. Open your browser and navigate to:
   - XAMPP: `http://localhost/clinic_management/login.php`
   - MAMP: `http://localhost:8888/clinic_management/login.php`
   - Linux: `http://localhost/clinic_management/login.php`

2. You should see the login page

3. Test with demo credentials:
   - Username: `admin`
   - Password: `admin`

## Step 5: Common Issues & Solutions

### Issue: "Connection failed: Unknown MySQL server host"

**Solution:**
- Ensure MySQL service is running
- Check `config/database.php` has correct credentials
- Try using IP instead: `'127.0.0.1'` instead of `'localhost'`

### Issue: "Access denied for user 'root'@'localhost'"

**Solution:**
- XAMPP default password is empty (just press Enter)
- MAMP default password is `root`
- Update `config/database.php` with correct password

### Issue: "Database 'clinic_management' doesn't exist"

**Solution:**
- Run the SQL setup script again
- Verify the query executed without errors
- Check MySQL is running properly

### Issue: "Fatal error: Call to undefined function mysqli_connect()"

**Solution:**
- Uncomment `extension=mysqli` in php.ini
- Restart Apache
- Verify PHP MySQLi extension is installed

### Issue: "403 Forbidden" error

**Solution:**
- Check file permissions (should be 755 for directories, 644 for files)
- Ensure Apache has read access
- Check .htaccess configuration

## Step 6: Access the Application

### Demo Accounts

| Role   | Username | Password | Typical Actions                    |
|--------|----------|----------|-----------------------------------|
| Admin  | admin    | admin    | Manage all users, view all data   |
| Doctor | doctor   | doctor   | View patients, create appointments|
| Patient| patient  | patient  | View own appointments/prescriptions|

### Features by Role

**Admin:**
- Dashboard with system statistics
- View all patients
- Add new patients
- Manage appointments
- Manage prescriptions
- View all system users
- Delete users

**Doctor:**
- Dashboard with statistics
- View all patients
- Book appointments
- Create prescriptions
- View all appointments/prescriptions

**Patient:**
- Dashboard
- View own appointments
- View own prescriptions
- View own profile

## Step 7: Testing the Vulnerabilities

Now that the system is running, you can test various security vulnerabilities:

### 1. Test SQL Injection
- Go to Login page
- Username: `admin' OR '1'='1' --`
- Password: `anything`
- You should be logged in as admin!

### 2. Test XSS
- Register as a new patient
- In "Medical History" field, enter: `<img src=x onerror="alert('XSS')">`
- View patient details
- The alert should appear

### 3. Test Password Visibility
- Go to User Management
- Click "View Pass" button
- You can see all users' passwords in plaintext!

### 4. Test Missing Authorization
- Create two accounts (patient1 and patient2)
- Login as patient1
- Try to view patient2's ID in URL: change `?id=2` to `?id=3`
- You can view another patient's records!

### 5. Test CSRF
- Login as admin
- Craft a malicious link: `http://localhost/clinic_management/users.php?delete=2`
- You can delete users without confirmation

## Step 8: File Structure Reference

```
clinic_management/
│
├── config/
│   └── database.php              # Database connection config
│
├── database/
│   └── clinic_setup.sql          # Database schema
│
├── login.php                      # Login page
├── register.php                   # Registration page
├── logout.php                     # Logout handler
│
├── dashboard.php                  # Main dashboard
├── patients.php                   # Patient CRUD operations
├── patient_detail.php             # Patient details view
├── appointments.php               # Appointment management
├── prescriptions.php              # Prescription management
├── users.php                      # User management (admin)
│
├── README.md                      # Project documentation
├── SETUP_GUIDE.md                 # This file
└── .gitignore                     # Git ignore file (if using git)
```

## Step 9: Database Structure

### Tables Created

1. **users** - All system users (admin, doctor, patient)
2. **patients** - Patient-specific information
3. **doctors** - Doctor-specific information
4. **appointments** - Appointment records
5. **prescriptions** - Prescription records
6. **sessions** - Session tracking (if used)

### Sample Query to Check Data

```sql
-- View all users
SELECT * FROM users;

-- View all patients with their user info
SELECT u.full_name, p.age, p.gender, p.medical_history FROM patients p
JOIN users u ON p.user_id = u.id;

-- View all appointments
SELECT a.*, u.full_name as doctor_name 
FROM appointments a
JOIN doctors d ON a.doctor_id = d.id
JOIN users u ON d.user_id = u.id;
```

## Step 10: Security Notes (For Learning)

This system intentionally demonstrates:
- ❌ Plaintext password storage
- ❌ SQL injection vulnerabilities
- ❌ XSS vulnerabilities
- ❌ Missing authentication checks
- ❌ No CSRF protection
- ❌ Direct object reference exposure

When you create the secure V2 version, you'll fix all these!

## Troubleshooting Checklist

Before asking for help, verify:

- [ ] MySQL service is running
- [ ] Apache/PHP web server is running
- [ ] Database was created successfully
- [ ] `config/database.php` has correct credentials
- [ ] All PHP files are in the correct directory
- [ ] File permissions are correct (755 for dirs, 644 for files)
- [ ] PHP MySQLi extension is enabled
- [ ] Browser cache is cleared
- [ ] You're using correct port (usually 80, 8888 for MAMP, 3306 for MySQL)

## Next Steps

1. ✅ Understand the current vulnerabilities
2. ✅ Document security flaws found
3. ✅ Plan the secure version
4. ✅ Implement fixes one by one
5. ✅ Test each security improvement
6. ✅ Create V2 (Secure Version)

---

**Happy Learning!** 🎓
