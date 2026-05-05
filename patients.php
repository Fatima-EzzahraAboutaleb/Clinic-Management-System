<?php
session_start();
require_once 'config/database.php';

// VULNERABLE: No role-based access control
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$action = $_GET['action'] ?? '';
$success = '';
$error = '';

// Handle Add Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    // VULNERABLE: No input validation
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $medical_history = $_POST['medical_history'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];

    // VULNERABLE: SQL Injection
    $insert_user = "INSERT INTO users (username, password, email, full_name, role, phone) 
                    VALUES ('$username', '$password', '$email', '$full_name', 'patient', '$phone')";
    
    if ($conn->query($insert_user) === TRUE) {
        $patient_user_id = $conn->insert_id;
        
        // VULNERABLE: SQL Injection
        $insert_patient = "INSERT INTO patients (user_id, age, gender, medical_history, phone) 
                           VALUES ($patient_user_id, $age, '$gender', '$medical_history', '$phone')";
        
        if ($conn->query($insert_patient) === TRUE) {
            $success = "Patient added successfully!";
        } else {
            $error = "Error adding patient: " . $conn->error;
        }
    } else {
        $error = "Error creating user: " . $conn->error;
    }
}

// Handle Delete Patient
if (isset($_GET['delete'])) {
    $patient_id = $_GET['delete'];
    
    // VULNERABLE: No CSRF protection, direct deletion
    // First, get user_id from patient
    $query = "SELECT user_id FROM patients WHERE id = $patient_id";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        
        // Delete patient
        $delete_patient = "DELETE FROM patients WHERE id = $patient_id";
        // Delete user
        $delete_user = "DELETE FROM users WHERE id = " . $patient['user_id'];
        
        if ($conn->query($delete_patient) && $conn->query($delete_user)) {
            $success = "Patient deleted successfully!";
        } else {
            $error = "Error deleting patient";
        }
    }
}

// Fetch all patients
$patients_query = "SELECT p.id, p.age, p.gender, p.phone, p.medical_history, u.full_name, u.username 
                   FROM patients p 
                   JOIN users u ON p.user_id = u.id 
                   ORDER BY u.full_name";
$patients_result = $conn->query($patients_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - Clinic Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f6f7;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-weight: bold;
        }
        .sidebar {
            background: white;
            min-height: 100vh;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
        }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: 0.3s;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background-color: #f0f0f0;
            border-left-color: #667eea;
            color: #667eea;
        }
        .main-content {
            padding: 30px;
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .table {
            background: white;
        }
        .btn-primary {
            background-color: #667eea;
            border-color: #667eea;
        }
        .btn-primary:hover {
            background-color: #764ba2;
            border-color: #764ba2;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-edit {
            background-color: #28a745;
        }
        .alert {
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .modal-content {
            border-radius: 10px;
        }
        .table-striped tbody tr:hover {
            background-color: #f8f9ff;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">🏥 Clinic Management System</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h5 style="padding: 0 20px; margin-bottom: 20px;">Menu</h5>
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="patients.php" class="active">👨‍⚕️ Patients</a>
                <a href="appointments.php">📅 Appointments</a>
                <a href="prescriptions.php">💊 Prescriptions</a>
                <?php if ($role === 'admin'): ?>
                    <a href="users.php">👥 Users</a>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h2 style="margin-bottom: 30px;">👨‍⚕️ Patient Management</h2>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Add Patient Form -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">➕ Add New Patient</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name:</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username:</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password:</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email:</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone:</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Age:</label>
                                <input type="number" class="form-control" name="age" min="0" max="120">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender:</label>
                                <select class="form-control" name="gender">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6"></div>
                            <div class="col-12">
                                <label class="form-label">Medical History:</label>
                                <textarea class="form-control" name="medical_history" rows="4"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_patient" class="btn btn-primary">Add Patient</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Patients Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">All Patients</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Phone</th>
                                        <th>Medical History</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($patient = $patients_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($patient['username']); ?></td>
                                            <td><?php echo $patient['age']; ?></td>
                                            <td><?php echo $patient['gender']; ?></td>
                                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                            <td>
                                                <small>
                                                    <!-- VULNERABLE: XSS - Directly echoing user input -->
                                                    <?php echo substr($patient['medical_history'], 0, 50); ?>
                                                    <?php if (strlen($patient['medical_history']) > 50): ?>...<?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <a href="patient_detail.php?id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-edit">View</a>
                                                <a href="patients.php?delete=<?php echo $patient['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this patient?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
