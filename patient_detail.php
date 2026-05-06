<?php
session_start();
require_once 'config/database.php'; // Doit contenir $pdo (PDO)

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_role   = $_SESSION['role'];

// Récupération et validation de l'ID patient
$patient_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$patient_id || $patient_id <= 0) {
    // ID invalide -> redirection
    header('Location: patients.php');
    exit();
}

// --- 1. Requête préparée pour récupérer les infos du patient ---
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name, u.username, u.email, u.phone as user_phone, u.role 
        FROM patients p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En production, logguer l'erreur sans l'afficher
    error_log($e->getMessage());
    header('Location: patients.php');
    exit();
}

if (!$patient) {
    // Patient inexistant
    header('Location: patients.php');
    exit();
}

// --- 2. Contrôle d'accès : qui peut voir ce patient ? ---
$allowed = false;
if ($current_role === 'admin') {
    $allowed = true;                     // admin voit tout
} elseif ($current_role === 'doctor') {
    // Vérifier si le médecin connecté est bien celui assigné à ce patient
    // Hypothèse : la table 'patients' a une colonne 'assigned_doctor_id'
    // Si cette colonne n'existe pas, adapter selon votre schéma réel.
    $stmt2 = $pdo->prepare("SELECT assigned_doctor_id FROM patients WHERE id = ?");
    $stmt2->execute([$patient_id]);
    $assigned = $stmt2->fetchColumn();
    if ($assigned == $current_user_id) {
        $allowed = true;
    }
} elseif ($current_role === 'patient') {
    // Le patient ne peut voir que sa propre fiche
    // Comparer l'user_id du patient avec l'user_id connecté
    if ($patient['user_id'] == $current_user_id) {
        $allowed = true;
    }
}

if (!$allowed) {
    // Accès refusé
    header('HTTP/1.0 403 Forbidden');
    die("Accès refusé : vous n'êtes pas autorisé à consulter ce dossier.");
}

// --- 3. Rendez-vous récents (requête préparée) ---
$appointments = [];
try {
    $stmt_app = $pdo->prepare("
        SELECT a.*, d.id as doctor_id, du.full_name as doctor_name 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        JOIN users du ON d.user_id = du.id 
        WHERE a.patient_id = :pid 
        ORDER BY a.appointment_date DESC 
        LIMIT 5
    ");
    $stmt_app->execute([':pid' => $patient_id]);
    $appointments = $stmt_app->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $appointments = [];
}

// --- 4. Prescriptions récentes (requête préparée) ---
$prescriptions = [];
try {
    $stmt_rx = $pdo->prepare("
        SELECT p.*, du.full_name as doctor_name 
        FROM prescriptions p 
        JOIN doctors d ON p.doctor_id = d.id 
        JOIN users du ON d.user_id = du.id 
        WHERE p.patient_id = :pid 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmt_rx->execute([':pid' => $patient_id]);
    $prescriptions = $stmt_rx->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $prescriptions = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails patient - Clinic Management System (Sécurisé)</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f6f7; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .main-content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .info-label { font-weight: 600; color: #667eea; margin-top: 15px; }
        .info-value { color: #333; padding: 8px 0; }
        .back-link { margin-bottom: 20px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">🏥 Clinic Management System </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <a href="patients.php" class="btn btn-outline-secondary back-link">← Retour aux patients</a>

        <div class="row">
            <div class="col-md-8">
                <!-- Informations patient -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">👤 Informations patient</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-label">Nom complet :</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['full_name']); ?></div>

                                <div class="info-label">Nom d'utilisateur :</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['username']); ?></div>

                                <div class="info-label">Email :</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['email']); ?></div>

                                <div class="info-label">Téléphone :</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['user_phone']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Âge :</div>
                                <div class="info-value"><?php echo (int)$patient['age']; ?> ans</div>

                                <div class="info-label">Genre :</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['gender']); ?></div>

                                <div class="info-label">ID patient :</div>
                                <div class="info-value"><?php echo (int)$patient['id']; ?></div>

                                <div class="info-label">Membre depuis :</div>
                                <div class="info-value"><?php echo htmlspecialchars(substr($patient['created_at'], 0, 10)); ?></div>
                            </div>
                        </div>

                        <hr>

                        <div class="info-label">Antécédents médicaux :</div>
                        <div class="info-value">
                            <textarea class="form-control" rows="5" readonly><?php echo htmlspecialchars($patient['medical_history']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Derniers rendez-vous -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">📅 Derniers rendez-vous</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($appointments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th><th>Médecin</th><th>Motif</th><th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appt): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($appt['appointment_date']); ?></td>
                                                <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                                                <td><?php echo htmlspecialchars($appt['reason']); ?></td>
                                                <td><?php echo htmlspecialchars(strtoupper($appt['status'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucun rendez-vous trouvé.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Dernières prescriptions -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">💊 Dernières prescriptions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($prescriptions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Médicament</th><th>Posologie</th><th>Médecin</th><th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prescriptions as $rx): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($rx['medication_name']); ?></td>
                                                <td><?php echo htmlspecialchars($rx['dosage']); ?></td>
                                                <td><?php echo htmlspecialchars($rx['doctor_name']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($rx['created_at'], 0, 10)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune prescription trouvée.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>