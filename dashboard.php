<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

$stats = [
    'patients' => 0,
    'doctors' => 0,
    'appointments' => 0,
    'prescriptions' => 0
];

$query = "SELECT COUNT(*) as count FROM patients";
$result = $conn->query($query);
$stats['patients'] = $result->fetch_assoc()['count'];

$query = "SELECT COUNT(*) as count FROM doctors";
$result = $conn->query($query);
$stats['doctors'] = $result->fetch_assoc()['count'];

$query = "SELECT COUNT(*) as count FROM appointments";
$result = $conn->query($query);
$stats['appointments'] = $result->fetch_assoc()['count'];

$query = "SELECT COUNT(*) as count FROM prescriptions";
$result = $conn->query($query);
$stats['prescriptions'] = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Clinic Management System</title>
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
            font-size: 20px;
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 10px;
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .role-badge {
            background-color: #764ba2;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            text-transform: uppercase;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">🏥 Clinic Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link" style="color: white; cursor: default;">
                            Welcome, <?php echo htmlspecialchars($full_name); ?>
                            <span class="role-badge"><?php echo strtoupper($role); ?></span>
                        </span>
                    </li>
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
                <a href="dashboard.php" class="active">📊 Dashboard</a>
                
                <?php if ($role === 'admin'): ?>
                    <a href="users.php">👥 Manage Users</a>
                    <a href="patients.php">👨‍⚕️ Manage Patients</a>
                    <a href="appointments.php">📅 Appointments</a>
                    <a href="prescriptions.php">💊 Prescriptions</a>
                <?php elseif ($role === 'doctor'): ?>
                    <a href="patients.php">👨‍⚕️ Patients</a>
                    <a href="appointments.php">📅 My Appointments</a>
                    <a href="prescriptions.php">💊 Prescriptions</a>
                <?php elseif ($role === 'patient'): ?>
                    <a href="appointments.php">📅 My Appointments</a>
                    <a href="prescriptions.php">💊 My Prescriptions</a>
                    <a href="profile.php">👤 My Profile</a>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="welcome-card">
                    <h2>Welcome back, <?php echo htmlspecialchars($full_name); ?>! 👋</h2>
                    <p>You are logged in as <strong><?php echo strtoupper($role); ?></strong></p>
                </div>

                <h4 style="margin-bottom: 20px;">System Statistics</h4>
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['patients']; ?></div>
                            <div class="stat-label">Total Patients</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['doctors']; ?></div>
                            <div class="stat-label">Total Doctors</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['appointments']; ?></div>
                            <div class="stat-label">Total Appointments</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['prescriptions']; ?></div>
                            <div class="stat-label">Total Prescriptions</div>
                        </div>
                    </div>
                </div>

                
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
