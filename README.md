# 🏥 Clinic Management System - UNSECURED VERSION V0

This is an **intentionally vulnerable** Clinic Management System built with PHP and MySQL for **educational purposes only**. It demonstrates common security vulnerabilities that developers should know about and avoid in production systems.

⚠️ **WARNING: DO NOT USE IN PRODUCTION** ⚠️

## 📋 Features

### Authentication (Intentionally Weak)
- User registration and login system for Admin, Doctor, and Patient roles
- **Passwords stored in PLAIN TEXT** (NO hashing)
- **No input validation or sanitization**
- **No CSRF protection**
- **Weak session management**

### Patient Management
- Add, edit, delete, and view patient records
- Store patient information: name, age, gender, phone, medical history
- View all patients in a table

### Appointment System
- Book appointments between patients and doctors
- **No conflict checking** - multiple appointments at same time allowed
- **No input validation** on dates/times
- Update appointment status (scheduled, completed, cancelled)

### Prescription Management
- Doctors can add prescriptions for patients
- Store medication name, dosage, duration, and notes
- View prescriptions per patient

### Admin Dashboard
- View all users (patients, doctors, admins)
- Delete users directly
- **No role-based access control** - any user can access admin features

## ⚠️ Intentional Vulnerabilities

This system contains the following security flaws **by design**:

### 1. **SQL Injection**
```php
$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
// User input directly concatenated into SQL query
```

### 2. **Cross-Site Scripting (XSS)**
```php
<?php echo $user['medical_history']; ?>
<!-- User input echoed directly without escaping -->
```

### 3. **Plaintext Passwords**
```php
INSERT INTO users (password) VALUES ('$password');
// Password stored as plain text, no hashing
```

### 4. **No Authentication/Authorization**
- Pages don't verify user role
- Any authenticated user can access any feature
- URL manipulation allows unauthorized actions

### 5. **No CSRF Protection**
- No CSRF tokens on forms
- Direct GET requests delete data: `?delete=123`

### 6. **No Input Validation**
- All user inputs accepted without checking
- No type validation or length limits

### 7. **Direct Object References**
- Patient/appointment IDs exposed in URLs
- No verification that user owns the data

### 8. **No Rate Limiting**
- Unlimited login attempts possible
- Brute force attacks feasible

## 🚀 Installation

### Prerequisites
- PHP 7.4+ with MySQLi extension
- MySQL 5.7+
- A web server (Apache, Nginx, etc.)

### Setup Steps

1. **Create Database**
   ```bash
   mysql -u root -p < database/clinic_setup.sql
   ```
   Or manually import `database/clinic_setup.sql` into your MySQL client.

2. **Configure Database Connection**
   Edit `config/database.php` if needed:
   ```php
   $db_host = 'localhost';
   $db_user = 'root';
   $db_password = ''; // Your MySQL password
   $db_name = 'clinic_management';
   ```

3. **Place Files on Web Server**
   - Copy all PHP files to your web root directory
   - Ensure proper file permissions

4. **Access the Application**
   ```
   http://localhost/clinic_management/login.php
   ```

## 👤 Demo Accounts

Use these to test the system:

| Role   | Username | Password |
|--------|----------|----------|
| Admin  | admin    | admin    |
| Doctor | doctor   | doctor   |
| Patient| patient  | patient  |

## 📁 Project Structure

```
clinic_management/
├── config/
│   └── database.php          # Database connection
├── database/
│   └── clinic_setup.sql      # Database schema
├── login.php                 # User login
├── register.php              # User registration
├── logout.php                # Logout
├── dashboard.php             # Main dashboard
├── patients.php              # Patient management
├── patient_detail.php        # Patient details
├── appointments.php          # Appointment management
├── prescriptions.php         # Prescription management
├── users.php                 # Admin user management
└── README.md                 # This file
```

## 🔐 How This Demonstrates Vulnerabilities

This system is perfect for:
- **Learning**: Understanding how vulnerabilities work in practice
- **Security Training**: Demonstrating real-world security flaws
- **Code Review**: Practice identifying vulnerabilities in code
- **Remediation**: Using as a base for secure version (V1)

### Example Vulnerability Exploitation

**SQL Injection in Login:**
```
Username: admin' OR '1'='1
Password: anything

This will log in as admin without knowing the password!
```

**XSS in Medical History:**
```
Add patient with medical history:
<img src=x onerror="alert('XSS Vulnerability!')">

The alert will execute when the patient record is viewed.
```

## 🛡️ Next Steps: Creating Secure Version (V1)

To secure this application, you would need to:

1. **Hash passwords** with bcrypt or Argon2
2. **Use prepared statements** with parameterized queries
3. **Escape output** with htmlspecialchars() or HTML entities
4. **Add CSRF tokens** to all forms
5. **Implement role-based access control** (RBAC)
6. **Add input validation** on all user inputs
7. **Use HTTPS** for all connections
8. **Implement rate limiting** on login attempts
9. **Add security headers** (CSP, X-Frame-Options, etc.)
10. **Use an ORM or query builder** to prevent SQL injection

## ⚡ Quick Reference: Security Checks

When reviewing code, look for:
- ✅ Are user inputs validated?
- ✅ Are database queries using prepared statements?
- ✅ Are outputs properly escaped?
- ✅ Is authentication/authorization checked?
- ✅ Are CSRF tokens used?
- ✅ Are passwords hashed?
- ✅ Is sensitive data encrypted?
- ✅ Are errors handled securely?

## 📚 Educational Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security](https://www.php.net/manual/en/security.php)
- [SQL Injection](https://owasp.org/www-community/attacks/SQL_Injection)
- [Cross-Site Scripting](https://owasp.org/www-community/attacks/xss/)

## ⚖️ Disclaimer

**This software is provided for educational purposes only.** Unauthorized use of this system to test security of systems you do not own or have permission to test is illegal and unethical.

The author assumes no responsibility for misuse of this software.

## 📝 License

Educational use only. Not intended for production.

---

**Remember**: Understanding vulnerabilities helps you build better, more secure applications! 🔒
