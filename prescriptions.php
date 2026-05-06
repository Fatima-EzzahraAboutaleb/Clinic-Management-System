<?php
session_start();
require_once 'config/database.php'; // Doit fournir une connexion PDO nommée $pdo

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$success = '';
$error   = '';

// Génération d'un token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- AJOUT D'UNE PRESCRIPTION (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription'])) {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Erreur de validation CSRF. Veuillez réessayer.";
    } else {
        // Nettoyage et validation des entrées
        $patient_id      = filter_var($_POST['patient_id'] ?? 0, FILTER_VALIDATE_INT);
        $doctor_id       = filter_var($_POST['doctor_id'] ?? 0, FILTER_VALIDATE_INT);
        $medication_name = trim($_POST['medication_name'] ?? '');
        $dosage          = trim($_POST['dosage'] ?? '');
        $duration        = trim($_POST['duration'] ?? '');
        $notes           = trim($_POST['notes'] ?? '');

        if (!$patient_id || !$doctor_id || empty($medication_name) || empty($dosage)) {
            $error = "Veuillez remplir tous les champs obligatoires (Patient, Médecin, Médicament, Posologie).";
        } else {
            // Vérification des droits : seul un admin ou un médecin peut ajouter
            if ($role !== 'admin' && $role !== 'doctor') {
                $error = "Vous n'avez pas les droits pour ajouter une prescription.";
            } else {
                // Si l'utilisateur est médecin, on force l'ID du médecin à son propre ID
                if ($role === 'doctor') {
                    // Récupérer l'ID du docteur correspondant à l'utilisateur connecté
                    $stmtDoc = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
                    $stmtDoc->execute([$user_id]);
                    $docRow = $stmtDoc->fetch(PDO::FETCH_ASSOC);
                    if (!$docRow) {
                        $error = "Votre compte médecin n'est pas correctement configuré.";
                    } else {
                        $doctor_id = $docRow['id']; // Forcer l'ID du médecin connecté
                    }
                }

                if (empty($error)) {
                    try {
                        $insert = $pdo->prepare("
                            INSERT INTO prescriptions (patient_id, doctor_id, medication_name, dosage, duration, notes)
                            VALUES (:patient_id, :doctor_id, :medication_name, :dosage, :duration, :notes)
                        ");
                        $insert->execute([
                            ':patient_id'      => $patient_id,
                            ':doctor_id'       => $doctor_id,
                            ':medication_name' => $medication_name,
                            ':dosage'          => $dosage,
                            ':duration'        => $duration,
                            ':notes'           => $notes
                        ]);
                        $success = "Prescription ajoutée avec succès !";
                    } catch (PDOException $e) {
                        error_log($e->getMessage());
                        $error = "Erreur lors de l'ajout de la prescription.";
                    }
                }
            }
        }
    }
}

// --- SUPPRESSION D'UNE PRESCRIPTION (POST avec CSRF) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_prescription'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Erreur de validation CSRF.";
    } else {
        $prescription_id = filter_var($_POST['prescription_id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$prescription_id) {
            $error = "ID de prescription invalide.";
        } else {
            // Vérification des droits : seule un admin peut supprimer (ou le médecin propriétaire)
            // Pour cet exemple, seule l'admin peut supprimer.
            if ($role !== 'admin') {
                $error = "Vous n'avez pas les droits nécessaires pour supprimer une prescription.";
            } else {
                try {
                    $delete = $pdo->prepare("DELETE FROM prescriptions WHERE id = :id");
                    $delete->execute([':id' => $prescription_id]);
                    $success = "Prescription supprimée avec succès.";
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    $error = "Erreur lors de la suppression.";
                }
            }
        }
    }
}

