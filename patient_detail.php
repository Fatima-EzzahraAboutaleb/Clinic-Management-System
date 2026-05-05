<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$patient_id = $_GET['id'] ?? 0;

// VULNERABLE: No input validation or authorization check
$query = "SELECT p.*, u.full_name, u.username, u.email, u.phone as user_phone, u.role 
          FROM patients p 
          JOIN users u ON p.user_id = u.id 
          WHERE p.id = $patient_id";

$result = $conn->query($query);

if ($result->num_rows === 0) {
    header('Location: patients.php');
    exit();
}

$patient = $result->fetch_assoc();

// Get patient's appointments
$appointments_query = "SELECT a.*, d.id as doctor_id, du.full_name as doctor_name 
                       FROM appointments a 
                       JOIN doctors d ON a.doctor_id = d.id 
                       JOIN users du ON d.user_id = du.id 
                       WHERE a.patient_id = $patient_id 
                       ORDER BY a.appointment_date DESC 
                       LIMIT 5";

$appointments_result = $conn->query($appointments_query);

// Get patient's prescriptions
$prescriptions_query = "SELECT p.*, du.full_name as doctor_name 
                        FROM prescriptions p 
                        JOIN doctors d ON p.doctor_id = d.id 
                        JOIN users du ON d.user_id = du.id 
                        WHERE p.patient_id = $patient_id 
                        ORDER BY p.created_at DESC 
                        LIMIT 5";

$prescriptions_result = $conn->query($prescriptions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - Clinic Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f6f7;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .main-content {
            padding: 30px;
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .info-label {
            font-weight: 600;
            color: #667eea;
            margin-top: 15px;
        }
        .info-value {
            color: #333;
            padding: 8px 0;
        }
        .back-link {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
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

    <div class="container main-content">
        <a href="patients.php" class="btn btn-outline-secondary back-link">← Back to Patients</a>

        <div class="row">
            <div class="col-md-8">
                <!-- Patient Info Card -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">👤 Patient Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-label">Full Name:</div>
                                <div class="info-value">
                                    <!-- VULNERABLE: XSS - Direct echo -->
                                    <?php echo htmlspecialchars($patient['full_name']); ?>
                                </div>

                                <div class="info-label">Username:</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['username']); ?></div>

                                <div class="info-label">Email:</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['email']); ?></div>

                                <div class="info-label">Phone:</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['user_phone']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Age:</div>
                                <div class="info-value"><?php echo $patient['age']; ?> years old</div>

                                <div class="info-label">Gender:</div>
                                <div class="info-value"><?php echo $patient['gender']; ?></div>

                                <div class="info-label">User ID:</div>
                                <div class="info-value"><?php echo $patient['id']; ?></div>

                                <div class="info-label">Member Since:</div>
                                <div class="info-value"><?php echo substr($patient['created_at'], 0, 10); ?></div>
                            </div>
                        </div>

                        <hr>

                        <div class="info-label">Medical History:</div>
                        <div class="info-value">
                            <!-- VULNERABLE: XSS - Long medical history displayed -->
                            <textarea class="form-control" rows="5" readonly><?php echo htmlspecialchars($patient['medical_history']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Recent Appointments -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">📅 Recent Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($appointments_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Doctor</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($appt = $appointments_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $appt['appointment_date']; ?></td>
                                                <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                                                <td><?php echo htmlspecialchars($appt['reason']); ?></td>
                                                <td><?php echo strtoupper($appt['status']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No appointments found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Prescriptions -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">💊 Recent Prescriptions</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($prescriptions_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Medication</th>
                                            <th>Dosage</th>
                                            <th>Doctor</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($rx = $prescriptions_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($rx['medication_name']); ?></td>
                                                <td><?php echo htmlspecialchars($rx['dosage']); ?></td>
                                                <td><?php echo htmlspecialchars($rx['doctor_name']); ?></td>
                                                <td><?php echo substr($rx['created_at'], 0, 10); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No prescriptions found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
