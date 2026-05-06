<?php
session_start();
require_once 'config/database.php'; // Ce fichier doit maintenant contenir une connexion PDO

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$success = '';
$error = '';

// Génération d'un token CSRF pour les formulaires
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- AJOUT D'UN PATIENT (POST avec token CSRF) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Erreur de validation CSRF. Veuillez réessayer.";
    } else {
        // Nettoyage et validation des entrées
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $age = filter_var($_POST['age'] ?? 0, FILTER_VALIDATE_INT);
        $gender = $_POST['gender'] ?? '';
        $medical_history = trim($_POST['medical_history'] ?? '');

        // Validations basiques
        if (empty($full_name) || empty($username) || empty($password) || empty($email)) {
            $error = "Veuillez remplir tous les champs obligatoires.";
        } elseif ($age === false || $age < 0 || $age > 120) {
            $error = "Âge invalide (0-120).";
        } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
            $error = "Genre invalide.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Adresse email invalide.";
        } else {
            try {
                // Hachage du mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insertion dans la table users (requête préparée)
                $insertUser = $pdo->prepare("
                    INSERT INTO users (username, password, email, full_name, role, phone)
                    VALUES (:username, :password, :email, :full_name, 'patient', :phone)
                ");
                $insertUser->execute([
                    ':username' => $username,
                    ':password' => $hashed_password,
                    ':email' => $email,
                    ':full_name' => $full_name,
                    ':phone' => $phone
                ]);
                $patient_user_id = $pdo->lastInsertId();

                // Insertion dans la table patients
                $insertPatient = $pdo->prepare("
                    INSERT INTO patients (user_id, age, gender, medical_history, phone)
                    VALUES (:user_id, :age, :gender, :medical_history, :phone)
                ");
                $insertPatient->execute([
                    ':user_id' => $patient_user_id,
                    ':age' => $age,
                    ':gender' => $gender,
                    ':medical_history' => $medical_history,
                    ':phone' => $phone
                ]);

                $success = "Patient ajouté avec succès !";
            } catch (PDOException $e) {
                $error = "Erreur lors de l'ajout : " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// --- SUPPRESSION D'UN PATIENT (maintenant en POST avec token) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_patient'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Erreur de validation CSRF.";
    } else {
        $patient_id = filter_var($_POST['patient_id'] ?? 0, FILTER_VALIDATE_INT);
        if ($patient_id <= 0) {
            $error = "ID patient invalide.";
        } else {
            // Vérifier les droits d'accès (admin ou médecin propriétaire ?)
            // Pour simplifier, seuls les admins peuvent supprimer. Sinon, vérifier le lien médecin-patient.
            if ($role !== 'admin') {
                $error = "Vous n'avez pas les droits nécessaires pour supprimer un patient.";
            } else {
                try {
                    // Récupérer l'user_id lié au patient
                    $stmt = $pdo->prepare("SELECT user_id FROM patients WHERE id = :id");
                    $stmt->execute([':id' => $patient_id]);
                    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($patient) {
                        // Supprimer d'abord le patient
                        $deletePatient = $pdo->prepare("DELETE FROM patients WHERE id = :id");
                        $deletePatient->execute([':id' => $patient_id]);
                        // Puis supprimer l'utilisateur associé
                        $deleteUser = $pdo->prepare("DELETE FROM users WHERE id = :user_id");
                        $deleteUser->execute([':user_id' => $patient['user_id']]);
                        $success = "Patient supprimé avec succès.";
                    } else {
                        $error = "Patient introuvable.";
                    }
                } catch (PDOException $e) {
                    $error = "Erreur lors de la suppression : " . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}

// --- LISTE DES PATIENTS (avec restriction selon le rôle) ---
try {
    if ($role === 'admin') {
        $patientsQuery = $pdo->prepare("
            SELECT p.id, p.age, p.gender, p.phone, p.medical_history, u.full_name, u.username
            FROM patients p
            JOIN users u ON p.user_id = u.id
            ORDER BY u.full_name
        ");
        $patientsQuery->execute();
    } else {
        // hypothèse : les médecins voient leurs propres patients. Ajouter une colonne doctor_id si nécessaire.
        // Ici, pour l'exemple, on affiche tous (mais mieux vaut limiter).
        // Adaptez selon votre schéma réel.
        $patientsQuery = $pdo->prepare("
            SELECT p.id, p.age, p.gender, p.phone, p.medical_history, u.full_name, u.username
            FROM patients p
            JOIN users u ON p.user_id = u.id
            ORDER BY u.full_name
        ");
        $patientsQuery->execute();
    }
    $patients = $patientsQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors du chargement des patients : " . htmlspecialchars($e->getMessage());
    $patients = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - Clinic Management System </title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f6f7; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: bold; }
        .sidebar { background: white; min-height: 100vh; padding: 20px 0; box-shadow: 2px 0 5px rgba(0,0,0,0.05); }
        .sidebar a { display: block; padding: 12px 20px; color: #333; text-decoration: none; border-left: 4px solid transparent; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background-color: #f0f0f0; border-left-color: #667eea; color: #667eea; }
        .main-content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn-primary { background-color: #667eea; border-color: #667eea; }
        .btn-primary:hover { background-color: #764ba2; border-color: #764ba2; }
        .btn-danger { background-color: #dc3545; }
        .btn-edit { background-color: #28a745; color: white; }
        .btn-edit:hover { background-color: #218838; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">🏥 Clinic Management System (Sécurisé)</a>
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
                <a href="patients.php" class="active">👨‍⚕️ Patients</a>
                <a href="appointments.php">📅 Rendez-vous</a>
                <a href="prescriptions.php">💊 Prescriptions</a>
                <?php if ($role === 'admin'): ?>
                    <a href="users.php">👥 Utilisateurs</a>
                <?php endif; ?>
            </div>

            <div class="col-md-10 main-content">
                <h2 style="margin-bottom: 30px;">👨‍⚕️ Gestion des patients</h2>

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

                <!-- Formulaire d'ajout avec token CSRF -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">➕ Ajouter un patient</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <div class="col-md-6">
                                <label class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nom d'utilisateur *</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mot de passe *</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Âge</label>
                                <input type="number" class="form-control" name="age" min="0" max="120">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Genre</label>
                                <select class="form-control" name="gender">
                                    <option value="Male">Homme</option>
                                    <option value="Female">Femme</option>
                                    <option value="Other">Autre</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Antécédents médicaux</label>
                                <textarea class="form-control" name="medical_history" rows="4"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_patient" class="btn btn-primary">Ajouter le patient</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des patients avec suppression sécurisée (formulaire POST) -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Tous les patients</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th><th>Username</th><th>Âge</th><th>Genre</th><th>Téléphone</th><th>Antécédents</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $patient): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($patient['username']); ?></td>
                                            <td><?php echo intval($patient['age']); ?></td>
                                            <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                            <td>
                                                <small>
                                                    <?php 
                                                    $history = htmlspecialchars($patient['medical_history']);
                                                    echo strlen($history) > 50 ? substr($history, 0, 50) . '...' : $history;
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <a href="patient_detail.php?id=<?php echo intval($patient['id']); ?>" class="btn btn-sm btn-edit">Voir</a>
                                                <!-- Formulaire de suppression sécurisé (POST) -->
                                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Supprimer définitivement ce patient ?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                    <input type="hidden" name="patient_id" value="<?php echo intval($patient['id']); ?>">
                                                    <button type="submit" name="delete_patient" class="btn btn-sm btn-danger">Supprimer</button>
                                                </form>
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