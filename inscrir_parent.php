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

    // ---------- Utilitaires ----------
    function clean(string $value): string {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    $nameRegex  = '/^[A-Za-zÀ-ÿ\s]+$/u';
    $emailRegex = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    $cinRegex   = '/^[0-9]{8}$/';
    $telRegex   = '/^[24579][0-9]{7}$/';

    // ── 1. Données parent ──
    $nom_parent = clean($_POST['P_parent']  ?? '');
    $email      = clean($_POST['Adr_mail']  ?? '');
    $cin        = clean($_POST['N_cin']     ?? '');
    $ncnam      = clean($_POST['N_cnam']    ?? '');
    $tel        = clean($_POST['tel']       ?? '');
    $mdp        = $_POST['mdp']             ?? '';
    $conf_mdp   = $_POST['Conf_mdp']        ?? '';

    if ($nom_parent === '' || !preg_match($nameRegex, $nom_parent)) {
        $response = ["success" => false, "message" => "Nom du parent invalide."];
    } elseif ($tel === '' || !preg_match($telRegex, $tel)) {
        $response = ["success" => false, "message" => "Numéro de téléphone invalide."];
    } elseif ($email !== '' && !preg_match($emailRegex, $email)) {
        $response = ["success" => false, "message" => "Email invalide."];
    } elseif (!preg_match($cinRegex, $cin)) {
        $response = ["success" => false, "message" => "CIN invalide (8 chiffres)."];
    } elseif (strlen($mdp) < 6) {
        $response = ["success" => false, "message" => "Mot de passe faible (min 6 caractères)."];
    } elseif ($mdp !== $conf_mdp) {
        $response = ["success" => false, "message" => "Les mots de passe ne correspondent pas."];
    }

    // ── 2. Données bébés ──
    if ($response === null) {
        $babyCount = intval($_POST['baby_count'] ?? 1);
        if ($babyCount < 1 || $babyCount > 2) {
            $response = ["success" => false, "message" => "Nombre de bébés invalide."];
        }
    }

    if ($response === null) {
        $bebes = [];

        for ($i = 1; $i <= $babyCount; $i++) {
            $nom_bebe  = clean($_POST["P_bebe$i"] ?? '');
            $date_bebe = clean($_POST["D_bebe$i"] ?? '');
            $lieu_bebe = clean($_POST["L_bebe$i"] ?? '');
            $sexe_bebe = clean($_POST["sexe$i"]   ?? '');

            if ($nom_bebe === '' || !preg_match($nameRegex, $nom_bebe)) {
                $response = ["success" => false, "message" => "Nom du bébé $i invalide."]; break;
            }
            if ($date_bebe === '') {
                $response = ["success" => false, "message" => "Date de naissance du bébé $i obligatoire."]; break;
            }
            $d = DateTime::createFromFormat('Y-m-d', $date_bebe);
            if (!$d || $d->format('Y-m-d') !== $date_bebe) {
                $response = ["success" => false, "message" => "Date de naissance du bébé $i invalide."]; break;
            }
            if ($lieu_bebe === '' || !preg_match($nameRegex, $lieu_bebe)) {
                $response = ["success" => false, "message" => "Lieu de naissance du bébé $i invalide."]; break;
            }
            if (!in_array($sexe_bebe, ['garcon', 'fille'])) {
                $response = ["success" => false, "message" => "Choisir le sexe du bébé $i."]; break;
            }

            $bebes[] = [
                'nom'  => $nom_bebe,
                'date' => $date_bebe,
                'lieu' => $lieu_bebe,
                'sexe' => ($sexe_bebe === 'garcon') ? 'M' : 'F',
            ];
        }

        // Vérification jumeaux
        if ($response === null && $babyCount === 2) {
            if ($bebes[0]['date'] !== $bebes[1]['date']) {
                $response = ["success" => false, "message" => "Jumeaux requis : même DATE de naissance."];
            } elseif (strtolower($bebes[0]['lieu']) !== strtolower($bebes[1]['lieu'])) {
                $response = ["success" => false, "message" => "Jumeaux requis : même LIEU de naissance."];
            }
        }
    }

    // ── 3. Unicité email / CIN ──
    if ($response === null) {
        $stmtCheck = $pdo->prepare("SELECT id_parent FROM parent WHERE email = :email OR cin = :cin LIMIT 1");
        $stmtCheck->execute([':email' => $email, ':cin' => $cin]);
        if ($stmtCheck->fetch()) {
            $response = ["success" => false, "message" => "Un compte avec cet email ou ce CIN existe déjà."];
        }
    }

    // ── 4. Insertion en base ──
    if ($response === null) {
        $mot_de_passe_hash = password_hash($mdp, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            $stmtParent = $pdo->prepare("
                INSERT INTO parent (nom_prenom, telephone, email, cin, ncnam, mot_de_passe)
                VALUES (:nom, :tel, :email, :cin, :ncnam, :mdp)
            ");
            $stmtParent->execute([
                ':nom'   => $nom_parent,
                ':tel'   => $tel,
                ':email' => $email,
                ':cin'   => $cin,
                ':ncnam' => ($ncnam !== '') ? $ncnam : null,
                ':mdp'   => $mot_de_passe_hash,
            ]);
            $id_parent = $pdo->lastInsertId();

            $stmtBebe = $pdo->prepare("
                INSERT INTO bebe (nom_prenom, date_naissance, lieu_naissance, sexe, id_parent)
                VALUES (:nom, :date, :lieu, :sexe, :id_parent)
            ");
            foreach ($bebes as $bebe) {
                $stmtBebe->execute([
                    ':nom'       => $bebe['nom'],
                    ':date'      => $bebe['date'],
                    ':lieu'      => $bebe['lieu'],
                    ':sexe'      => $bebe['sexe'],
                    ':id_parent' => $id_parent,
                ]);
            }

            $pdo->commit();
            $response = ["success" => true, "message" => "Inscription réussie ! Vous pouvez maintenant vous connecter."];

        } catch (PDOException $e) {
            $pdo->rollBack();
            $response = ["success" => false, "message" => "Erreur lors de l'enregistrement : " . $e->getMessage()];
        }
    }

    echo json_encode($response);
    exit; // ← Stoppe ici pour les requêtes AJAX, n'affiche pas le HTML
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inscription Parent</title>
  <link rel="stylesheet" href="styles/style_inscri_parent.css" />
  <link rel="icon" type="image/png" href="images/logo1.png" />
  <style>
    /* ── Lien "Vous avez déjà un compte ?" ── */
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

    .login-link a:hover {
      color: #1c1f5a;
      text-decoration: underline;
    }
    .fixed-top-left {
  position: fixed;
  top: 15px;
  left: 15px;
  z-index: 1000;
}

/* lien sans soulignement + alignement */
.logo-link {
  display: flex;
  align-items: center;
  text-decoration: none;
}

/* logo cercle */
.welcome-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
}

/* texte TinySteps */
.site-name {
  color: #2f5d7c;
  font-weight: bold;
  font-size: 20px;
  margin-left: 8px;
}

/* responsive */
@media (max-width: 600px) {
  .welcome-icon {
    width: 45px;
    height: 45px;
  }
  .site-name {
    font-size: 16px;
  }
}
  </style>
</head>

<body>
<body>

<div class="form-header fixed-top-left">
  <a href="home.php" class="logo-link">
    <img src="images/logo1.png" alt="Logo" class="welcome-icon" />
    <span class="site-name">TinySteps</span>
  </a>
</div>
  <form onsubmit="verifier(event)">
    <div align="center">
      <h1>Bienvenue chez Votre Pédiatre</h1>
      <h4>Inscrivez-vous maintenant</h4>
    </div>

    <!-- Section Bébé 1 (toujours visible) -->
    <div align="right" class="form-section" id="baby1">
      <table>
        <tr>
          <td><h5>Information Bébé</h5></td>
        </tr>
        <tr>
          <td><label for="P_bebe1">Nom et Prénom du Bébé*</label></td>
          <td><input type="text" id="P_bebe1" name="P_bebe1" /></td>
        </tr>
        <tr>
          <td><label for="D_bebe1">Date de naissance*</label></td>
          <td><input type="date" id="D_bebe1" name="D_bebe1" /></td>
          <td><label for="L_bebe1">Lieu de naissance*</label></td>
          <td><input type="text" id="L_bebe1" name="L_bebe1" /></td>
        </tr>
        <tr>
          <td><label>Sexe*</label></td>
          <td><input type="radio" name="sexe1" id="garcon1" value="garcon" /> Garçon</td>
          <td><input type="radio" name="sexe1" id="fille1"  value="fille"  /> Fille</td>
        </tr>
      </table>
    </div>

    <!-- Bouton ajouter un bébé -->
    <input type="button" id="add-baby" style="margin-bottom: 20px" value="Ajouter un autre Bébé" />

    <!-- Section Parent -->
    <div align="center" class="form-section">
      <table>
        <tr>
          <td><h5>Information Parent</h5></td>
        </tr>
        <tr>
          <td><label for="P_parent">Nom et Prénom*</label></td>
          <td><input type="text" id="P_parent" name="P_parent" /></td>
        </tr>
        <tr>
          <td><label for="tel">Numéro Téléphone*</label></td>
          <td><input type="tel" name="tel" id="tel" /></td>
        </tr>
        <tr>
          <td><label for="Adr_mail">Adresse e-Mail*</label></td>
          <td><input type="email" name="Adr_mail" id="Adr_mail" /></td>
        </tr>
        <tr>
          <td><label for="N_cin">Numéro CIN*</label></td>
          <td><input type="text" id="N_cin" name="N_cin" maxlength="8" /></td>
        </tr>
        <tr>
          <td><label for="N_cnam">Numéro CNAM</label></td>
          <td><input type="text" id="N_cnam" name="N_cnam" /></td>
        </tr>
        <tr>
          <td><label for="mdp">Mot de passe*</label></td>
          <td><input type="password" name="mdp" id="mdp" /></td>
        </tr>
        <tr>
          <td><label for="Conf_mdp">Confirmer le Mot de passe*</label></td>
          <td><input type="password" name="Conf_mdp" id="Conf_mdp" /></td>
        </tr>
      </table>
    </div>

    <div align="right">
      <input type="submit" value="Valider l'inscription" />
      <input type="reset" value="Réinitialiser" onclick="resetBabySection()" />
      <p class="login-link">
          Vous avez déjà un compte ?
          <a href="connexion.php?role=parent">Se connecter</a>
        </p>
    </div>
    

  </form>

  <!-- Popup -->
  <div id="popup" class="popup">
    <div class="popup-box">
      <p id="popupText"></p>
      <button onclick="closePopup()">OK</button>
    </div>
  </div>

  <script>


  // ─── Popup ───────────────────────────────────────────────────
  function showPopup(message) {
    document.getElementById("popupText").innerText = message;
    document.getElementById("popup").style.display = "flex";
  }

  function closePopup() {
    document.getElementById("popup").style.display = "none";
  }

  // ─── Ajout / Suppression bébé 2 ──────────────────────────────
  let babyCount = 1;

  document.getElementById("add-baby").addEventListener("click", function () {
    if (babyCount === 2) {
      const existing = document.getElementById("baby2");
      if (existing) existing.remove();
      babyCount = 1;
      this.value = "Ajouter un autre Bébé";
      return;
    }

    if (!verifierBebe(1)) return;

    const originalBaby = document.getElementById("baby1");
    const newBaby = originalBaby.cloneNode(true);
    newBaby.id = "baby2";

    const title = newBaby.querySelector("h5");
    if (title) title.textContent = "Information Bébé 2";

    newBaby.querySelectorAll("input").forEach((input) => {
      if (input.type === "radio") {
        input.checked = false;
        input.name = input.name.replace(/\d+$/, "2");
        input.id = input.id.replace(/\d+$/, "2");
      } else {
        input.value = "";
        const oldName = input.name || input.id;
        input.name = oldName.replace(/\d+$/, "2");
        input.id = input.id.replace(/\d+$/, "2");
      }
    });

    newBaby.querySelector("#D_bebe2").value = document.getElementById("D_bebe1").value;
    newBaby.querySelector("#L_bebe2").value = document.getElementById("L_bebe1").value;

    originalBaby.insertAdjacentElement("afterend", newBaby);
    babyCount = 2;
    this.value = "Supprimer le 2ème Bébé";
  });

  // ─── Reset ───────────────────────────────────────────────────
  function resetBabySection() {
    const baby2 = document.getElementById("baby2");
    if (baby2) baby2.remove();
    babyCount = 1;
    document.getElementById("add-baby").value = "Ajouter un autre Bébé";
  }

  // ─── Validation d'un bébé ────────────────────────────────────
  function verifierBebe(i) {
    const nameRegex = /^[A-Za-zÀ-ÿ\s]+$/;
    const nom  = document.getElementById("P_bebe" + i).value.trim();
    const date = document.getElementById("D_bebe" + i).value;
    const lieu = document.getElementById("L_bebe" + i).value.trim();
    const garcon = document.getElementById("garcon" + i);
    const fille  = document.getElementById("fille"  + i);

    if (nom === "" || !nameRegex.test(nom)) {
      showPopup("Nom du bébé " + i + " invalide."); return false;
    }
    if (date === "") {
      showPopup("Date de naissance du bébé " + i + " obligatoire."); return false;
    }
    if (lieu === "" || !nameRegex.test(lieu)) {
      showPopup("Lieu de naissance du bébé " + i + " invalide."); return false;
    }
    if (!garcon.checked && !fille.checked) {
      showPopup("Choisir le sexe du bébé " + i + "."); return false;
    }
    return true;
  }

  // ─── Soumission ──────────────────────────────────────────────
  async function verifier(event) {
    event.preventDefault();

    const nameRegex  = /^[A-Za-zÀ-ÿ\s]+$/;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const cinRegex   = /^[0-9]{8}$/;
    const telRegex   = /^[24579][0-9]{7}$/;

    for (let i = 1; i <= babyCount; i++) {
      if (!verifierBebe(i)) return;
    }

    if (babyCount === 2) {
      if (document.getElementById("D_bebe1").value !== document.getElementById("D_bebe2").value) {
        showPopup("Jumeaux requis : même DATE de naissance."); return;
      }
      if (document.getElementById("L_bebe1").value.toLowerCase() !== document.getElementById("L_bebe2").value.toLowerCase()) {
        showPopup("Jumeaux requis : même LIEU de naissance."); return;
      }
    }

    const parent = document.getElementById("P_parent").value.trim();
    const email  = document.getElementById("Adr_mail").value.trim();
    const cin    = document.getElementById("N_cin").value.trim();
    const mdp    = document.getElementById("mdp").value;
    const conf   = document.getElementById("Conf_mdp").value;
    const tel    = document.getElementById("tel").value.trim();

    if (parent === "" || !nameRegex.test(parent)) { showPopup("Nom du parent invalide."); return; }
    if (tel === "" || !telRegex.test(tel))         { showPopup("Numéro de téléphone invalide."); return; }
    if (email !== "" && !emailRegex.test(email))   { showPopup("Email invalide."); return; }
    if (!cinRegex.test(cin))                       { showPopup("CIN invalide (8 chiffres)."); return; }
    if (mdp.length < 6)                            { showPopup("Mot de passe faible (min 6 caractères)."); return; }
    if (mdp !== conf)                              { showPopup("Les mots de passe ne correspondent pas."); return; }

    const formData = new FormData();
    formData.append("baby_count", babyCount);
    formData.append("P_parent",   parent);
    formData.append("Adr_mail",   email);
    formData.append("N_cin",      cin);
    formData.append("N_cnam",     document.getElementById("N_cnam").value.trim());
    formData.append("tel",        tel);
    formData.append("mdp",        mdp);
    formData.append("Conf_mdp",   conf);

    for (let i = 1; i <= babyCount; i++) {
      formData.append("P_bebe" + i, document.getElementById("P_bebe" + i).value.trim());
      formData.append("D_bebe" + i, document.getElementById("D_bebe" + i).value);
      formData.append("L_bebe" + i, document.getElementById("L_bebe" + i).value.trim());
      const garconEl = document.getElementById("garcon" + i);
      formData.append("sexe" + i, garconEl.checked ? "garcon" : "fille");
    }

    // ✅ Envoi vers cette même page (self-submit)
    try {
      const response = await fetch("inscrir_parent.php", {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      showPopup(result.message);
      if (result.success) {
        event.target.reset();
        resetBabySection();
        setTimeout(() => { window.location.href = "connexion.php"; }, 1800);
      }
    } catch (err) {
      showPopup("Erreur réseau. Veuillez réessayer.");
      console.error(err);
    }
  }
  </script>

</body>
</html>