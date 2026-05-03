<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$erreur = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = isset($_POST['email'])    ? trim($_POST['email'])  : '';
    $password = isset($_POST['password']) ? $_POST['password']     : '';
    $role     = isset($_POST['role'])     ? $_POST['role']         : 'parent';

    require_once __DIR__ . '/connexion_bd.php';

    $connecte = false;

    // ══════════════════════════════════════════════════════
    //  CAS 1 : Parent
    // ══════════════════════════════════════════════════════
    if ($role === 'parent') {

        $stmt = $conn->prepare("SELECT id_parent, nom_prenom, mot_de_passe FROM parent WHERE email = ?");

        if (!$stmt) {
            $erreur = "Erreur système (parent) : " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $nom_prenom, $mdp_bd); 
                $stmt->fetch();

                $ok = false;
                if (strlen($mdp_bd) === 60 || substr($mdp_bd, 0, 2) === '$2') {
                    $ok = password_verify($password, $mdp_bd);
                } elseif (strlen($mdp_bd) === 32) {
                    $ok = (md5($password) === $mdp_bd);
                } else {
                    $ok = ($password === $mdp_bd);
                }

                if ($ok) {
                    $_SESSION['user_id']   = $id;
                    $_SESSION['user_nom']  = $nom_prenom; 
                    $_SESSION['user_role'] = 'parent';    
                    $connecte = true;
                    $stmt->close();                       
                    $conn->close();
                    header("Location: accueil_parent.php"); 
                    exit;
                }
            }
            $stmt->close(); 
            if (!$connecte) {
                $erreur = "Email ou mot de passe incorrect. Veuillez réessayer.";
            }
        }

    // ══════════════════════════════════════════════════════
    //  CAS 2 : Médecin
    // ══════════════════════════════════════════════════════
    } elseif ($role === 'medecin') {

        // Détecter automatiquement le vrai nom de la table
        $table_medecin = 'medecins';
        $test = $conn->query("SHOW TABLES LIKE 'medecins'");
        if (!$test || $test->num_rows === 0) {
            $test2 = $conn->query("SHOW TABLES LIKE 'medecin'");
            if ($test2 && $test2->num_rows > 0) {
                $table_medecin = 'medecin';
            }
        }

        // Détecter automatiquement la colonne mot de passe
        $col_mdp = 'mdp';
        $cols_res = $conn->query("SHOW COLUMNS FROM `$table_medecin` LIKE 'mdp'");
        if (!$cols_res || $cols_res->num_rows === 0) {
            $cols_res2 = $conn->query("SHOW COLUMNS FROM `$table_medecin` LIKE 'mot_de_passe'");
            if ($cols_res2 && $cols_res2->num_rows > 0) {
                $col_mdp = 'mot_de_passe';
            } else {
                $cols_res3 = $conn->query("SHOW COLUMNS FROM `$table_medecin` LIKE 'password'");
                if ($cols_res3 && $cols_res3->num_rows > 0) {
                    $col_mdp = 'password';
                }
            }
        }

        $sql   = "SELECT id, nom, prenom, `$col_mdp` AS mdp_col FROM `$table_medecin` WHERE email = ?";
        $stmt2 = $conn->prepare($sql);

        if (!$stmt2) {
            $erreur = "Erreur système (médecin) : " . $conn->error
                . " | Table : $table_medecin | Colonne mdp : $col_mdp";
        } else {
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $stmt2->store_result();

            if ($stmt2->num_rows === 1) {
                $stmt2->bind_result($id, $nom, $prenom, $mdp_bd);
                $stmt2->fetch();

                $ok = false;
                if (strlen($mdp_bd) === 60 || substr($mdp_bd, 0, 2) === '$2') {
                    $ok = password_verify($password, $mdp_bd);
                } elseif (strlen($mdp_bd) === 32) {
                    $ok = (md5($password) === $mdp_bd);
                } else {
                    $ok = ($password === $mdp_bd);
                }

                if ($ok) {
                    $_SESSION['user_id']     = $id;
                    $_SESSION['pediatre_id'] = $id;
                    $_SESSION['user_nom']    = $nom . ' ' . $prenom;
                    $_SESSION['user_role']   = 'medecin';
                    $connecte = true;
                    $stmt2->close();
                    $conn->close();
                    header("Location: dashboard_pediatre.php");
                    exit;
                }
            }
            $stmt2->close();

            if (!$connecte) {
                $erreur = "Email ou mot de passe incorrect. Veuillez réessayer.";
            }
        }
    }

    $conn->close();
}
?>
<?php if (!empty($erreur)): ?>
    <style>
        .erreur-connexion {
            background: #ffe0e0;
            color: #c0392b;
            border: 1px solid #e74c3c;
            border-radius: 8px;
            padding: 10px 16px;
            margin: 10px 0 6px 0;
            font-size: 14px;
            text-align: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0);    }
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var form = document.querySelector(".login-form");
            if (form) {
                var div = document.createElement("div");
                div.className = "erreur-connexion";
                div.textContent = <?php echo json_encode($erreur); ?>;
                var submitBtn = form.querySelector('input[type="submit"]');
                if (submitBtn) {
                    form.insertBefore(div, submitBtn);
                } else {
                    form.appendChild(div);
                }
            }
        });
    </script>
<?php endif; ?>