<?php
session_start();

$response = null;

// ── Traitement POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=utf-8');

    // ---------- Connexion BDD ----------
    $host   = "127.0.0.1";   // XAMPP : 127.0.0.1 plus fiable que localhost
    $dbname = "tinysteps";
    $user   = "root";
    $pass   = "";

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erreur de connexion : " . $e->getMessage()]);
        exit;
    }
  // Récupération et nettoyage des données
  $nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$email = trim($_POST['email'] ?? '');
$tel = trim($_POST['tel'] ?? '');
$date_naiss = $_POST['date_naissance'] ?? '';
$sexe = $_POST['sexe'] ?? '';
$mdp = $_POST['mdp'] ?? '';
$cmdp = $_POST['cmdp'] ?? '';
$num_inscri = trim($_POST['num_inscri'] ?? '');
$annees_exp = $_POST['annees_experience'] ?? '';
$diplome = trim($_POST['diplome'] ?? '');
$nom_cabinet = trim($_POST['nom_cabinet'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$pays = trim($_POST['pays'] ?? 'Tunisie');
$ville = trim($_POST['ville'] ?? '');
$code_postal = trim($_POST['code_postal'] ?? '');
$tel_cabinet = trim($_POST['tel_cabinet'] ?? '');

  // ---- Validation côté serveur ----
  $erreurs = [];
  if (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $nom) || !preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $prenom)) {
    $erreurs[] = "Nom et prénom ne doivent contenir que des lettres.";
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erreurs[] = "Email invalide.";
  }
  if (!preg_match('/^[2-9][0-9]{7}$/', $tel)) {
    $erreurs[] = "Numéro de téléphone invalide.";
  }
  if ($date_naiss) {
    $age = date('Y') - date('Y', strtotime($date_naiss));
    if ($age < 25 || $age > 80) {
      $erreurs[] = "Date de naissance incohérente pour un médecin.";
    }
  } else {
    $erreurs[] = "Date de naissance requise.";
  }
  if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $mdp)) {
    $erreurs[] = "Mot de passe faible (min 8 caractères, majuscule, minuscule, chiffre).";
  }
  if ($mdp !== $cmdp) {
    $erreurs[] = "Les mots de passe ne correspondent pas.";
  }
  if (!preg_match('/^[A-Z0-9\-]{5,30}$/', $num_inscri)) {
    $erreurs[] = "Numéro d'inscription invalide.";
  }
  if (!empty($diplome) && !preg_match('/^[A-Za-zÀ-ÿ0-9\s,.()-]*$/', $diplome)) {
    $erreurs[] = "Diplôme invalide.";
  }
  if (strlen($nom_cabinet) < 2) {
    $erreurs[] = "Nom du cabinet invalide.";
  }
  if (strlen($adresse) < 5) {
    $erreurs[] = "Adresse trop courte.";
  }
  if (!preg_match('/^[0-9]{4}$/', $code_postal)) {
    $erreurs[] = "Code postal invalide (4 chiffres).";
  }
  if (!empty($tel_cabinet) && !preg_match('/^[2-9][0-9]{7}$/', $tel_cabinet)) {
    $erreurs[] = "Téléphone du cabinet invalide.";
  }

  // Validation disponibilités
  $dispos_post = $_POST['dispos'] ?? [];
  $dispos_valides = [];
  foreach ($dispos_post as $jour_num => $plages) {
    $jour_num = (int) $jour_num;
    if ($jour_num < 1 || $jour_num > 7) continue;
    if (!is_array($plages)) continue;
    foreach ($plages as $plage) {
      $debut = trim($plage['debut'] ?? '');
      $fin   = trim($plage['fin']   ?? '');
      if (!$debut || !$fin) continue;
      if (!preg_match('/^\d{2}:\d{2}$/', $debut) || !preg_match('/^\d{2}:\d{2}$/', $fin)) {
        $erreurs[] = "Format d'heure invalide pour les disponibilités.";
        break 2;
      }
      if ($debut >= $fin) {
        $erreurs[] = "L'heure de fin doit être après l'heure de début.";
        break 2;
      }
      $dispos_valides[] = ['jour' => $jour_num, 'debut' => $debut . ':00', 'fin' => $fin . ':00'];
    }
  }

  // Vérifier si l'email existe déjà
  $stmt = $pdo->prepare("SELECT id FROM medecin WHERE email = ?");
  $stmt->execute([$email]);
  if ($stmt->fetch()) {
    $erreurs[] = "Cet email est déjà utilisé.";
  }

  if (!empty($erreurs)) {
    header('Content-Type: application/json');
    echo json_encode(['succes' => false, 'message' => implode(' ', $erreurs)]);
    exit;
  }
  // Hash du mot de passe
  $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);

  try {
    $pdo->beginTransaction();

    // ── Insertion dans medecins (avec s, sans colonne specialite) ──
    $sqlMed = "INSERT INTO medecin
                       (nom, prenom, email, tel, date_nai, sexe, mdp, num_inscri_med, ann_expr, diplome)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtMed = $pdo->prepare($sqlMed);
    $stmtMed->execute([
      $nom,
      $prenom,
      $email,
      $tel,
      $date_naiss,
      $sexe,
      $mdp_hash,
      $num_inscri,
      $annees_exp,
      $diplome
    ]);
    $medecin_id = $pdo->lastInsertId();

    // ── Insertion dans cabinet ──
    $sqlCab = "INSERT INTO cabinet
                       (medecin_id, nom_cabinet, adresse, ville, code_postal, pays, tel_cabin)
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtCab = $pdo->prepare($sqlCab);
    $stmtCab->execute([
      $medecin_id,
      $nom_cabinet,
      $adresse,
      $ville,
      $code_postal,
      $pays,
      $tel_cabinet
    ]);
    // Insertion dans disponibilites
    if (!empty($dispos_valides)) {
      $sqlDispo = "INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, actif) VALUES (?, ?, ?, ?, 1)";
      $stmtDispo = $pdo->prepare($sqlDispo);
      foreach ($dispos_valides as $d) {
        $stmtDispo->execute([$medecin_id, $d['jour'], $d['debut'], $d['fin']]);
      }
    }

    $pdo->commit();
    header('Content-Type: application/json');
    echo json_encode(['succes' => true, 'message' => 'Inscription réussie ! Redirection vers la page de connexion...']);
    exit;

  } catch (Exception $e) {
    $pdo->rollBack();
    header('Content-Type: application/json');
    echo json_encode(['succes' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inscription Pédiatre</title>
  <link rel="stylesheet" href="styles/style_pediatre.css" />
  <link rel="stylesheet" href="styles/style.css">
  <link rel="icon" href="images/logo1.png" />
  <style>
    .login-link {
      text-align: center;
      margin-top: 18px;
      margin-bottom: 6px;
      font-size: 14px;
      color: #6b7280;
    }
    .login-link a {
      color: var(--primary-blue, #4e8d9c);
      font-weight: 600;
      text-decoration: none;
      transition: color 0.2s;
    }
    .login-link a:hover { color: #1c1f5a; text-decoration: underline; }

    /* ── Disponibilités ── */
    .dispo-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    @media (max-width: 640px) { .dispo-grid { grid-template-columns: 1fr; } }

    .dispo-jour {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 12px 14px;
      transition: border-color 0.2s;
    }
    .dispo-jour.actif { border-color: var(--primary-blue, #4e8d9c); background: #f0f9ff; }

    .dispo-jour-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 10px;
    }
    .dispo-jour-header input[type="checkbox"] {
      width: 16px; height: 16px;
      accent-color: var(--primary-blue, #4e8d9c);
      margin: 0;
      cursor: pointer;
    }
    .dispo-jour-header label {
      font-weight: 600;
      font-size: 13px;
      color: #1c1f5a;
      cursor: pointer;
    }

    .plages-container { display: none; flex-direction: column; gap: 8px; }
    .dispo-jour.actif .plages-container { display: flex; }

    .plage-row {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .plage-row input[type="time"] {
      flex: 1;
      padding: 7px 10px;
      border: 1px solid #d1d9e6;
      border-radius: 7px;
      font-size: 13px;
      width: auto;
      margin: 0;
    }
    .plage-sep { font-size: 12px; color: #6b7280; white-space: nowrap; }

    .btn-add-plage {
      background: none;
      border: 1px dashed var(--primary-blue, #4e8d9c);
      color: var(--primary-blue, #4e8d9c);
      border-radius: 7px;
      padding: 5px 10px;
      font-size: 12px;
      cursor: pointer;
      margin-top: 4px;
      transition: background 0.2s;
    }
    .btn-add-plage:hover { background: #e0f2fe; }

    .btn-remove-plage {
      background: none;
      border: none;
      color: #ef4444;
      cursor: pointer;
      font-size: 15px;
      padding: 0 2px;
      line-height: 1;
    }
    .btn-remove-plage:hover { color: #b91c1c; }

    .dispo-hint {
      font-size: 12px;
      color: #6b7280;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
  </style>
  <script src="script_inscri_pediatre.js"></script>
</head>

<body>
  <div class="container">
    <!-- Sidebar gauche -->
    <div class="sidebar">
      <div class="info-box">
        Données sécurisées : Vos informations sont protégées et confidentielles.
      </div>
    </div>

    <!-- Formulaire droit -->
    <div class="form-container">
      <div class="form-header">
        <a href="home.php">
          <img src="images/logo1.png" alt="Logo TinySteps" class="form-logo" />
        </a>
        <p class="form-welcome">Bienvenue sur <span>TinySteps</span></p>
        <p class="form-subtitle">Créez votre compte pour accéder à votre espace et assurer le suivi médical.</p>
      </div>

      <form onsubmit="verifier(event)" id="form" method="POST">

        <!-- Informations Personnelles -->
        <fieldset>
          <legend>Informations Personnelles</legend>
          <div class="two-columns">
            <input type="text" placeholder="Nom *" id="nom" name="nom" required />
            <input type="text" placeholder="Prénom *" id="prenom" name="prenom" required />
          </div>
          <input type="email" placeholder="Email professionnel *" id="email" name="email" required />
          <div class="two-columns">
            <input type="tel" placeholder="Numéro de téléphone *" id="tel" name="tel" required />
            <input type="date" placeholder="Date de naissance" id="date" name="date_naissance" />
            <select name="sexe" required>
              <option value="" disabled selected hidden>Sexe</option>
              <option value="Homme">Homme</option>
              <option value="Femme">Femme</option>
            </select>
          </div>
        </fieldset>

        <!-- Informations de Connexion -->
        <fieldset>
          <legend>Informations de Connexion</legend>
          <input type="password" placeholder="Mot de passe *" id="mdp" name="mdp" required />
          <input type="password" placeholder="Confirmer le mot de passe *" id="cmdp" name="cmdp" required />
        </fieldset>

        <!-- Informations Professionnelles -->
        <fieldset>
          <legend>Informations Professionnelles</legend>
          <input type="text" placeholder="Numéro d'inscription à l'ordre des médecins *" required id="num_inscri"
            name="num_inscri" />
          <div class="two-columns">
            <select name="specialite">
              <option value="Pédiatre" selected>Pédiatre</option>
            </select>
            <select name="annees_experience" required>
              <option value="" disabled selected hidden>Années d'expérience</option>
              <option value="0-1">0 - 1 an</option>
              <option value="1-3">1 - 3 ans</option>
              <option value="3-5">3 - 5 ans</option>
              <option value="5-10">5 - 10 ans</option>
              <option value="10-20">10 - 20 ans</option>
              <option value="20+">20+ ans</option>
            </select>
          </div>
          <input type="text" placeholder="Diplômes (optionnel)" id="diplom" name="diplome" />
        </fieldset>

        <!-- Informations du Cabinet / Clinique -->
        <fieldset>
          <legend>Informations du Cabinet / Clinique</legend>
          <input type="text" placeholder="Nom du cabinet / clinique *" required id="nom_cabin" name="nom_cabinet" />
          <input type="text" placeholder="Adresse *" required id="adr" name="adresse" />
          <div class="two-columns">
            <select name="pays" required>
              <option value="Tunisie" selected>Tunisie</option>
            </select>
            <select name="ville" required>
              <option value="" disabled selected hidden>Choisir la ville</option>
              <option value="Ariana">Ariana</option>
              <option value="Béja">Béja</option>
              <option value="Ben Arous">Ben Arous</option>
              <option value="Bizerte">Bizerte</option>
              <option value="Gabès">Gabès</option>
              <option value="Gafsa">Gafsa</option>
              <option value="Jendouba">Jendouba</option>
              <option value="Kairouan">Kairouan</option>
              <option value="Kasserine">Kasserine</option>
              <option value="Kébili">Kébili</option>
              <option value="Le Kef">Le Kef</option>
              <option value="Mahdia">Mahdia</option>
              <option value="La Manouba">La Manouba</option>
              <option value="Médenine">Médenine</option>
              <option value="Monastir">Monastir</option>
              <option value="Nabeul">Nabeul</option>
              <option value="Sfax">Sfax</option>
              <option value="Sidi Bouzid">Sidi Bouzid</option>
              <option value="Siliana">Siliana</option>
              <option value="Sousse">Sousse</option>
              <option value="Tataouine">Tataouine</option>
              <option value="Tozeur">Tozeur</option>
              <option value="Tunis">Tunis</option>
              <option value="Zaghouan">Zaghouan</option>
            </select>
            <input type="text" placeholder="Code postal *" pattern="[0-9]{4}" maxlength="4" required id="code_post"
              name="code_postal" />
          </div>
          <input type="tel" placeholder="Téléphone du cabinet" id="tel_cabin" name="tel_cabinet" />
        </fieldset>
         <!-- ══ DISPONIBILITÉS ══ -->
         <fieldset>
          <legend>Disponibilités de consultation</legend>
          <p class="dispo-hint">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="#4e8d9c" stroke-width="1.3"/><path d="M8 5v3.5l2 2" stroke="#4e8d9c" stroke-width="1.3" stroke-linecap="round"/></svg>
            Cochez les jours où vous consultez et indiquez vos plages horaires.
          </p>
          <div class="dispo-grid" id="dispoGrid">

            <?php
            $jours = [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi',7=>'Dimanche'];
            foreach ($jours as $num => $label):
            ?>
            <div class="dispo-jour" id="jour-<?= $num ?>">
              <div class="dispo-jour-header">
                <input type="checkbox" id="chk-<?= $num ?>" data-jour="<?= $num ?>"
                       onchange="toggleJour(<?= $num ?>)">
                <label for="chk-<?= $num ?>"><?= $label ?></label>
              </div>
              <div class="plages-container" id="plages-<?= $num ?>">
                <!-- Plage par défaut -->
                <div class="plage-row">
                  <input type="time" name="dispos[<?= $num ?>][0][debut]" value="08:00" required>
                  <span class="plage-sep">→</span>
                  <input type="time" name="dispos[<?= $num ?>][0][fin]" value="12:00" required>
                  <button type="button" class="btn-remove-plage" onclick="removePlage(this)" title="Supprimer">✕</button>
                </div>
              </div>
              <button type="button" class="btn-add-plage" id="btn-add-<?= $num ?>"
                      style="display:none;" onclick="addPlage(<?= $num ?>)">
                + Ajouter une plage
              </button>
            </div>
            <?php endforeach; ?>

          </div>
        </fieldset>

        <input type="submit" value="S'inscrire" />
        <br><br>
        <p class="login-link">
          Vous avez déjà un compte ?
          <a href="connexion.php?role=medecin">Se connecter</a>
        </p>

      </form>
    </div>
  </div>

  <div id="popup" class="popup">
    <div class="popup-box">
      <p id="popupText"></p>
      <button onclick="closePopup()">OK</button>
    </div>
  </div>
  <script>
    // ── Gestion des disponibilités ──

    // Compteur de plages par jour
    const plageCounts = { 1:1,2:1,3:1,4:1,5:1,6:1,7:1 };

    function toggleJour(jourNum) {
      const chk = document.getElementById('chk-' + jourNum);
      const jourDiv = document.getElementById('jour-' + jourNum);
      const plagesContainer = document.getElementById('plages-' + jourNum);
      const btnAdd = document.getElementById('btn-add-' + jourNum);

      if (chk.checked) {
        jourDiv.classList.add('actif');
        btnAdd.style.display = 'inline-block';
        // Activer les inputs
        plagesContainer.querySelectorAll('input[type="time"]').forEach(i => i.required = true);
      } else {
        jourDiv.classList.remove('actif');
        btnAdd.style.display = 'none';
        // Désactiver les inputs pour ne pas les valider
        plagesContainer.querySelectorAll('input[type="time"]').forEach(i => {
          i.required = false;
          // Vider le name pour ne pas envoyer de données
        });
        // Renommer pour éviter l'envoi
        plagesContainer.querySelectorAll('input[type="time"]').forEach(i => i.removeAttribute('name'));
      }
    }

    function addPlage(jourNum) {
      const container = document.getElementById('plages-' + jourNum);
      const idx = plageCounts[jourNum]++;
      const row = document.createElement('div');
      row.className = 'plage-row';
      row.innerHTML = `
        <input type="time" name="dispos[${jourNum}][${idx}][debut]" value="14:00">
        <span class="plage-sep">→</span>
        <input type="time" name="dispos[${jourNum}][${idx}][fin]" value="18:00">
        <button type="button" class="btn-remove-plage" onclick="removePlage(this)" title="Supprimer">✕</button>
      `;
      container.appendChild(row);
    }

    function removePlage(btn) {
      const row = btn.closest('.plage-row');
      const container = row.parentElement;
      if (container.querySelectorAll('.plage-row').length <= 1) {
        // Laisser au moins une plage, décocher le jour à la place
        const jourDiv = container.closest('.dispo-jour');
        const jourNum = jourDiv.id.replace('jour-', '');
        const chk = document.getElementById('chk-' + jourNum);
        chk.checked = false;
        toggleJour(parseInt(jourNum));
        return;
      }
      row.remove();
    }

    // Avant soumission : retirer les inputs des jours non cochés
    // (déjà géré dans toggleJour via removeAttribute name)
    // S'assurer que les jours décochés n'envoient rien
    document.getElementById('form').addEventListener('submit', function() {
      document.querySelectorAll('.dispo-jour:not(.actif) input[type="time"]').forEach(i => {
        i.removeAttribute('name');
        i.required = false;
      });
    });
  </script>
</body>

</html>