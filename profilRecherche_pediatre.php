<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: connexion.php");
    exit;
}
$conn = new mysqli("localhost", "root", "", "tinysteps");
$conn->set_charset("utf8mb4");

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id === 0) {
  header('Location: recherche_pediatre.php');
  exit;
}

// Récupérer les horaires de travail depuis disponibilites
$plages_par_jour = [];
$map_jour = [
  1 => 'Lundi',
  2 => 'Mardi',
  3 => 'Mercredi',
  4 => 'Jeudi',
  5 => 'Vendredi',
  6 => 'Samedi',
  7 => 'Dimanche'
];

$sql = "SELECT jour_semaine, heure_debut, heure_fin 
        FROM disponibilites 
        WHERE medecin_id = ? AND actif = 1
        ORDER BY jour_semaine, heure_debut";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

while ($dispo = $result->fetch_assoc()) {
  $jour_num = $dispo['jour_semaine'];
  $nom_jour = $map_jour[$jour_num];
  $debut = substr($dispo['heure_debut'], 0, 5);
  $fin = substr($dispo['heure_fin'], 0, 5);
  $plages_par_jour[$nom_jour][] = "$debut – $fin";
}
$stmt->close();

// Initialiser tous les jours (pour que les jours sans plage apparaissent avec "Fermé")
$tous_les_jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
foreach ($tous_les_jours as $jour) {
  if (!isset($plages_par_jour[$jour])) {
    $plages_par_jour[$jour] = [];
  }
}


$stmt = $conn->prepare("SELECT * FROM medecin WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
  echo "Médecin introuvable.";
  exit;
}

$medecin = [
  'nom' => 'Dr. ' . $row['prenom'] . ' ' . $row['nom'],
  'initiales' => strtoupper(substr($row['prenom'], 0, 1) . substr($row['nom'], 0, 1)),
  'specialite' => $row['specialite'],
  'clinique' => $row['cabinet'],
  'experience' => ($row['ann_expr'] ?? '—') . ' ans',
  'diplome' => $row['diplome'] ?? '—',
  'note' => '—',
  'consultations' => '—',
  'adresse' => $row['cabinet'],
  'horaires' => 'Lun – Ven · 08h00 – 17h00',
  'teleconsult' => false,
  'cnam' => false,
  'disponible' => true,
];
// ✅ REMPLACE le tableau $avis statique :
$avis = [];
$stmt2 = $conn->prepare("SELECT a.note, a.commentaire, p.nom_prenom 
                          FROM avis a 
                          JOIN parent p ON a.parent_id = p.id_parent
                          WHERE a.medecin_id = ? AND a.statut = 'approuve'");
if (!$stmt2) {
  die("Erreur SQL avis : " . $conn->error);
}
$stmt2->bind_param("i", $id);
$stmt2->execute();
$avisResult = $stmt2->get_result();
while ($a = $avisResult->fetch_assoc()) {
  $avis[] = [
    'nom' => $a['nom_prenom'],   // ← directement nom_prenom
    'note' => (int) $a['note'],
    'texte' => $a['commentaire']
  ];
}

// Calculer la note moyenne
$stmtNote = $conn->prepare("SELECT AVG(note) as moy FROM avis WHERE medecin_id = ? AND statut = 'approuve'");
$stmtNote->bind_param("i", $id);
$stmtNote->execute();
$noteResult = $stmtNote->get_result();
$noteRow = $noteResult->fetch_assoc();
$medecin['note'] = round($noteRow['moy'] ?? 0, 1) . ' / 5';
$noteResult->free();
$stmtNote->close();

// Nombre de consultations (rendez-vous terminés)
$stmtConsult = $conn->prepare("SELECT COUNT(*) as total FROM rendez_vous WHERE pediatre_id = ? AND statut = 'confirmé'");
$stmtConsult->bind_param("i", $id);
$stmtConsult->execute();

$consultResult = $stmtConsult->get_result();
$consultRow = $consultResult->fetch_assoc();
$medecin['consultations'] = $consultRow['total'] ?? 0;
$consultResult->free();
$stmtConsult->close();


$tags = ['Nourrissons', 'Adolescents', 'Vaccination', 'Suivi de croissance'];
$competences = ['Nutrition infantile', 'Maladies infectieuses', 'Développement', 'Allergies'];

$tarifs = [
  ['label' => 'Consultation standard', 'prix' => '70 DT'],
  ['label' => 'Téléconsultation', 'prix' => '80 DT'],
  ['label' => 'Suivi nourrisson', 'prix' => '60 DT'],
];

// Traitement du formulaire de RDV
$rdv_confirme = false;
$rdv_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['creneau'])) {
  $creneau = htmlspecialchars($_POST['creneau']);
  $jour = htmlspecialchars($_POST['jour'] ?? '');
  // Ici : insérer en BDD, envoyer un email, etc.
  $rdv_confirme = true;
  $rdv_message = "Rendez-vous confirmé le <strong>$jour à $creneau</strong> avec {$medecin['nom']}.";
}

