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

// Handle Add Appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $reason = $_POST['reason'];
    $notes = $_POST['notes'];

    $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, notes, status) 
                     VALUES ($patient_id, $doctor_id, '$appointment_date', '$reason', '$notes', 'scheduled')";
    
    if ($conn->query($insert_query) === TRUE) {
        $success = "Appointment booked successfully!";
    } else {
        $error = "Error booking appointment: " . $conn->error;
    }
}

// Handle Delete Appointment
if (isset($_GET['delete'])) {
    $appointment_id = $_GET['delete'];
    
    $delete_query = "DELETE FROM appointments WHERE id = $appointment_id";
    
    if ($conn->query($delete_query) === TRUE) {
        $success = "Appointment deleted successfully!";
    } else {
        $error = "Error deleting appointment";
    }
}

// Handle Update Appointment Status
if (isset($_GET['update_status'])) {
    $appointment_id = $_GET['update_status'];
    $status = $_GET['status'];
    
    $update_query = "UPDATE appointments SET status = '$status' WHERE id = $appointment_id";
    
    if ($conn->query($update_query) === TRUE) {
        $success = "Appointment status updated!";
    } else {
        $error = "Error updating appointment";
    }
}

// Fetch appointments
if ($role === 'patient') {
    // Get patient id
    $patient_query = "SELECT id FROM patients WHERE user_id = $user_id";
    $patient_result = $conn->query($patient_query);
    
    if ($patient_result->num_rows > 0) {
        $patient = $patient_result->fetch_assoc();
        $patient_id = $patient['id'];
        
        $appointments_query = "SELECT a.*, u.full_name as doctor_name, p.id as patient_id, pu.full_name as patient_name 
                               FROM appointments a 
                               JOIN doctors d ON a.doctor_id = d.id 
                               JOIN users u ON d.user_id = u.id 
                               JOIN patients p ON a.patient_id = p.id 
                               JOIN users pu ON p.user_id = pu.id 
                               WHERE a.patient_id = $patient_id 
                               ORDER BY a.appointment_date DESC";
    } else {
        $appointments_query = "SELECT 1 WHERE 0"; // Empty result
    }
} else {
    // Admin and Doctor can see all
    $appointments_query = "SELECT a.*, u.full_name as doctor_name, p.id as patient_id, pu.full_name as patient_name 
                           FROM appointments a 
                           JOIN doctors d ON a.doctor_id = d.id 
                           JOIN users u ON d.user_id = u.id 
                           JOIN patients p ON a.patient_id = p.id 
                           JOIN users pu ON p.user_id = pu.id 
                           ORDER BY a.appointment_date DESC";
}

$appointments_result = $conn->query($appointments_query);

// Fetch patients and doctors for dropdowns
$patients_query = "SELECT p.id, u.full_name FROM patients p JOIN users u ON p.user_id = u.id";
$patients_result = $conn->query($patients_query);

$doctors_query = "SELECT d.id, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id";
$doctors_result = $conn->query($doctors_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Clinic Management System</title>
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
        .btn-primary {
            background-color: #667eea;
            border-color: #667eea;
        }
        .btn-primary:hover {
            background-color: #764ba2;
            border-color: #764ba2;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-scheduled {
            background-color: #cfe2ff;
            color: #084298;
        }
        .status-completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #842029;
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
                <a href="patients.php">👨‍⚕️ Patients</a>
                <a href="appointments.php" class="active">📅 Appointments</a>
                <a href="prescriptions.php">💊 Prescriptions</a>
                <?php if ($role === 'admin'): ?>
                    <a href="users.php">👥 Users</a>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h2 style="margin-bottom: 30px;">📅 Appointment Management</h2>

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

                <!-- Add Appointment Form (only for admin and doctor) -->
                <?php if ($role !== 'patient'): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">➕ Book New Appointment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Patient:</label>
                                <select class="form-control" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php 
                                    // Reset the pointer
                                    $patients_result->data_seek(0);
                                    while ($patient = $patients_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Doctor:</label>
                                <select class="form-control" name="doctor_id" required>
                                    <option value="">Select Doctor</option>
                                    <?php 
                                    $doctors_result->data_seek(0);
                                    while ($doctor = $doctors_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $doctor['id']; ?>">
                                            <?php echo htmlspecialchars($doctor['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date & Time:</label>
                                <input type="datetime-local" class="form-control" name="appointment_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reason:</label>
                                <input type="text" class="form-control" name="reason">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes:</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_appointment" class="btn btn-primary">Book Appointment</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Appointments Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">All Appointments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Date & Time</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                            <td><?php echo $appointment['appointment_date']; ?></td>
                                            <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                    <?php echo strtoupper($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <!-- VULNERABLE: XSS - Direct echo of user input -->
                                                <small><?php echo substr($appointment['notes'], 0, 30); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($appointment['status'] !== 'completed'): ?>
                                                    <a href="appointments.php?update_status=<?php echo $appointment['id']; ?>&status=completed" class="btn btn-sm btn-success">Complete</a>
                                                <?php endif; ?>
                                                <a href="appointments.php?delete=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this appointment?');">Delete</a>
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