// --- RÉCUPÉRATION DES LISTES (PATIENTS, MÉDECINS, PRESCRIPTIONS) ---
// 1. Liste des patients (pour le formulaire d'ajout)
$patients_list = [];
if ($role === 'admin' || $role === 'doctor') {
    try {
        $stmtPats = $pdo->query("SELECT p.id, u.full_name FROM patients p JOIN users u ON p.user_id = u.id ORDER BY u.full_name");
        $patients_list = $stmtPats->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// 2. Liste des médecins (pour le formulaire d'ajout) - seulement si admin, sinon le médecin est fixé
$doctors_list = [];
if ($role === 'admin') {
    try {
        $stmtDocs = $pdo->query("SELECT d.id, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.full_name");
        $doctors_list = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
} elseif ($role === 'doctor') {
    // Récupérer l'ID du médecin connecté pour l'afficher en lecture seule (ou cacher le champ)
    try {
        $stmtDoc = $pdo->prepare("SELECT d.id, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.user_id = ?");
        $stmtDoc->execute([$user_id]);
        $doctors_list = $stmtDoc->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// 3. Récupération des prescriptions (avec droits)
$prescriptions = [];
try {
    if ($role === 'patient') {
        // Récupérer l'id du patient correspondant à l'utilisateur connecté
        $stmtPatId = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
        $stmtPatId->execute([$user_id]);
        $patRow = $stmtPatId->fetch(PDO::FETCH_ASSOC);
        if ($patRow) {
            $patient_id_self = $patRow['id'];
            $sql = "
                SELECT p.*, u.full_name as doctor_name, pat.id as patient_id, pu.full_name as patient_name 
                FROM prescriptions p 
                JOIN doctors d ON p.doctor_id = d.id 
                JOIN users u ON d.user_id = u.id 
                JOIN patients pat ON p.patient_id = pat.id 
                JOIN users pu ON pat.user_id = pu.id 
                WHERE p.patient_id = :patient_id
                ORDER BY p.created_at DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':patient_id' => $patient_id_self]);
            $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        // admin ou doctor : tous les médecins voient toutes les prescriptions (ou restreindre selon le médecin)
        $sql = "
            SELECT p.*, u.full_name as doctor_name, pat.id as patient_id, pu.full_name as patient_name 
            FROM prescriptions p 
            JOIN doctors d ON p.doctor_id = d.id 
            JOIN users u ON d.user_id = u.id 
            JOIN patients pat ON p.patient_id = pat.id 
            JOIN users pu ON pat.user_id = pu.id 
            ORDER BY p.created_at DESC
        ";
        $stmt = $pdo->query($sql);
        $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
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
    <title>Prescriptions - Clinic Management System (Sécurisé)</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f6f7; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .sidebar { background: white; min-height: 100vh; padding: 20px 0; box-shadow: 2px 0 5px rgba(0,0,0,0.05); }
        .sidebar a { display: block; padding: 12px 20px; color: #333; text-decoration: none; border-left: 4px solid transparent; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background-color: #f0f0f0; border-left-color: #667eea; color: #667eea; }
        .main-content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn-primary { background-color: #667eea; border-color: #667eea; }
        .btn-primary:hover { background-color: #764ba2; border-color: #764ba2; }
        .table-responsive { overflow-x: auto; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">🏥 Clinic Management System </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="logout.php">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <h5 style="padding: 0 20px; margin-bottom: 20px;">Menu</h5>
                <a href="dashboard.php">📊 Tableau de bord</a>
                <a href="patients.php">👨‍⚕️ Patients</a>
                <a href="appointments.php">📅 Rendez-vous</a>
                <a href="prescriptions.php" class="active">💊 Prescriptions</a>
                <?php if ($role === 'admin'): ?>
                    <a href="users.php">👥 Utilisateurs</a>
                <?php endif; ?>
            </div>

            <div class="col-md-10 main-content">
                <h2 style="margin-bottom: 30px;">💊 Gestion des prescriptions</h2>

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

                <!-- Formulaire d'ajout de prescription (seulement pour admin et médecins) -->
                <?php if ($role === 'admin' || $role === 'doctor'): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">➕ Ajouter une prescription</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="col-md-6">
                                <label class="form-label">Patient :</label>
                                <select class="form-control" name="patient_id" required>
                                    <option value="">Sélectionner un patient</option>
                                    <?php foreach ($patients_list as $patient): ?>
                                        <option value="<?php echo (int)$patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Médecin :</label>
                                <?php if ($role === 'admin'): ?>
                                    <select class="form-control" name="doctor_id" required>
                                        <option value="">Sélectionner un médecin</option>
                                        <?php foreach ($doctors_list as $doctor): ?>
                                            <option value="<?php echo (int)$doctor['id']; ?>">
                                                <?php echo htmlspecialchars($doctor['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: // doctor ?>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($doctors_list[0]['full_name'] ?? ''); ?>" disabled>
                                    <input type="hidden" name="doctor_id" value="<?php echo (int)($doctors_list[0]['id'] ?? 0); ?>">
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Médicament :</label>
                                <input type="text" class="form-control" name="medication_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Posologie :</label>
                                <input type="text" class="form-control" name="dosage" placeholder="ex: 500mg deux fois par jour" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Durée :</label>
                                <input type="text" class="form-control" name="duration" placeholder="ex: 7 jours">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes :</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_prescription" class="btn btn-primary">Ajouter la prescription</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tableau des prescriptions -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Toutes les prescriptions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th><th>Médecin</th><th>Médicament</th><th>Posologie</th>
                                        <th>Durée</th><th>Notes</th><th>Date</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prescriptions as $rx): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rx['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($rx['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($rx['medication_name']); ?></td>
                                            <td><?php echo htmlspecialchars($rx['dosage']); ?></td>
                                            <td><?php echo htmlspecialchars($rx['duration']); ?></td>
                                            <td><small><?php echo htmlspecialchars(substr($rx['notes'], 0, 30)); ?></small></td>
                                            <td><?php echo htmlspecialchars(substr($rx['created_at'], 0, 10)); ?></td>
                                            <td>
                                                <!-- Suppression via formulaire POST (CSRF protégé) -->
                                                <?php if ($role === 'admin'): ?>
                                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Supprimer cette prescription ?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                    <input type="hidden" name="prescription_id" value="<?php echo (int)$rx['id']; ?>">
                                                    <button type="submit" name="delete_prescription" class="btn btn-sm btn-danger">Supprimer</button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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