// Fonction utilitaire : afficher des étoiles
function etoiles(int $note, int $max = 5): string
{
  $html = '';
  for ($i = 1; $i <= $max; $i++) {
    $html .= $i <= $note
      ? '<span class="star filled">★</span>'
      : '<span class="star empty">★</span>';
  }
  return $html;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil – <?= htmlspecialchars($medecin['nom']) ?></title>
  <link rel="stylesheet" href="styles/profil.css">
  <link rel="stylesheet" href="styles/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body {
      padding-left: 260px !important;
    }
  </style>
</head>

<body>
  <!-- ══ SIDEBAR ══ -->
  <aside class="sidebar" id="sidebar">
        <a href="home.php">
            <div class="sidebar-header">
                <div class="logo-img">
                    <img src="images/logo1.png" alt="TinySteps Logo">
                </div>
                <h2>TinySteps</h2>
                <p style="font-size:12px;opacity:.7;margin-top:8px;">Suivi bébé</p>
            </div>
        </a>
        <nav class="sidebar-nav">
            <a href="accueil_parent.php"><i class="fas fa-home"></i> Accueil</a>
            <a href="profil_parent.php"><i class="fas fa-user"></i> Profil</a>
            <a href="recherche_pediatre.php" class="active"><i class="fas fa-stethoscope"></i> Recherche pédiatre</a>
            <a href="croissance_simple.php"><i class="fas fa-chart-line"></i> Courbe de croissance</a>
            <a href="calendrier_vaccination.php"><i class="fas fa-syringe"></i> Calendrier vaccinal</a>
            <a href="conseils_logged.php"><i class="fas fa-lightbulb"></i> Conseils</a>
            <a href="messagerie.php"><i class="fas fa-envelope"></i> Messagerie</a>
        </nav>
        <div class="sidebar-footer">
            <div class="parent-info">
                <div class="parent-avatar">
                    <?= strtoupper(substr($parent['nom_prenom'] ?? 'P', 0, 1)) ?>
                </div>
                <div>
                    <div class="parent-name">
                        <?= htmlspecialchars(($parent['nom_prenom'] ?? '')) ?>
                    </div>

                    <div class="parent-email">
                        <?= htmlspecialchars($parent['email'] ?? '') ?>
                    </div>
                </div>
            </div>
            <a href="home.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>
  <div class="page">


    <!-- ── CARTE IDENTITÉ ── -->
    <div class="card card-hero">
      <div class="hero-top">
        <div class="avatar"><?= $medecin['initiales'] ?></div>
        <div class="hero-info">
          <div class="hero-name-row">
            <h1><?= htmlspecialchars($medecin['nom']) ?></h1>
            <?php if ($medecin['disponible']): ?>
              <span class="badge badge-green">Disponible</span>
            <?php else: ?>
              <span class="badge badge-red">Indisponible</span>
            <?php endif; ?>
          </div>
          <p class="hero-sub"><?= htmlspecialchars($medecin['specialite']) ?> ·
            <?= htmlspecialchars($medecin['clinique']) ?></p>
          <div class="tags-row">
            <?php foreach ($tags as $tag): ?>
              <span class="badge badge-blue"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="stats-row">
        <div class="stat">
          <span class="stat-val"><?= htmlspecialchars($medecin['experience']) ?></span>
          <span class="stat-label">Expérience</span>
        </div>
        <div class="stat">
          <span class="stat-val"><?= htmlspecialchars($medecin['note']) ?></span>
          <span class="stat-label">Note patients</span>
        </div>
        <div class="stat">
          <span class="stat-val"><?= htmlspecialchars($medecin['consultations']) ?></span>
          <span class="stat-label">Consultations</span>
        </div>
      </div>
    </div>

    <!-- ── COLONNES ── -->
    <div class="grid-two">

      <!-- Colonne gauche -->
      <div class="col">
        <div class="card card-diplome">
          <h2 class="section-title">Diplômes & Formation</h2>
          <div class="diplome-item">
            <div class="diplome-icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="diplome-info">
              <span class="diplome-titre"><?= htmlspecialchars($medecin['diplome']) ?></span>
            </div>
          </div>
        </div>
        <!-- À propos -->
        <div class="card">
          <h2 class="section-title">À propos</h2>
          <p class="about-text">Spécialiste en pédiatrie générale avec une expertise en développement de l'enfant,
            nutrition infantile et maladies infectieuses.</p>
          <div class="tags-row tags-skills">
            <?php foreach ($competences as $c): ?>
              <span class="tag"><?= htmlspecialchars($c) ?></span>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Infos pratiques -->
        <div class="card">
          <h2 class="section-title">Informations pratiques</h2>
          <ul class="info-list">
            <li>
              <svg class="icon" viewBox="0 0 16 16" fill="none">
                <path d="M8 1.5A4.5 4.5 0 0 0 3.5 6c0 3 4.5 8.5 4.5 8.5S12.5 9 12.5 6A4.5 4.5 0 0 0 8 1.5Z"
                  stroke="#378ADD" stroke-width="1.2" />
                <circle cx="8" cy="6" r="1.5" stroke="#378ADD" stroke-width="1.2" />
              </svg>
              <?= htmlspecialchars($medecin['adresse']) ?>
            </li>
            <li>
              <svg class="icon" viewBox="0 0 16 16" fill="none">
                <rect x="2" y="3" width="12" height="11" rx="1.5" stroke="#378ADD" stroke-width="1.2" />
                <path d="M5 2v2M11 2v2M2 7h12" stroke="#378ADD" stroke-width="1.2" />
              </svg>
              <?= htmlspecialchars($medecin['horaires']) ?>
            </li>
            <?php if ($medecin['teleconsult']): ?>
              <li>
                <svg class="icon" viewBox="0 0 16 16" fill="none">
                  <rect x="2" y="3" width="12" height="9" rx="1.5" stroke="#378ADD" stroke-width="1.2" />
                  <path d="M6 14h4M8 12v2" stroke="#378ADD" stroke-width="1.2" />
                </svg>
                Téléconsultation disponible
              </li>
            <?php endif; ?>
            <?php if ($medecin['cnam']): ?>
              <li class="info-success">
                <svg class="icon" viewBox="0 0 16 16" fill="none">
                  <circle cx="8" cy="8" r="5.5" stroke="#1D9E75" stroke-width="1.2" />
                  <path d="M5.5 8l2 2 3-3" stroke="#1D9E75" stroke-width="1.2" />
                </svg>
                CNAM conventionné
              </li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Avis patients -->
        <div class="card">
          <h2 class="section-title">Avis patients</h2>
          <?php foreach ($avis as $i => $a): ?>
            <div class="review <?= $i > 0 ? 'review-border' : '' ?>">
              <div class="review-header">
                <span class="review-name"><?= htmlspecialchars($a['nom']) ?></span>
                <span class="stars"><?= etoiles($a['note']) ?></span>
              </div>
              <p class="review-text">"<?= htmlspecialchars($a['texte']) ?>"</p>
            </div>
          <?php endforeach; ?>
        </div>

      </div>

      <!-- Colonne droite -->
      <div class="col">
        <!-- Prise de RDV -->
        <div class="card-rdv-diplome">
          <div class="card card-rdv">
            <h2 class="section-title">Horaires de consultation</h2>
            <div class="horaires-consultation">
              <?php foreach ($tous_les_jours as $jour): ?>
                <div class="horaire-ligne">
                  <span class="jour-nom"><?= htmlspecialchars($jour) ?></span>
                  <span class="jour-horaire <?= empty($plages_par_jour[$jour]) ? 'ferme' : '' ?>">
                    <?php if (empty($plages_par_jour[$jour])): ?>
                      Fermé
                    <?php else: ?>
                      <?= implode(' / ', $plages_par_jour[$jour]) ?>
                    <?php endif; ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

        </div>
        <!-- Tarifs -->
        <div class="card">
          <h2 class="section-title">Tarifs</h2>
          <ul class="tarifs-list">
            <?php foreach ($tarifs as $i => $t): ?>
              <li class="tarif-item <?= $i < count($tarifs) - 1 ? 'tarif-border' : '' ?>">
                <span class="tarif-label"><?= htmlspecialchars($t['label']) ?></span>
                <span class="tarif-prix"><?= htmlspecialchars($t['prix']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
          <p class="tarif-note"><i class="fas fa-circle-check" style="color:var(--green);"></i> Remboursable CNAM selon
            convention.</p>

        </div>


      </div>

    </div>
  </div>

  <script>
    function selectSlot(el) {
      document.querySelectorAll('.slot').forEach(s => s.classList.remove('slot-selected'));
      el.classList.add('slot-selected');
      const heure = el.dataset.heure;
      const jour = el.dataset.jour;
      document.getElementById('input-creneau').value = heure;
      document.getElementById('input-jour').value = jour;
      document.getElementById('label-creneau').textContent = jour + ' à ' + heure;
      document.getElementById('confirm-zone').classList.remove('hidden');
      document.getElementById('no-selection').classList.add('hidden');
    }

    function cancelSlot() {
      document.querySelectorAll('.slot').forEach(s => s.classList.remove('slot-selected'));
      document.getElementById('input-creneau').value = '';
      document.getElementById('input-jour').value = '';
      document.getElementById('confirm-zone').classList.add('hidden');
      document.getElementById('no-selection').classList.remove('hidden');
    }
  </script>

</body>

</html>