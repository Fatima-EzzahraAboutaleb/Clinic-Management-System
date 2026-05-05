<?php
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    $phone = $_POST['phone'] ?? '';

    $check_query = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($check_query);

    if ($result->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        $insert_query = "INSERT INTO users (username, password, email, full_name, role, phone) 
                         VALUES ('$username', '$password', '$email', '$full_name', '$role', '$phone')";
        
        if ($conn->query($insert_query) === TRUE) {
            $user_id = $conn->insert_id;
            
            // Create patient or doctor profile
            if ($role === 'patient') {
                $age = $_POST['age'] ?? 0;
                $gender = $_POST['gender'] ?? 'Other';
                $medical_history = $_POST['medical_history'] ?? '';
                
                $profile_query = "INSERT INTO patients (user_id, age, gender, medical_history, phone) 
                                 VALUES ($user_id, $age, '$gender', '$medical_history', '$phone')";
                $conn->query($profile_query);
            } elseif ($role === 'doctor') {
                $specialty = $_POST['specialty'] ?? '';
                
                $profile_query = "INSERT INTO doctors (user_id, specialty, phone) 
                                 VALUES ($user_id, '$specialty', '$phone')";
                $conn->query($profile_query);
            }
            
            $success = "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            $error = "Registration failed: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Clinic Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .register-container h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .btn-register {
            width: 100%;
            padding: 10px;
            background-color: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-register:hover {
            background-color: #764ba2;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
        }
        .alert {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 5px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .role-fields {
            display: none;
        }
        .role-fields.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>🏥 Clinic Management System</h1>
        <h3 style="text-align: center; margin-bottom: 30px; color: #666; font-size: 18px;">Register Account</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Phone:</label>
                <input type="text" name="phone">
            </div>

            <div class="form-group">
                <label>Register as:</label>
                <select name="role" id="role" required onchange="toggleRoleFields()">
                    <option value="">Select Role</option>
                    <option value="patient">Patient</option>
                    <option value="doctor">Doctor</option>
                </select>
            </div>

            <!-- Patient Fields -->
            <div id="patient-fields" class="role-fields">
                <div class="form-group">
                    <label>Age:</label>
                    <input type="number" name="age" min="0" max="120">
                </div>

                <div class="form-group">
                    <label>Gender:</label>
                    <select name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Medical History:</label>
                    <textarea name="medical_history"></textarea>
                </div>
            </div>

            <!-- Doctor Fields -->
            <div id="doctor-fields" class="role-fields">
                <div class="form-group">
                    <label>Specialty:</label>
                    <input type="text" name="specialty" placeholder="e.g., Cardiology, Pediatrics">
                </div>
            </div>

            <button type="submit" class="btn-register">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script>
        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            const patientFields = document.getElementById('patient-fields');
            const doctorFields = document.getElementById('doctor-fields');

            patientFields.classList.remove('show');
            doctorFields.classList.remove('show');

            if (role === 'patient') {
                patientFields.classList.add('show');
            } else if (role === 'doctor') {
                doctorFields.classList.add('show');
            }
        }
    </script>
</body>
</html>
