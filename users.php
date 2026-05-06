<?php
session_start();
require_once 'config/database.php'; // doit fournir $pdo (PDO)

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Vérification du rôle administrateur
if ($_SESSION['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    die("Accès refusé : vous n'êtes pas administrateur.");
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

// --- SUPPRESSION D'UN UTILISATEUR (POST avec token) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Erreur de validation CSRF.";
    } else {
        $delete_id = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$delete_id) {
            $error = "ID utilisateur invalide.";
        } elseif ($delete_id == $_SESSION['user_id']) {
            $error = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            try {
                // Vérifier que l'utilisateur existe et n'est pas admin (optionnel)
                $stmtCheck = $pdo->prepare("SELECT role FROM users WHERE id = :id");
                $stmtCheck->execute([':id' => $delete_id]);
                $target = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                if (!$target) {
                    $error = "Utilisateur introuvable.";
                } else {
                    // Suppression via requête préparée
                    $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = :id");
                    $stmtDel->execute([':id' => $delete_id]);
                    $success = "Utilisateur supprimé avec succès.";
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = "Erreur lors de la suppression.";
            }
        }
    }
}

// --- RÉCUPÉRATION DES UTILISATEURS (sans le mot de passe) ---
$users = [];
try {
    $stmtUsers = $pdo->query("
        SELECT id, username, full_name, email, role, phone, created_at
        FROM users
        ORDER BY created_at DESC
    ");
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Erreur lors du chargement des utilisateurs.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - Clinic Management System (Sécurisé)</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f6f7; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .sidebar { background: white; min-height: 100vh; padding: 20px 0; box-shadow: 2px 0 5px rgba(0,0,0,0.05); }
        .sidebar a { display: block; padding: 12px 20px; color: #333; text-decoration: none; border-left: 4px solid transparent; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background-color: #f0f0f0; border-left-color: #667eea; color: #667eea; }
        .main-content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .role-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .role-admin { background-color: #f8d7da; color: #721c24; }
        .role-doctor { background-color: #cfe2ff; color: #084298; }
        .role-patient { background-color: #d1e7dd; color: #0f5132; }
        .alert { border-radius: 8px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">🏥 Clinic Management System (Sécurisé)</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><span class="nav-link" style="color:white;">Bienvenue, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span></li>
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
                <a href="prescriptions.php">💊 Prescriptions</a>
                <a href="users.php" class="active">👥 Utilisateurs</a>
            </div>

            <div class="col-md-10 main-content">
                <h2 style="margin-bottom: 30px;">👥 Gestion des utilisateurs</h2>

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

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Tous les utilisateurs du système</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th><th>Nom d'utilisateur</th><th>Nom complet</th>
                                        <th>Email</th><th>Rôle</th><th>Téléphone</th><th>Date d'inscription</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo (int)$user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
                                                    <?php echo strtoupper(htmlspecialchars($user['role'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($user['created_at'], 0, 10)); ?></td>
                                            <td>
                                                <!-- Suppression via formulaire POST (CSRF protégé) -->
                                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger">Supprimer</button>
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