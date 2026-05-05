<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success = '';
$error = '';


// Handle Delete User
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $delete_query = "DELETE FROM users WHERE id = $delete_id";
    
    if ($conn->query($delete_query) === TRUE) {
        $success = "User deleted successfully!";
    } else {
        $error = "Error deleting user";
    }
}

// Fetch all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Clinic Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f6f7;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        .role-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .role-admin {
            background-color: #f8d7da;
            color: #721c24;
        }
        .role-doctor {
            background-color: #cfe2ff;
            color: #084298;
        }
        .role-patient {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .alert {
            border-radius: 8px;
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
                        <span class="nav-link" style="color: white; cursor: default;">
                            Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
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
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="patients.php">👨‍⚕️ Patients</a>
                <a href="appointments.php">📅 Appointments</a>
                <a href="prescriptions.php">💊 Prescriptions</a>
                <a href="users.php" class="active">👥 Users</a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h2 style="margin-bottom: 30px;">👥 User Management</h2>

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

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">All System Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Phone</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $users_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td>
                                                <!-- VULNERABLE: XSS - Direct echo of username -->
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                                    <?php echo strtoupper($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td><?php echo substr($user['created_at'], 0, 10); ?></td>
                                            <td>
                                                <!-- VULNERABLE: Password visible in plain text -->
                                                <button class="btn btn-sm btn-info" onclick="alert('Password: <?php echo htmlspecialchars($user['password']); ?>');">View Pass</button>
                                                <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?');">Delete</a>
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
