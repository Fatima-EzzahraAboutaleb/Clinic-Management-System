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

// Handle Add Prescription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $medication_name = $_POST['medication_name'];
    $dosage = $_POST['dosage'];
    $duration = $_POST['duration'];
    $notes = $_POST['notes'];

    $insert_query = "INSERT INTO prescriptions (patient_id, doctor_id, medication_name, dosage, duration, notes) 
                     VALUES ($patient_id, $doctor_id, '$medication_name', '$dosage', '$duration', '$notes')";
    
    if ($conn->query($insert_query) === TRUE) {
        $success = "Prescription added successfully!";
    } else {
        $error = "Error adding prescription: " . $conn->error;
    }
}

if (isset($_GET['delete'])) {
    $prescription_id = $_GET['delete'];
    
    $delete_query = "DELETE FROM prescriptions WHERE id = $prescription_id";
    
    if ($conn->query($delete_query) === TRUE) {
        $success = "Prescription deleted successfully!";
    } else {
        $error = "Error deleting prescription";
    }
}

// Fetch prescriptions
if ($role === 'patient') {
    // Get patient id
    $patient_query = "SELECT id FROM patients WHERE user_id = $user_id";
    $patient_result = $conn->query($patient_query);
    
    if ($patient_result->num_rows > 0) {
        $patient = $patient_result->fetch_assoc();
        $patient_id = $patient['id'];
        
        $prescriptions_query = "SELECT p.*, u.full_name as doctor_name, pat.id as patient_id, pu.full_name as patient_name 
                                FROM prescriptions p 
                                JOIN doctors d ON p.doctor_id = d.id 
                                JOIN users u ON d.user_id = u.id 
                                JOIN patients pat ON p.patient_id = pat.id 
                                JOIN users pu ON pat.user_id = pu.id 
                                WHERE p.patient_id = $patient_id 
                                ORDER BY p.created_at DESC";
    } else {
        $prescriptions_query = "SELECT 1 WHERE 0";
    }
} else {
    $prescriptions_query = "SELECT p.*, u.full_name as doctor_name, pat.id as patient_id, pu.full_name as patient_name 
                            FROM prescriptions p 
                            JOIN doctors d ON p.doctor_id = d.id 
                            JOIN users u ON d.user_id = u.id 
                            JOIN patients pat ON p.patient_id = pat.id 
                            JOIN users pu ON pat.user_id = pu.id 
                            ORDER BY p.created_at DESC";
}

$prescriptions_result = $conn->query($prescriptions_query);

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
    <title>Prescriptions - Clinic Management System</title>
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
        .table-responsive {
            overflow-x: auto;
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
                <a href="appointments.php">📅 Appointments</a>
                <a href="prescriptions.php" class="active">💊 Prescriptions</a>
                <?php if ($role === 'admin'): ?>
                    <a href="users.php">👥 Users</a>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h2 style="margin-bottom: 30px;">💊 Prescription Management</h2>

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

                <!-- Add Prescription Form (only for doctors and admin) -->
                <?php if ($role !== 'patient'): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">➕ Add New Prescription</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Patient:</label>
                                <select class="form-control" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php 
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
                                <label class="form-label">Medication Name:</label>
                                <input type="text" class="form-control" name="medication_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Dosage:</label>
                                <input type="text" class="form-control" name="dosage" placeholder="e.g., 500mg twice daily" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Duration:</label>
                                <input type="text" class="form-control" name="duration" placeholder="e.g., 7 days">
                            </div>
                            <div class="col-md-6"></div>
                            <div class="col-12">
                                <label class="form-label">Notes:</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_prescription" class="btn btn-primary">Add Prescription</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Prescriptions Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">All Prescriptions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Medication</th>
                                        <th>Dosage</th>
                                        <th>Duration</th>
                                        <th>Notes</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($prescription = $prescriptions_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($prescription['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['medication_name']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['dosage']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['duration']); ?></td>
                                            <td>
                                                <small><?php echo substr($prescription['notes'], 0, 30); ?></small>
                                            </td>
                                            <td><?php echo substr($prescription['created_at'], 0, 10); ?></td>
                                            <td>
                                                <a href="prescriptions.php?delete=<?php echo $prescription['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this prescription?');">Delete</a>
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
