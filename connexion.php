<?php
// Détecter le rôle depuis l'URL (?role=medecin)
$role_url = (isset($_GET['role']) && $_GET['role'] === 'medecin') ? 'medecin' : 'parent';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Connexion</title>
  <link rel="stylesheet" href="styles/style_connexion.css" />
  <link rel="stylesheet" href="styles/style.css">
  <link rel="icon" href="images/logo1.png" />
</head>

<body>

  <?php include 'connexion_traitement.php'; ?>

  <div class="form-container">
    <form class="login-form" method="POST" action="connexion.php">

      <!-- Champ caché : rôle sélectionné, pré-rempli par PHP -->
      <input type="hidden" name="role" id="role_input" value="<?php echo $role_url; ?>" />

      <div class="role-switcher">
        <button type="button"
          class="role-btn <?php echo $role_url === 'parent'  ? 'active' : ''; ?>"
          onclick="setRole(this)">Parent</button>
        <button type="button"
          class="role-btn <?php echo $role_url === 'medecin' ? 'active' : ''; ?>"
          onclick="setRole(this)">Pédiatre</button>
      </div>

      <div class="form-header">
        <a href="home.php">
          <img src="images/logo1.png" alt="Logo" class="welcome-icon" />
        </a>
        <p class="form-title">Bienvenue</p>
        <p class="form-subtitle">Connectez-vous à votre espace</p>
      </div>

      <input type="email"    name="email"    placeholder="Email"         required />
      <input type="password" name="password" id="pwd" placeholder="Mot de passe" required />

      <div class="login-options">
        <label><input type="checkbox" /> Se souvenir de moi</label>
        <!-- Lien mis à jour : transmet le rôle actif à la page mot de passe oublié -->
        <a class="mdp_oublie" id="forgot-link"
           href="mot_de_passe_oublie.php?role=<?php echo $role_url; ?>">
          Mot de passe oublié ?
        </a>
      </div>

      <input type="submit" value="Se connecter" />

      <!-- Partie Parent : visible uniquement si rôle = parent -->
      <div class="partie_parent" style="display:<?php echo $role_url === 'parent' ? 'block' : 'none'; ?>;">
        <p class="register-link">
          Vous n'avez pas de compte ?
          <a href="inscrir_parent.php">S'inscrire</a>
        </p>
      </div>

      <!-- Partie Pédiatre : visible uniquement si rôle = medecin -->
      <div class="partie_pediatre" style="display:<?php echo $role_url === 'medecin' ? 'block' : 'none'; ?>;">
        <p class="register-link">
          Vous n'avez pas de compte ?
          <a href="inscrir_pediatre.php">S'inscrire</a>
        </p>
      </div>

    </form>
  </div>

  <script>
    function setRole(btn) {
      document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const parentDiv   = document.querySelector('.partie_parent');
      const pediatreDiv = document.querySelector('.partie_pediatre');
      const forgotLink  = document.getElementById('forgot-link');

      if (btn.textContent.includes("Pédiatre")) {
        pediatreDiv.style.display = "block";
        parentDiv.style.display   = "none";
        document.getElementById('role_input').value = 'medecin';
        forgotLink.href = 'mot_de_passe_oublie.php?role=medecin';
      } else {
        parentDiv.style.display   = "block";
        pediatreDiv.style.display = "none";
        document.getElementById('role_input').value = 'parent';
        forgotLink.href = 'mot_de_passe_oublie.php?role=parent';
      }
    }
  </script>

</body>
</html>