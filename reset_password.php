<?php
// reset_password.php
// Étape 2 : l'utilisateur clique sur le lien reçu par email et choisit un nouveau mot de passe

session_start();

$token   = isset($_GET['token'])  ? trim($_GET['token'])  : '';
$erreur  = '';
$message = '';
$token_valide = false;
$role    = '';
$email   = '';

// ── Valider le token ────────────────────────────────────────────────────────
if (!empty($token)) {
    require_once __DIR__ . '/connexion_bd.php';

    $stmt = $conn->prepare(
        "SELECT email, role FROM password_resets
         WHERE token = ? AND used = 0 AND expire_at > NOW()
         LIMIT 1"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($email, $role);
        $stmt->fetch();
        $token_valide = true;
    } else {
        $erreur = "Ce lien est invalide ou a expiré. Veuillez faire une nouvelle demande.";
    }
    $stmt->close();
}

// ── Traitement du formulaire (nouveau mot de passe) ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valide) {
    $nouveau_mdp   = isset($_POST['nouveau_mdp'])   ? $_POST['nouveau_mdp']   : '';
    $confirmer_mdp = isset($_POST['confirmer_mdp']) ? $_POST['confirmer_mdp'] : '';

    if (strlen($nouveau_mdp) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($nouveau_mdp !== $confirmer_mdp) {
        $erreur = "Les deux mots de passe ne correspondent pas.";
    } else {
        $hash = password_hash($nouveau_mdp, PASSWORD_BCRYPT);

        if ($role === 'parent') {
            $upd = $conn->prepare("UPDATE parent SET mot_de_passe = ? WHERE email = ?");
        } else {
            $upd = $conn->prepare("UPDATE medecins SET mdp = ? WHERE email = ?");
        }

        $upd->bind_param("ss", $hash, $email);
        $upd->execute();
        $upd->close();

        // Marquer le token comme utilisé
        $del = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $del->bind_param("s", $token);
        $del->execute();
        $del->close();

        $message = "Votre mot de passe a été réinitialisé avec succès !";
        $token_valide = false; // masquer le formulaire
    }

    if (isset($conn)) $conn->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Réinitialiser le mot de passe – TinySteps</title>
  <link rel="icon" href="images/logo1.png" />
  <link rel="stylesheet" href="styles/style_connexion.css" />
  <link rel="stylesheet" href="styles/style.css" />
  <style>
    .reset-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .reset-card {
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

    .reset-card .header {
      text-align: center;
      margin-bottom: 28px;
    }

    .reset-card .header img {
      width: 62px;
      margin-bottom: 12px;
    }

    .reset-card .header h1 {
      font-size: 1.4rem;
      font-weight: 700;
      color: #2d3a4a;
      margin: 0 0 6px;
    }

    .reset-card .header p {
      font-size: .87rem;
      color: #7a8a9a;
      margin: 0;
      line-height: 1.5;
    }

    .reset-card label {
      display: block;
      font-size: .83rem;
      font-weight: 600;
      color: #4a5568;
      margin-bottom: 6px;
      margin-top: 14px;
    }

    .input-group {
      position: relative;
      margin-bottom: 0;
    }

    .input-group input[type="password"],
    .input-group input[type="text"] {
      width: 100%;
      padding: 13px 46px 13px 16px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: .93rem;
      color: #2d3a4a;
      background: #f9fafb;
      box-sizing: border-box;
      transition: border-color .2s, box-shadow .2s;
      outline: none;
    }

    .input-group input:focus {
      border-color: #3a86ff;
      box-shadow: 0 0 0 3px rgba(58,134,255,.12);
      background: #fff;
    }

    .toggle-eye {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #a0aec0;
      padding: 0;
      font-size: 1.05rem;
      line-height: 1;
      transition: color .2s;
    }

    .toggle-eye:hover { color: #3a86ff; }

    /* Jauge de force */
    .strength-bar-wrap {
      height: 4px;
      background: #e2e8f0;
      border-radius: 4px;
      margin: 8px 0 4px;
      overflow: hidden;
    }

    .strength-bar {
      height: 100%;
      width: 0%;
      border-radius: 4px;
      transition: width .3s, background .3s;
    }

    .strength-label {
      font-size: .76rem;
      color: #a0aec0;
      margin-bottom: 14px;
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
      margin-top: 22px;
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

    .success-icon {
      font-size: 3rem;
      margin-bottom: 10px;
      display: block;
    }

    /* Condition list */
    .conditions {
      list-style: none;
      padding: 0;
      margin: 8px 0 0;
      font-size: .80rem;
    }

    .conditions li {
      color: #a0aec0;
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 3px;
      transition: color .2s;
    }

    .conditions li.ok { color: #27ae60; }

    .conditions li::before {
      content: '○';
      font-size: .7rem;
    }

    .conditions li.ok::before { content: '✓'; }
  </style>
</head>
<body>

<div class="reset-wrapper">
  <div class="reset-card">

    <?php if (!empty($message)): ?>
      <!-- Succès -->
      <div class="header">
        <span class="success-icon">🎉</span>
        <h1>Mot de passe modifié !</h1>
        <p><?php echo htmlspecialchars($message); ?></p>
      </div>
      <a class="btn-submit"
         href="connexion.php<?php echo ($role === 'medecin') ? '?role=medecin' : ''; ?>"
         style="display:block;text-align:center;text-decoration:none;">
        Se connecter
      </a>

    <?php elseif (!$token_valide): ?>
      <!-- Token invalide / expiré -->
      <div class="header">
        <a href="home.php"><img src="images/logo1.png" alt="TinySteps Logo" /></a>
        <h1>Lien invalide</h1>
        <p>Ce lien de réinitialisation est invalide ou a expiré.</p>
      </div>
      <?php if (!empty($erreur)): ?>
        <div class="msg-error"><?php echo htmlspecialchars($erreur); ?></div>
      <?php endif; ?>
      <a class="btn-submit" href="mot_de_passe_oublie.php"
         style="display:block;text-align:center;text-decoration:none;">
        Nouvelle demande
      </a>

    <?php else: ?>
      <!-- Formulaire nouveau mot de passe -->
      <div class="header">
        <a href="home.php"><img src="images/logo1.png" alt="TinySteps Logo" /></a>
        <h1>Nouveau mot de passe</h1>
        <p>Choisissez un mot de passe sécurisé pour votre compte.</p>
      </div>

      <?php if (!empty($erreur)): ?>
        <div class="msg-error"><?php echo htmlspecialchars($erreur); ?></div>
      <?php endif; ?>

      <form method="POST" action="reset_password.php?token=<?php echo urlencode($token); ?>"
            id="resetForm">

        <label for="nouveau_mdp">Nouveau mot de passe</label>
        <div class="input-group">
          <input type="password" name="nouveau_mdp" id="nouveau_mdp"
                 placeholder="Minimum 8 caractères" required autocomplete="new-password"
                 oninput="checkStrength(this.value)" />
          <button type="button" class="toggle-eye" onclick="toggleVis('nouveau_mdp', this)">👁</button>
        </div>
        <div class="strength-bar-wrap"><div class="strength-bar" id="sbar"></div></div>
        <div class="strength-label" id="slabel">Saisissez un mot de passe</div>

        <ul class="conditions" id="conditions">
          <li id="c-len">Au moins 8 caractères</li>
          <li id="c-upp">Une lettre majuscule</li>
          <li id="c-num">Un chiffre</li>
        </ul>

        <label for="confirmer_mdp">Confirmer le mot de passe</label>
        <div class="input-group">
          <input type="password" name="confirmer_mdp" id="confirmer_mdp"
                 placeholder="Répétez le mot de passe" required autocomplete="new-password" />
          <button type="button" class="toggle-eye" onclick="toggleVis('confirmer_mdp', this)">👁</button>
        </div>

        <button type="submit" class="btn-submit">Réinitialiser le mot de passe</button>
      </form>

      <a class="back-link" href="connexion.php">← Retour à la connexion</a>

    <?php endif; ?>

  </div>
</div>

<script>
  // ── Afficher/masquer le mot de passe ─────────────────────────────────────
  function toggleVis(id, btn) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') {
      inp.type = 'text';
      btn.textContent = '🙈';
    } else {
      inp.type = 'password';
      btn.textContent = '👁';
    }
  }

  // ── Jauge de force ───────────────────────────────────────────────────────
  function checkStrength(val) {
    const bar    = document.getElementById('sbar');
    const label  = document.getElementById('slabel');
    const cLen   = document.getElementById('c-len');
    const cUpp   = document.getElementById('c-upp');
    const cNum   = document.getElementById('c-num');

    const hasLen = val.length >= 8;
    const hasUpp = /[A-Z]/.test(val);
    const hasNum = /[0-9]/.test(val);

    cLen.classList.toggle('ok', hasLen);
    cUpp.classList.toggle('ok', hasUpp);
    cNum.classList.toggle('ok', hasNum);

    const score = [hasLen, hasUpp, hasNum, val.length >= 12, /[^A-Za-z0-9]/.test(val)]
                  .filter(Boolean).length;

    const levels = [
      { w: '0%',   bg: '#e2e8f0', txt: '' },
      { w: '25%',  bg: '#e74c3c', txt: 'Très faible' },
      { w: '50%',  bg: '#f39c12', txt: 'Faible' },
      { w: '75%',  bg: '#f1c40f', txt: 'Moyen' },
      { w: '90%',  bg: '#27ae60', txt: 'Fort' },
      { w: '100%', bg: '#16a085', txt: 'Très fort' },
    ];

    const l = val.length === 0 ? levels[0] : levels[Math.min(score, 5)];
    bar.style.width      = l.w;
    bar.style.background = l.bg;
    label.textContent    = l.txt;
  }
</script>

</body>
</html>