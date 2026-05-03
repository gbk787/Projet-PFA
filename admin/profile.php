<?php
session_start();
$conn = new mysqli("localhost", "root", "", "locationvoitures");

// Protection admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$success = '';
$erreur = '';

// Récupérer les infos admin
$stmt = $conn->prepare("SELECT idadmin, nom, email, motdepasse FROM admin WHERE idadmin = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($id, $nom, $email, $motdepasse);
$stmt->fetch();
$stmt->close();

// Mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Changer nom / email
    if (isset($_POST['update_info'])) {
        $new_nom = trim($_POST['nom']);
        $new_email = trim($_POST['email']);

        $stmt = $conn->prepare("UPDATE admin SET nom = ?, email = ? WHERE idadmin = ?");
        $stmt->bind_param("ssi", $new_nom, $new_email, $admin_id);
        if ($stmt->execute()) {
            $_SESSION['admin_nom'] = $new_nom;
            $_SESSION['admin_email'] = $new_email;
            $nom = $new_nom;
            $email = $new_email;
            $success = "Profile updated successfully!";
        } else {
            $erreur = "Error updating profile.";
        }
        $stmt->close();
    }

    // Changer mot de passe
    if (isset($_POST['update_password'])) {
        $current = $_POST['current_password'];
        $new_pwd = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if ($current !== $motdepasse) {
            $erreur = "Current password is incorrect.";
        } elseif ($new_pwd !== $confirm) {
            $erreur = "New passwords do not match.";
        } elseif (strlen($new_pwd) < 4) {
            $erreur = "Password must be at least 4 characters.";
        } else {
            $stmt = $conn->prepare("UPDATE admin SET motdepasse = ? WHERE idadmin = ?");
            $stmt->bind_param("si", $new_pwd, $admin_id);
            if ($stmt->execute()) {
                $success = "Password changed successfully!";
                $motdepasse = $new_pwd;
            } else {
                $erreur = "Error changing password.";
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Profil Admin — TinySteps</title>
    <link rel="stylesheet" href="styles/sidebar.css" />
    <link rel="stylesheet" href="styles/profil.css" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

</head>

<body>

    <div class="admin-layout">

        <!-- SIDEBAR (identique au dashboard) -->
        <aside class="sidebar">
            <?php include 'sidebar.php'; ?>
        </aside>

        <!-- MAIN -->
        <main class="admin-main">

            <!-- Header -->
            <div class="admin-header">
                <h1>Mon Profil</h1>
                <div class="admin-profile">
                    <a href="profile.php" class="profile-btn">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($_SESSION['admin_nom'], 0, 1)); ?>
                        </div>
                        <i class="fa-solid fa-chevron-down" style="font-size:0.75rem; color:#888;"></i>
                    </a>
                </div>
            </div>

            <div class="profile-page">

                <!-- Alertes -->
                <?php if ($erreur): ?>
                    <div class="alert alert-error">
                        <i class="fa fa-circle-exclamation"></i>
                        <?= htmlspecialchars($erreur) ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fa fa-circle-check"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <!-- Bannière profil -->
                <div class="profile-banner">
                    <div class="avatar">
                        <?= strtoupper(substr($nom, 0, 1)) ?>
                    </div>
                    <div>
                        <h2>
                            <?= htmlspecialchars($nom) ?>
                        </h2>
                        <p>
                            <?= htmlspecialchars($email) ?>
                        </p>
                        <span class="info-badge" style="margin-top:8px; display:inline-flex;">
                            <i class="fa fa-shield-halved"></i> Administrateur
                        </span>
                    </div>
                </div>

                <!-- Formulaire infos -->
                <div class="profile-card">
                    <h3><i class="fa fa-user"></i> Informations Personnelles</h3>
                    <form method="POST" action="profile.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fa fa-user"></i> Nom Complet</label>
                                <div class="input-wrap">
                                    <i class="fa fa-user input-icon"></i>
                                    <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" required />
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fa fa-envelope"></i> Adresse e-mail</label>
                                <div class="input-wrap">
                                    <i class="fa fa-envelope input-icon"></i>
                                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required />
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_info" class="btn-save">
                            <i class="fa fa-floppy-disk"></i> Enregistrer les modifications
                        </button>
                    </form>
                </div>

                <!-- Formulaire mot de passe -->
                <div class="profile-card">
                    <h3><i class="fa fa-lock"></i> Changer le mot de passe</h3>
                    <form method="POST" action="profile.php">
                        <div class="form-group">
                            <label><i class="fa fa-lock"></i> Mot de passe actuel</label>
                            <div class="input-wrap">
                                <i class="fa fa-lock input-icon"></i>
                                <input type="password" name="current_password" id="pwd1" placeholder="••••••••"
                                    required />
                                <button type="button" class="toggle-pwd" onclick="toggle('pwd1','eye1')">
                                    <i class="fa fa-eye" id="eye1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fa fa-key"></i> Nouveau mot de passe</label>
                                <div class="input-wrap">
                                    <i class="fa fa-key input-icon"></i>
                                    <input type="password" name="new_password" id="pwd2" placeholder="••••••••"
                                        required />
                                    <button type="button" class="toggle-pwd" onclick="toggle('pwd2','eye2')">
                                        <i class="fa fa-eye" id="eye2"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fa fa-key"></i> Confirmer le mot de passe</label>
                                <div class="input-wrap">
                                    <i class="fa fa-key input-icon"></i>
                                    <input type="password" name="confirm_password" id="pwd3" placeholder="••••••••"
                                        required />
                                    <button type="button" class="toggle-pwd" onclick="toggle('pwd3','eye3')">
                                        <i class="fa fa-eye" id="eye3"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_password" class="btn-save">
                            <i class="fa fa-lock"></i> Modifier le mot de passe
                        </button>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <script>
        function toggle(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>

</body>

</html>