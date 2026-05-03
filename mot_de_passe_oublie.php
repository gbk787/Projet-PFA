<?php
// mot_de_passe_oublie.php
// Étape 1 : l'utilisateur saisit son email et son rôle

$role_url = (isset($_GET['role']) && $_GET['role'] === 'medecin') ? 'medecin' : 'parent';
$message  = '';
$erreur   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role  = isset($_POST['role'])  ? $_POST['role']        : 'parent';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Veuillez saisir une adresse email valide.";
    } else {
        require_once __DIR__ . '/connexion_bd.php';

        // Vérifier que l'email existe dans la bonne table
        if ($role === 'parent') {
            $stmt = $conn->prepare("SELECT id_parent FROM parent WHERE email = ?");
        } else {
            $stmt = $conn->prepare("SELECT id FROM medecins WHERE email = ?");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $existe = ($stmt->num_rows === 1);
        $stmt->close();

        if ($existe) {
            // Supprimer les anciens tokens inutilisés pour cet email/rôle
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND role = ?");
            $del->bind_param("ss", $email, $role);
            $del->execute();
            $del->close();

            // Générer un token sécurisé
            $token     = bin2hex(random_bytes(32));           // 64 chars hex
            $expire_at = date('Y-m-d H:i:s', time() + 3600); // valide 1 heure

            $ins = $conn->prepare(
                "INSERT INTO password_resets (email, role, token, expire_at) VALUES (?, ?, ?, ?)"
            );
            $ins->bind_param("ssss", $email, $role, $token, $expire_at);
            $ins->execute();
            $ins->close();

            // Construire le lien de réinitialisation
            $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host      = $_SERVER['HTTP_HOST'];
            $dir       = dirname($_SERVER['REQUEST_URI']);
            $reset_url = $protocol . '://' . $host . rtrim($dir, '/') . '/reset_password.php?token=' . $token;

            // ----------------------------------------------------------------
            // Envoi de l'email (PHP mail natif — remplacez par PHPMailer/SMTP
            // en production pour une meilleure délivrabilité)
            // ----------------------------------------------------------------
            $sujet = "TinySteps – Réinitialisation de votre mot de passe";
            $corps = "Bonjour,\n\n"
                   . "Vous avez demandé la réinitialisation de votre mot de passe TinySteps.\n\n"
                   . "Cliquez sur le lien ci-dessous (valable 1 heure) :\n"
                   . $reset_url . "\n\n"
                   . "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.\n\n"
                   . "L'équipe TinySteps";
            $entetes = "From: noreply@tinysteps.tn\r\nContent-Type: text/plain; charset=UTF-8";

            mail($email, $sujet, $corps, $entetes);

            // On affiche le même message qu'il existe ou non (sécurité anti-énumération)
            $message = "Si cet email est associé à un compte, un lien de réinitialisation vous a été envoyé. Vérifiez votre boîte mail.";
        } else {
            // Message générique pour ne pas révéler l'existence du compte
            $message = "Si cet email est associé à un compte, un lien de réinitialisation vous a été envoyé. Vérifiez votre boîte mail.";
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mot de passe oublié – TinySteps</title>
  <link rel="icon" href="images/logo1.png" />
  <link rel="stylesheet" href="styles/style_connexion.css" />
  <link rel="stylesheet" href="styles/style.css" />
  <style>
    /* ── Styles spécifiques à cette page ── */
    .forgot-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .forgot-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 40px rgba(0,0,0,.10);
      padding: 44px 40px 36px;
      width: 100%;
      max-width: 420px;
      animation: slideUp .4s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0);    }
    }

    .forgot-card .header {
      text-align: center;
      margin-bottom: 28px;
    }

    .forgot-card .header img {
      width: 62px;
      margin-bottom: 12px;
    }

    .forgot-card .header h1 {
      font-size: 1.4rem;
      font-weight: 700;
      color: #2d3a4a;
      margin: 0 0 6px;
    }

    .forgot-card .header p {
      font-size: .87rem;
      color: #7a8a9a;
      margin: 0;
      line-height: 1.5;
    }

    /* Role switcher — même style que connexion.php */
    .role-switcher {
      display: flex;
      background: #f0f4f8;
      border-radius: 10px;
      padding: 4px;
      margin-bottom: 22px;
      gap: 4px;
    }

    .role-btn {
      flex: 1;
      padding: 9px;
      border: none;
      border-radius: 8px;
      background: transparent;
      font-size: .88rem;
      font-weight: 600;
      color: #7a8a9a;
      cursor: pointer;
      transition: all .2s;
    }

    .role-btn.active {
      background: #fff;
      color: #3a86ff;
      box-shadow: 0 2px 8px rgba(0,0,0,.10);
    }

    .forgot-card label {
      display: block;
      font-size: .83rem;
      font-weight: 600;
      color: #4a5568;
      margin-bottom: 6px;
    }

    .forgot-card input[type="email"] {
      width: 100%;
      padding: 13px 16px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: .93rem;
      color: #2d3a4a;
      background: #f9fafb;
      box-sizing: border-box;
      transition: border-color .2s, box-shadow .2s;
      outline: none;
      margin-bottom: 18px;
    }

    .forgot-card input[type="email"]:focus {
      border-color: #3a86ff;
      box-shadow: 0 0 0 3px rgba(58,134,255,.12);
      background: #fff;
    }

    .btn-submit {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #3a86ff, #5b9fff);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: .97rem;
      font-weight: 700;
      cursor: pointer;
      transition: opacity .2s, transform .15s;
      letter-spacing: .3px;
    }

    .btn-submit:hover  { opacity: .9; transform: translateY(-1px); }
    .btn-submit:active { opacity: 1;  transform: translateY(0);    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 18px;
      font-size: .85rem;
      color: #7a8a9a;
      text-decoration: none;
      transition: color .2s;
    }

    .back-link:hover { color: #3a86ff; }

    /* Messages */
    .msg-success,
    .msg-error {
      border-radius: 10px;
      padding: 12px 16px;
      font-size: .87rem;
      margin-bottom: 18px;
      text-align: center;
      animation: fadeIn .3s ease;
    }

    .msg-success {
      background: #e6f9ee;
      color: #1a7a45;
      border: 1px solid #6fcf97;
    }

    .msg-error {
      background: #ffe0e0;
      color: #c0392b;
      border: 1px solid #e74c3c;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-6px); }
      to   { opacity: 1; transform: translateY(0);    }
    }

    /* Icône enveloppe animée quand succès */
    .success-icon {
      font-size: 3rem;
      margin-bottom: 10px;
      display: block;
    }
  </style>
</head>
<body>

<div class="forgot-wrapper">
  <div class="forgot-card">

    <?php if (!empty($message)): ?>
      <!-- État succès : message envoyé -->
      <div class="header">
        <span class="success-icon">📧</span>
        <h1>Email envoyé !</h1>
        <p><?php echo htmlspecialchars($message); ?></p>
      </div>
      <a class="btn-submit" href="connexion.php" style="display:block;text-align:center;text-decoration:none;margin-top:10px;">
        Retour à la connexion
      </a>

    <?php else: ?>
      <!-- Formulaire de demande -->
      <div class="header">
        <a href="home.php"><img src="images/logo1.png" alt="TinySteps Logo" /></a>
        <h1>Mot de passe oublié ?</h1>
        <p>Saisissez votre email et nous vous enverrons un lien de réinitialisation.</p>
      </div>

      <?php if (!empty($erreur)): ?>
        <div class="msg-error"><?php echo htmlspecialchars($erreur); ?></div>
      <?php endif; ?>

      <form method="POST" action="mot_de_passe_oublie.php">
        <input type="hidden" name="role" id="role_input" value="<?php echo $role_url; ?>" />

        <div class="role-switcher">
          <button type="button"
            class="role-btn <?php echo $role_url === 'parent'  ? 'active' : ''; ?>"
            onclick="setRole(this)">Parent</button>
          <button type="button"
            class="role-btn <?php echo $role_url === 'medecin' ? 'active' : ''; ?>"
            onclick="setRole(this)">Pédiatre</button>
        </div>

        <label for="email">Adresse email</label>
        <input type="email" name="email" id="email"
               placeholder="votre@email.com" required
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />

        <button type="submit" class="btn-submit">Envoyer le lien de réinitialisation</button>
      </form>

      <a class="back-link" href="connexion.php">← Retour à la connexion</a>

    <?php endif; ?>

  </div>
</div>

<script>
  function setRole(btn) {
    document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('role_input').value =
      btn.textContent.includes('Pédiatre') ? 'medecin' : 'parent';
  }
</script>

</body>
</html>