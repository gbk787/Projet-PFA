<?php
// ══════════════════════════════════════════════════════════════
//  dossier_patient.php — Dossier complet d'un patient
//  TinySteps · Espace Pédiatre
// ══════════════════════════════════════════════════════════════
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
  header("Location: connexion.php");
  exit;
}

require_once 'connexion_bd.php';

$id_medecin = (int) $_SESSION['user_id'];
$nom_medecin = $_SESSION['user_nom'];

// ── Bébé ciblé ────────────────────────────────────────────────
$id_bebe = isset($_GET['id_bebe']) ? (int) $_GET['id_bebe'] : 0;
if (!$id_bebe) {
  header("Location: liste_patients.php");
  exit;
}

// ── Infos bébé ────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT b.*, p.nom_prenom AS nom_parent, p.telephone, p.email AS email_parent
                         FROM bebe b
                         LEFT JOIN parent p ON b.id_parent = p.id_parent
                         WHERE b.id_bebe = ?");
$stmt->bind_param("i", $id_bebe);
$stmt->execute();
$bebe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bebe) {
  header("Location: liste_patients.php");
  exit;
}

// ── Calcul âge ────────────────────────────────────────────────
$dob = new DateTime($bebe['date_naissance']);
$today = new DateTime();
$diff = $dob->diff($today);
$age_mois = $diff->y * 12 + $diff->m;
$age_label = $diff->y > 0
  ? $diff->y . ' an' . ($diff->y > 1 ? 's' : '') . ($diff->m ? ' ' . $diff->m . ' mois' : '')
  : $diff->m . ' mois';

// ── Historique consultations ──────────────────────────────────
$consultations = [];
$res = $conn->query("
    SELECT c.*, p.contenu AS prescription
    FROM consultation c
    LEFT JOIN prescription p ON p.id_consultation = c.id_consultation
    WHERE c.id_bebe = $id_bebe
    ORDER BY c.date_consultation DESC
    LIMIT 30
");
while ($row = $res->fetch_assoc()) {
  $consultations[] = $row;
}

// ── Mesures ───────────────────────────────────────────────────
$mesures = [];
$res_m = $conn->query("
    SELECT * FROM mesure
    WHERE id_bebe = $id_bebe
    ORDER BY date_mesure ASC
");
if ($res_m) {
  while ($row = $res_m->fetch_assoc()) {
    $mesures[] = $row;
  }
}

// Dernière mesure
$last_mesure = !empty($mesures) ? end($mesures) : null;
$prev_mesure = count($mesures) >= 2 ? $mesures[count($mesures) - 2] : null;

// ── Vaccinations ──────────────────────────────────────────────
$vaccins = [];
$res_v = $conn->query("
    SELECT * FROM vaccination
    WHERE bebe_id = $id_bebe
    ORDER BY date_prevue ASC
");
if ($res_v) {
  while ($row = $res_v->fetch_assoc()) {
    $vaccins[] = $row;
  }
}

// ── Notes ─────────────────────────────────────────────────────
// Toutes les notes/observations des consultations
$notes_list = array_filter($consultations, function ($c) {
  return !empty($c['notes']) || !empty($c['observation']);
});

// ── Ordonnances récentes ──────────────────────────────────────
$ordonnances = [];
$res_o = $conn->query("
    SELECT o.*, m.nom AS nom_med, m.prenom AS prenom_med, m.ann_expr,
           cab.nom_cabinet, cab.adresse, cab.ville, cab.tel_cabin
    FROM ordonnance o
    LEFT JOIN medecin m   ON o.id_medecin = m.id
    LEFT JOIN cabinet cab  ON cab.medecin_id = m.id
    WHERE o.id_bebe = $id_bebe
    ORDER BY o.date_ord DESC
    LIMIT 10
");
if ($res_o) {
  while ($row = $res_o->fetch_assoc()) {
    $ordonnances[] = $row;
  }
}

// ── Infos médecin pour ordonnance ────────────────────────────
$med_info = null;
$stmt_med = $conn->prepare("
    SELECT m.nom, m.prenom, m.ann_expr, m.diplome,
           cab.nom_cabinet, cab.adresse, cab.ville, cab.tel_cabin
    FROM medecin m
    LEFT JOIN cabinet cab ON cab.medecin_id = m.id
    WHERE m.id = ?
");
$stmt_med->bind_param("i", $id_medecin);
$stmt_med->execute();
$med_info = $stmt_med->get_result()->fetch_assoc();
$stmt_med->close();

$conn->close();

// ── Helpers ───────────────────────────────────────────────────
function fmt_date($d)
{
  if (!$d)
    return '—';
  $ts = strtotime($d);
  $m = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
  return date('d', $ts) . ' ' . $m[date('n', $ts) - 1] . ' ' . date('Y', $ts);
}
function val2($a, $k, $def = '—')
{
  return isset($a[$k]) && $a[$k] !== '' && $a[$k] !== null ? $a[$k] : $def;
}
function trend_icon($new, $old)
{
  if ($old === null || $new === null)
    return '';
  if ($new > $old)
    return '<i class="fas fa-arrow-up trend-up"></i>';
  if ($new < $old)
    return '<i class="fas fa-arrow-down trend-down"></i>';
  return '<i class="fas fa-minus trend-eq"></i>';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dossier — <?= htmlspecialchars($bebe['nom_prenom']) ?> · TinySteps</title>
  <link rel="icon" href="images/logo1.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="styles/style_accueilP.css">
  <link rel="stylesheet" href="styles/style_dossier_patient.css?v=999">
  <!-- Chart.js pour les courbes de croissance -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    /* ── Onglet Mesures & Courbes : layout deux colonnes ── */
    .growth-main-content {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      align-items: start;
    }

    .growth-charts-section {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .growth-data-section {
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    .growth-stats-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .growth-stat-box {
      background: linear-gradient(135deg, #144474, #2b6e9e);
      color: #fff;
      padding: 18px;
      border-radius: 16px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(43, 110, 158, 0.2);
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .growth-stat-box:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(43, 110, 158, 0.3);
    }

    .growth-stat-label {
      font-size: 11px;
      opacity: 0.9;
      margin-bottom: 6px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .growth-stat-value {
      font-size: 22px;
      font-weight: 700;
    }

    @media (max-width: 1100px) {
      .growth-main-content {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

  <!-- ══════════ SIDEBAR ══════════ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo-img">
        <img src="images/logo1.png" alt="TinySteps Logo">
      </div>
      <h2>TinySteps</h2>
      <p style="font-size:12px;opacity:.7;margin-top:8px;">Espace pediatre</p>
    </div>
    <div class="doctor-badge">
      <div class="doctor-avatar">
        <?= strtoupper(substr($pediatre['nom'] ?? 'P', 0, 1)) ?>
      </div>
      <div>
        <div class="doctor-name">Dr.
          <?= htmlspecialchars($pediatre['nom'] ?? 'Pédiatre') ?>
        </div>
        <div class="doctor-role">Pédiatre</div>
      </div>
    </div>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard_pediatre.php"><i class="fas fa-home"></i> Tableau de bord</a>
      <a href="liste_patients.php" class="active"><i class="fas fa-baby"></i> Mes patients</a>
      <a href="agenda.php"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
      <a href="profil_pediatre.php"><i class="fas fa-user-md"></i> Mon profil</a>
    </nav>
    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
  </aside>

  <!-- ══════════ MAIN ══════════ -->
  <div class="main">

    <!-- Header -->
    <header class="main-header">
      <div class="header-left">
        <a href="liste_patients.php" class="back-btn" title="Retour à la liste">
          <i class="fas fa-arrow-left"></i>
        </a>
        <div class="header-patient">
          <h1><?= htmlspecialchars($bebe['nom_prenom']) ?></h1>
          <p>Dossier patient · Né(e) le <?= fmt_date($bebe['date_naissance']) ?></p>
        </div>
      </div>
      <span class="header-badge"><i class="fas fa-baby"></i> <?= $age_label ?></span>
    </header>

    <!-- Content -->
    <div class="dossier-content">

      <!-- ── Hero Patient ── -->
      <div class="patient-hero">
        <div class="hero-avatar">
          <?= $bebe['sexe'] === 'F' || $bebe['sexe'] === 'Féminin' ? '<ion-icon name="happy-outline"></ion-icon>' : '<ion-icon name="happy-outline"></ion-icon>' ?>
        </div>
        <div class="hero-info">
          <h2><?= htmlspecialchars($bebe['nom_prenom']) ?></h2>
          <div class="hero-meta">
            <span class="hero-tag"><i class="fas fa-birthday-cake"></i> <?= fmt_date($bebe['date_naissance']) ?></span>
            <span class="hero-tag"><i class="fas fa-venus-mars"></i> <?= htmlspecialchars($bebe['sexe']) ?></span>
            <?php if (!empty($bebe['lieu_naissance'])): ?>
              <span class="hero-tag"><i class="fas fa-map-marker-alt"></i>
                <?= htmlspecialchars($bebe['lieu_naissance']) ?></span>
            <?php endif; ?>
            <?php if (!empty($bebe['nom_parent'])): ?>
              <span class="hero-tag"><i class="fas fa-user"></i> Parent :
                <?= htmlspecialchars($bebe['nom_parent']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="hero-stats">
          <?php if ($last_mesure): ?>
            <div class="hero-stat">
              <div class="hero-stat-val"><?= $last_mesure['poids'] ? $last_mesure['poids'] . ' kg' : '—' ?></div>
              <div class="hero-stat-lbl">Poids</div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat-val"><?= $last_mesure['taille'] ? $last_mesure['taille'] . ' cm' : '—' ?></div>
              <div class="hero-stat-lbl">Taille</div>
            </div>
          <?php else: ?>
            <div class="hero-stat">
              <div class="hero-stat-val"><?= count($consultations) ?></div>
              <div class="hero-stat-lbl">Consultations</div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat-val"><?= count(array_filter($vaccins, fn($v) => !empty($v['date_effectuee']))) ?>
              </div>
              <div class="hero-stat-lbl">Vaccins faits</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── Onglets ── -->
      <div class="tabs-bar" id="tabs">
        <button class="tab-btn active" data-tab="historique">
          <i class="fas fa-history"></i> Historique
        </button>
        <button class="tab-btn" data-tab="nouvelle">
          <i class="fas fa-plus-circle"></i> Nouvelle consultation
        </button>
        <button class="tab-btn" data-tab="mesures">
          <i class="fas fa-ruler-vertical"></i> Mesures & Courbes
        </button>
        <button class="tab-btn" data-tab="vaccins">
          <i class="fas fa-syringe"></i> Vaccins
        </button>
        <button class="tab-btn" data-tab="notes">
          <i class="fas fa-sticky-note"></i> Notes
        </button>
        <button class="tab-btn" data-tab="ordonnance">
          <i class="fas fa-file-medical"></i> Ordonnance
        </button>
      </div>

      <!-- ══════════════════════════════════════════════════
         TAB 1 : HISTORIQUE CONSULTATIONS
    ═══════════════════════════════════════════════════ -->
      <div class="tab-panel active" id="tab-historique">
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-history"></i> Historique complet</div>
            <span class="badge badge-blue"><?= count($consultations) ?> consultation(s)</span>
          </div>

          <?php if (empty($consultations)): ?>
            <div class="empty-state">
              <i class="fas fa-notes-medical"></i>
              Aucune consultation enregistrée pour ce patient.
            </div>
          <?php else: ?>
            <div class="consult-list">
              <?php foreach ($consultations as $i => $c): ?>
                <div class="consult-item" id="ci-<?= $i ?>">
                  <div class="consult-item-head" onclick="toggleConsult('ci-<?= $i ?>')">
                    <span class="consult-date-badge">
                      <i class="fas fa-calendar"></i>
                      <?= fmt_date($c['date_consultation']) ?>
                    </span>
                    <span class="consult-motif"><?= htmlspecialchars(val2($c, 'motif', 'Consultation')) ?></span>
                    <div class="consult-measures">
                      <?php if (!empty($c['poids'])): ?>
                        <span class="measure-chip"><i class="fas fa-weight"></i> <?= $c['poids'] ?> kg</span>
                      <?php endif; ?>
                      <?php if (!empty($c['taille'])): ?>
                        <span class="measure-chip"><i class="fas fa-ruler-vertical"></i> <?= $c['taille'] ?> cm</span>
                      <?php endif; ?>
                      <?php if (!empty($c['temperature'])): ?>
                        <span class="measure-chip"><i class="fas fa-thermometer-half"></i> <?= $c['temperature'] ?>°C</span>
                      <?php endif; ?>
                    </div>
                    <i class="fas fa-chevron-down chevron"></i>
                  </div>
                  <div class="consult-body">
                    <div class="consult-grid">
                      <?php if (!empty($c['observation'])): ?>
                        <div class="consult-field">
                          <div class="consult-field-label">Observation</div>
                          <div class="consult-field-val"><?= nl2br(htmlspecialchars($c['observation'])) ?></div>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($c['diagnostic'])): ?>
                        <div class="consult-field">
                          <div class="consult-field-label">Diagnostic</div>
                          <div class="consult-field-val"><?= nl2br(htmlspecialchars($c['diagnostic'])) ?></div>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($c['notes'])): ?>
                        <div class="consult-field">
                          <div class="consult-field-label">Notes</div>
                          <div class="consult-field-val"><?= nl2br(htmlspecialchars($c['notes'])) ?></div>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($c['compte_rendu'])): ?>
                        <div class="consult-field">
                          <div class="consult-field-label">Compte-rendu</div>
                          <div class="consult-field-val"><?= nl2br(htmlspecialchars($c['compte_rendu'])) ?></div>
                        </div>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($c['prescription'])): ?>
                      <div class="prescription-box">
                        <strong><i class="fas fa-prescription"></i> Prescription</strong>
                        <?= nl2br(htmlspecialchars($c['prescription'])) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════
         TAB 2 : NOUVELLE CONSULTATION
    ═══════════════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-nouvelle">
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-plus-circle"></i> Nouvelle consultation</div>
            <span class="badge badge-blue"><?= htmlspecialchars($bebe['nom_prenom']) ?></span>
          </div>

          <form id="form-consultation" method="POST" action="save_consultation.php">
            <input type="hidden" name="id_bebe" value="<?= $id_bebe ?>">
            <input type="hidden" name="id_medecin" value="<?= $id_medecin ?>">

            <!-- Mesures -->
            <h4
              style="font-size:14px;color:var(--blue-main);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
              <i class="fas fa-ruler-combined"></i> Mesures du jour
            </h4>
            <div class="form-grid">
              <div class="form-group">
                <label>Poids (kg)</label>
                <input type="number" name="poids" step="0.01" min="0" max="50" placeholder="ex : 7.50">
              </div>
              <div class="form-group">
                <label>Taille (cm)</label>
                <input type="number" name="taille" step="0.1" min="0" max="200" placeholder="ex : 68.5">
              </div>
              <div class="form-group">
                <label>Périmètre crânien (cm)</label>
                <input type="number" name="pc" step="0.1" min="0" max="70" placeholder="ex : 42.0">
              </div>
              <div class="form-group">
                <label>Température (°C)</label>
                <input type="number" name="temperature" step="0.1" min="34" max="43" placeholder="ex : 37.2">
              </div>
            </div>

            <div class="divider"></div>

            <!-- Consultation -->
            <h4
              style="font-size:14px;color:var(--blue-main);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
              <i class="fas fa-stethoscope"></i> Informations cliniques
            </h4>
            <div class="form-grid">
              <div class="form-group full">
                <label>Motif de consultation *</label>
                <input type="text" name="motif" required placeholder="Visite de routine, fièvre, vaccin…">
              </div>
              <div class="form-group full">
                <label>Observation clinique</label>
                <textarea name="observation" placeholder="Examen clinique, comportement, signes observés…"></textarea>
              </div>
              <div class="form-group full">
                <label>Diagnostic</label>
                <textarea name="diagnostic" placeholder="Diagnostic retenu…"></textarea>
              </div>
              <div class="form-group full">
                <label>Prescription / Traitement</label>
                <textarea name="prescription" placeholder="Médicaments, posologie, durée…"></textarea>
              </div>
              <div class="form-group full">
                <label>Notes internes</label>
                <textarea name="notes" placeholder="Notes privées pour suivi…"></textarea>
              </div>
            </div>

            <div class="divider"></div>

            <!-- AI Compte-rendu -->
            <div class="ai-box">
              <div class="ai-box-head">
                <div>
                  <h4>Compte-rendu automatique (IA)</h4>
                  <p>Générez un compte-rendu professionnel basé sur les informations saisies ci-dessus</p>
                </div>
              </div>
              <div class="btn-row" style="margin-top:0;margin-bottom:14px;">
                <button type="button" class="btn btn-outline btn-sm" id="btn-ai-generate">
                  <i class="fas fa-magic"></i> Générer avec l'IA
                </button>
                <button type="button" class="btn btn-outline btn-sm" id="btn-ai-copy" style="display:none">
                  <i class="fas fa-copy"></i> Copier
                </button>
              </div>
              <div class="ai-loading" id="ai-loading">
                <div class="spinner"></div> Génération en cours…
              </div>
              <div id="ai-result"></div>
              <input type="hidden" name="compte_rendu" id="compte-rendu-hidden">
            </div>

            <div class="btn-row">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer la consultation
              </button>
              <button type="reset" class="btn btn-outline">
                <i class="fas fa-undo"></i> Réinitialiser
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════
         TAB 3 : MESURES & COURBES
    ═══════════════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-mesures">

        <!-- ── Courbes + Tableau côte à côte ── -->
        <div class="growth-main-content">

          <!-- Colonne gauche : deux courbes empilées -->
          <div class="growth-charts-section">

            <!-- Courbe Poids -->
            <div class="card growth-chart-card">
              <div class="card-header">
                <div class="card-title"><i class="fas fa-weight"></i> Courbe du Poids (kg)</div>
              </div>
              <?php if (count($mesures) < 2): ?>
                <div class="empty-state">
                  <i class="fas fa-chart-line"></i>
                  Ajoutez au moins 2 mesures pour afficher la courbe.
                </div>
              <?php else: ?>
                <div style="position:relative;height:300px;">
                  <canvas id="chartPoids"></canvas>
                </div>
              <?php endif; ?>
            </div>

            <!-- Courbe Taille -->
            <div class="card growth-chart-card">
              <div class="card-header">
                <div class="card-title"><i class="fas fa-ruler-vertical"></i> Courbe de la Taille (cm)</div>
              </div>
              <?php if (count($mesures) < 2): ?>
                <div class="empty-state">
                  <i class="fas fa-chart-line"></i>
                  Ajoutez au moins 2 mesures pour afficher la courbe.
                </div>
              <?php else: ?>
                <div style="position:relative;height:300px;">
                  <canvas id="chartTaille"></canvas>
                </div>
              <?php endif; ?>
            </div>

          </div><!-- /growth-charts-section -->

          <!-- Colonne droite : tableau + stats -->
          <div class="growth-data-section">

            <!-- Tableau des mesures -->
            <div class="card" style="margin-bottom:20px;">
              <div class="card-header">
                <div class="card-title"><i class="fas fa-table"></i> Données de croissance</div>
                <span class="badge badge-blue"><?= count($mesures) ?> mesure(s)</span>
              </div>
              <?php if (empty($mesures)): ?>
                <div class="empty-state">
                  <i class="fas fa-ruler-combined"></i> Aucune mesure enregistrée.
                </div>
              <?php else: ?>
                <div class="measure-table-wrap">
                  <table class="measure-table">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Poids (kg)</th>
                        <th>Taille (cm)</th>
                        <th>PC (cm)</th>
                        <th>IMC</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach (array_reverse($mesures) as $idx => $m):
                        $prev = $idx < count($mesures) - 1 ? array_reverse($mesures)[$idx + 1] : null;
                        ?>
                        <tr>
                          <td><?= fmt_date($m['date_mesure']) ?></td>
                          <td>
                            <?= $m['poids'] ? $m['poids'] . ' kg ' . trend_icon($m['poids'], $prev ? $prev['poids'] : null) : '—' ?>
                          </td>
                          <td>
                            <?= $m['taille'] ? $m['taille'] . ' cm ' . trend_icon($m['taille'], $prev ? $prev['taille'] : null) : '—' ?>
                          </td>
                          <td><?= $m['pc'] ? $m['pc'] . ' cm' : '—' ?></td>
                          <td>
                            <?= !empty($m['poids']) && !empty($m['taille']) ? number_format($m['poids'] / pow($m['taille'] / 100, 2), 1) : '—' ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

            <!-- Stats rapides -->
            <div class="growth-stats-grid">
              <div class="growth-stat-box">
                <div class="growth-stat-label">Poids Actuel</div>
                <div class="growth-stat-value">
                  <?= $last_mesure && $last_mesure['poids'] ? $last_mesure['poids'] . ' kg' : '—' ?>
                </div>
              </div>
              <div class="growth-stat-box">
                <div class="growth-stat-label">Taille Actuelle</div>
                <div class="growth-stat-value">
                  <?= $last_mesure && $last_mesure['taille'] ? $last_mesure['taille'] . ' cm' : '—' ?>
                </div>
              </div>
              <div class="growth-stat-box">
                <div class="growth-stat-label">Gain de Poids</div>
                <div class="growth-stat-value">
                  <?php
                  $first = !empty($mesures) ? $mesures[0] : null;
                  if ($first && $last_mesure && $first['poids'] && $last_mesure['poids'] && count($mesures) >= 2) {
                    echo '+' . number_format($last_mesure['poids'] - $first['poids'], 1) . ' kg';
                  } else {
                    echo '—';
                  }
                  ?>
                </div>
              </div>
              <div class="growth-stat-box">
                <div class="growth-stat-label">Gain de Taille</div>
                <div class="growth-stat-value">
                  <?php
                  if ($first && $last_mesure && $first['taille'] && $last_mesure['taille'] && count($mesures) >= 2) {
                    echo '+' . number_format($last_mesure['taille'] - $first['taille'], 1) . ' cm';
                  } else {
                    echo '—';
                  }
                  ?>
                </div>
              </div>
            </div>

          </div><!-- /growth-data-section -->
        </div><!-- /growth-main-content -->
      </div><!-- /tab-mesures -->

      <!-- ══════════════════════════════════════════════════
         TAB 4 : VACCINS  (Calendrier vaccinal complet)
    ═══════════════════════════════════════════════════ -->
      <?php
      // ── Données calendrier vaccinal pour ce bébé ─────────────────
      function ageMoisDepuisLabel($label)
      {
        $label = strtolower(trim($label));
        if ($label === 'naissance')
          return 0;
        if (preg_match('/^(\d+)\s*mois$/', $label, $m))
          return (int) $m[1];
        if (preg_match('/^(\d+)\s*an[s]?$/', $label, $m))
          return (int) $m[1] * 12;
        return 0;
      }
      function dateFR_cal($d)
      {
        if (!$d)
          return '—';
        return date('d/m/Y', strtotime($d));
      }
      function statutLabel_cal($s)
      {
        return match ($s) {
          'done' => '<i class="fas fa-check-circle"></i> Effectué',
          'upcoming' => '<i class="fas fa-clock"></i> Imminent',
          'overdue' => '<i class="fas fa-times-circle"></i> En retard',
          default => '<i class="fas fa-calendar-alt"></i> À venir'
        };
      }

      $today_str_cal = date('Y-m-d');
      $cal_data = [];
      $all_vaccs_cal = [];

      // Réouvrir la connexion BD (elle a été fermée plus haut)
      $conn_cal = new mysqli("localhost", "root", "", "tinysteps");
      if (!$conn_cal->connect_error) {
        $sql_cal = "
          SELECT c.age, v.id AS vaccin_id, v.nom, v.maladies,
                 vac.id AS vaccination_id,
                 vac.date_prevue, vac.date_effectuee, vac.statut,
                 vac.note_medicale
          FROM calendrier c
          JOIN vaccin v ON c.vaccin_id = v.id
          LEFT JOIN vaccination vac ON vac.vaccin_id = v.id AND vac.bebe_id = $id_bebe
          ORDER BY c.id
        ";
        $res_cal = $conn_cal->query($sql_cal);
        if ($res_cal) {
          while ($row_cal = $res_cal->fetch_assoc()) {
            $age_label_cal = $row_cal['age'];
            $date_prevue_cal = $row_cal['date_prevue'];
            if (empty($date_prevue_cal)) {
              $nb_mois_cal = ageMoisDepuisLabel($age_label_cal);
              $dob_cal = new DateTime($bebe['date_naissance']);
              $dob_cal->modify("+{$nb_mois_cal} months");
              $date_prevue_cal = $dob_cal->format('Y-m-d');
            }
            if (!empty($row_cal['date_effectuee'])) {
              $statut_cal = 'done';
            } elseif (!empty($date_prevue_cal) && $date_prevue_cal < $today_str_cal) {
              $statut_cal = 'overdue';
            } elseif (!empty($date_prevue_cal)) {
              $diff_days_cal = (strtotime($date_prevue_cal) - strtotime($today_str_cal)) / 86400;
              $statut_cal = $diff_days_cal <= 30 ? 'upcoming' : 'future';
            } else {
              $statut_cal = 'future';
            }
            $vacc_cal = [
              'vaccin_id' => $row_cal['vaccin_id'],
              'vaccination_id' => $row_cal['vaccination_id'],
              'nom' => $row_cal['nom'],
              'maladies' => $row_cal['maladies'] ?? '',
              'age' => $age_label_cal,
              'statut' => $statut_cal,
              'date_prevue' => $date_prevue_cal,
              'date_effectuee' => $row_cal['date_effectuee'] ?? '',
              'note_medicale' => $row_cal['note_medicale'] ?? '',
            ];
            $cal_data[$age_label_cal][] = $vacc_cal;
            $all_vaccs_cal[] = $vacc_cal;
          }
        }
        $conn_cal->close();
      }

      $cal_total = count($all_vaccs_cal);
      $cal_done = count(array_filter($all_vaccs_cal, fn($v) => $v['statut'] === 'done'));
      $cal_upcoming = count(array_filter($all_vaccs_cal, fn($v) => $v['statut'] === 'upcoming'));
      $cal_overdue = count(array_filter($all_vaccs_cal, fn($v) => $v['statut'] === 'overdue'));
      $cal_pct = $cal_total > 0 ? round(($cal_done / $cal_total) * 100) : 0;
      ?>

      <div class="tab-panel" id="tab-vaccins">
        <style>
          /* ── Calendrier vaccinal embedded styles ── */
          #tab-vaccins .cv-stats-row {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 18px
          }

          #tab-vaccins .cv-stat-card {
            flex: 1;
            min-width: 120px;
            background: #fff;
            border: 1px solid #e8edf5;
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .06)
          }

          #tab-vaccins .cv-stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0
          }

          #tab-vaccins .cv-stat-val {
            font-size: 22px;
            font-weight: 700;
            line-height: 1
          }

          #tab-vaccins .cv-stat-lbl {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px
          }

          #tab-vaccins .cv-alert-bar {
            display: flex;
            align-items: center;
            gap: 14px;
            background: linear-gradient(135deg, #fef2f2, #fff5f5);
            border: 1.5px solid #fca5a5;
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 18px
          }

          #tab-vaccins .cv-alert-bar.upcoming-alert {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border-color: #fcd34d
          }

          #tab-vaccins .cv-alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fecaca;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc2626;
            font-size: 17px;
            flex-shrink: 0
          }

          #tab-vaccins .cv-alert-bar.upcoming-alert .cv-alert-icon {
            background: #fde68a;
            color: #d97706
          }

          #tab-vaccins .cv-alert-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #dc2626;
            margin-bottom: 2px
          }

          #tab-vaccins .cv-alert-bar.upcoming-alert .cv-alert-label {
            color: #d97706
          }

          #tab-vaccins .cv-alert-title {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a
          }

          #tab-vaccins .cv-alert-days {
            margin-left: auto;
            text-align: center;
            background: #dc2626;
            color: #fff;
            border-radius: 10px;
            padding: 8px 14px;
            min-width: 60px
          }

          #tab-vaccins .cv-alert-bar.upcoming-alert .cv-alert-days {
            background: #d97706
          }

          #tab-vaccins .cv-alert-days-num {
            font-size: 22px;
            font-weight: 700;
            line-height: 1
          }

          #tab-vaccins .cv-alert-days-lbl {
            font-size: 10px;
            opacity: .85
          }

          #tab-vaccins .cv-progress-section {
            background: #fff;
            border: 1px solid #e8edf5;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .06)
          }

          #tab-vaccins .cv-prog-pct {
            font-size: 32px;
            font-weight: 700;
            color: #2b6e9e;
            line-height: 1;
            text-align: center;
            flex-shrink: 0
          }

          #tab-vaccins .cv-prog-pct span {
            font-size: 12px;
            color: #64748b;
            font-weight: 400
          }

          #tab-vaccins .cv-prog-info {
            flex: 1
          }

          #tab-vaccins .cv-prog-label {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px
          }

          #tab-vaccins .cv-prog-track {
            height: 10px;
            background: #e8edf5;
            border-radius: 99px;
            overflow: hidden;
            margin-bottom: 6px
          }

          #tab-vaccins .cv-prog-fill {
            height: 100%;
            background: linear-gradient(90deg, #2b6e9e, #5ba3d9);
            border-radius: 99px;
            transition: width .6s
          }

          #tab-vaccins .cv-prog-sub {
            font-size: 12px;
            color: #64748b
          }

          #tab-vaccins .cv-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px
          }

          #tab-vaccins .cv-legend {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap
          }

          #tab-vaccins .cv-leg {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #64748b
          }

          #tab-vaccins .cv-leg-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%
          }

          #tab-vaccins .cv-filters {
            display: flex;
            gap: 6px;
            flex-wrap: wrap
          }

          #tab-vaccins .cv-filters button {
            border: 1.5px solid #e8edf5;
            background: #fff;
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 500;
            color: #5a6e7c;
            cursor: pointer;
            transition: all .18s
          }

          #tab-vaccins .cv-filters button.active,
          #tab-vaccins .cv-filters button:hover {
            background: #2b6e9e;
            border-color: #2b6e9e;
            color: #fff
          }

          #tab-vaccins .cv-timeline {
            display: flex;
            flex-direction: column;
            gap: 0
          }

          #tab-vaccins .cv-tl-row {
            display: flex;
            align-items: flex-start;
            gap: 0;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9
          }

          #tab-vaccins .cv-tl-row:last-child {
            border-bottom: none
          }

          #tab-vaccins .cv-tl-age {
            width: 90px;
            flex-shrink: 0;
            padding-top: 4px
          }

          #tab-vaccins .cv-tl-age-label {
            font-size: 12px;
            font-weight: 700;
            color: #2b6e9e;
            background: #e0f0ff;
            border-radius: 20px;
            padding: 3px 10px;
            display: inline-block;
            white-space: nowrap
          }

          #tab-vaccins .cv-tl-dot-col {
            width: 32px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 6px
          }

          #tab-vaccins .cv-tl-dot {
            width: 13px;
            height: 13px;
            border-radius: 50%;
            border: 2.5px solid #fff;
            box-shadow: 0 0 0 2px currentColor
          }

          #tab-vaccins .cv-tl-dot.done {
            color: #16a34a;
            background: #16a34a
          }

          #tab-vaccins .cv-tl-dot.upcoming {
            color: #d97706;
            background: #d97706
          }

          #tab-vaccins .cv-tl-dot.overdue {
            color: #dc2626;
            background: #dc2626
          }

          #tab-vaccins .cv-tl-dot.future {
            color: #94a3b8;
            background: #94a3b8
          }

          #tab-vaccins .cv-tl-cards {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px
          }

          #tab-vaccins .cv-vacc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 10px
          }

          #tab-vaccins .cv-vacc-card {
            background: #fff;
            border: 1.5px solid #e8edf5;
            border-radius: 12px;
            padding: 13px 14px;
            border-left-width: 4px;
            transition: transform .18s, box-shadow .18s
          }

          #tab-vaccins .cv-vacc-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(15, 23, 42, .1)
          }

          #tab-vaccins .cv-vacc-card.done {
            border-left-color: #16a34a
          }

          #tab-vaccins .cv-vacc-card.upcoming {
            border-left-color: #d97706
          }

          #tab-vaccins .cv-vacc-card.overdue {
            border-left-color: #dc2626
          }

          #tab-vaccins .cv-vacc-card.future {
            border-left-color: #94a3b8
          }

          #tab-vaccins .cv-vacc-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px
          }

          #tab-vaccins .cv-vacc-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a
          }

          #tab-vaccins .cv-vacc-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 1px
          }

          #tab-vaccins .cv-status-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 20px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 4px
          }

          #tab-vaccins .cv-status-badge.done {
            background: #dcfce7;
            color: #16a34a
          }

          #tab-vaccins .cv-status-badge.upcoming {
            background: #fef3c7;
            color: #d97706
          }

          #tab-vaccins .cv-status-badge.overdue {
            background: #fef2f2;
            color: #dc2626
          }

          #tab-vaccins .cv-status-badge.future {
            background: #f1f5f9;
            color: #64748b
          }

          #tab-vaccins .cv-vacc-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-bottom: 8px
          }

          #tab-vaccins .cv-vacc-tag {
            font-size: 10px;
            background: #f0f7ff;
            color: #2b6e9e;
            border-radius: 10px;
            padding: 2px 7px
          }

          #tab-vaccins .cv-vacc-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px
          }

          #tab-vaccins .cv-vacc-date {
            font-size: 11px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 5px
          }

          #tab-vaccins .cv-vacc-actions {
            display: flex;
            gap: 6px
          }

          #tab-vaccins .cv-btn-mark-done {
            background: #2b6e9e;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 5px 11px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background .18s
          }

          #tab-vaccins .cv-btn-mark-done:hover {
            background: #1a5580
          }

          #tab-vaccins .cv-btn-detail {
            background: #f1f5f9;
            color: #5a6e7c;
            border: none;
            border-radius: 8px;
            padding: 5px 11px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: background .18s
          }

          #tab-vaccins .cv-btn-detail:hover {
            background: #e2e8f0
          }

          #tab-vaccins .cv-btn-done-label {
            font-size: 11px;
            color: #16a34a;
            font-weight: 600
          }

          #tab-vaccins .cv-source {
            font-size: 12px;
            color: #64748b;
            margin-top: 18px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px
          }

          /* modals */
          #tab-vaccins .cv-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s
          }

          #tab-vaccins .cv-overlay.open {
            opacity: 1;
            pointer-events: all
          }

          #tab-vaccins .cv-modal {
            background: #fff;
            border-radius: 18px;
            padding: 0;
            width: min(520px, 94vw);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .18);
            transform: translateY(20px);
            transition: transform .25s
          }

          #tab-vaccins .cv-overlay.open .cv-modal {
            transform: translateY(0)
          }

          #tab-vaccins .cv-modal-head {
            padding: 20px 22px 14px;
            border-bottom: 1px solid #e8edf5
          }

          #tab-vaccins .cv-modal-head h2 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 3px
          }

          #tab-vaccins .cv-modal-head p {
            font-size: 12px;
            color: #64748b;
            margin: 0
          }

          #tab-vaccins .cv-modal-close {
            position: absolute;
            top: 14px;
            right: 16px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #94a3b8;
            line-height: 1
          }

          #tab-vaccins .cv-modal-head {
            position: relative
          }

          #tab-vaccins .cv-modal-body {
            padding: 18px 22px
          }

          #tab-vaccins .cv-modal-row {
            display: flex;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px
          }

          #tab-vaccins .cv-modal-row:last-child {
            border-bottom: none
          }

          #tab-vaccins .cv-modal-row .lbl {
            color: #64748b;
            min-width: 130px;
            font-weight: 500
          }

          #tab-vaccins .cv-modal-row .val {
            color: #0f172a;
            font-weight: 600
          }

          #tab-vaccins .cv-modal-foot {
            padding: 14px 22px 18px;
            border-top: 1px solid #e8edf5;
            display: flex;
            justify-content: flex-end;
            gap: 10px
          }

          #tab-vaccins .cv-form-row {
            margin-bottom: 14px
          }

          #tab-vaccins .cv-form-row label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #5a6e7c;
            margin-bottom: 5px
          }

          #tab-vaccins .cv-form-row input,
          #tab-vaccins .cv-form-row textarea {
            width: 100%;
            border: 1.5px solid #e8edf5;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 13px;
            outline: none;
            box-sizing: border-box;
            transition: border-color .18s;
            font-family: inherit
          }

          #tab-vaccins .cv-form-row input:focus,
          #tab-vaccins .cv-form-row textarea:focus {
            border-color: #2b6e9e
          }

          #tab-vaccins .cv-btn-primary {
            background: #2b6e9e;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: background .18s
          }

          #tab-vaccins .cv-btn-primary:hover {
            background: #1a5580
          }

          #tab-vaccins .cv-btn-secondary {
            background: #f1f5f9;
            color: #5a6e7c;
            border: none;
            border-radius: 10px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .18s
          }

          #tab-vaccins .cv-btn-secondary:hover {
            background: #e2e8f0
          }

          #tab-vaccins .cv-btn-success {
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: background .18s
          }

          #tab-vaccins .cv-btn-success:hover {
            background: #15803d
          }

          #tab-vaccins .cv-toast {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: #0f172a;
            color: #fff;
            border-radius: 12px;
            padding: 12px 20px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .25);
            opacity: 0;
            transform: translateY(10px);
            transition: all .3s;
            z-index: 99999;
            pointer-events: none
          }

          #tab-vaccins .cv-toast.show {
            opacity: 1;
            transform: translateY(0)
          }

          #tab-vaccins .cv-toast.error {
            background: #dc2626
          }

          #tab-vaccins .cv-empty {
            text-align: center;
            padding: 40px;
            color: #64748b
          }

          #tab-vaccins .cv-empty i {
            font-size: 40px;
            margin-bottom: 12px;
            opacity: .4;
            display: block
          }
        </style>

        <?php if (empty($all_vaccs_cal)): ?>
          <div class="card">
            <div class="cv-empty">
              <i class="fas fa-syringe"></i>
              <p>Aucun vaccin dans le calendrier vaccinal pour ce patient.<br>
                Vérifiez que la table <code>calendrier</code> et <code>vaccin</code> sont bien renseignées.</p>
            </div>
          </div>
        <?php else: ?>

          <!-- Stats row -->
          <div class="cv-stats-row">
            <div class="cv-stat-card">
              <div class="cv-stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-check-circle"></i></div>
              <div>
                <div class="cv-stat-val" style="color:#16a34a"><?= $cal_done ?></div>
                <div class="cv-stat-lbl">Effectués</div>
              </div>
            </div>
            <div class="cv-stat-card">
              <div class="cv-stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-clock"></i></div>
              <div>
                <div class="cv-stat-val" style="color:#d97706"><?= $cal_upcoming ?></div>
                <div class="cv-stat-lbl">Imminents</div>
              </div>
            </div>
            <div class="cv-stat-card">
              <div class="cv-stat-icon" style="background:#fef2f2;color:#dc2626"><i class="fas fa-times-circle"></i></div>
              <div>
                <div class="cv-stat-val" style="color:#dc2626"><?= $cal_overdue ?></div>
                <div class="cv-stat-lbl">En retard</div>
              </div>
            </div>
            <div class="cv-stat-card">
              <div class="cv-stat-icon" style="background:#e0f0ff;color:#2b6e9e"><i class="fas fa-calendar-alt"></i></div>
              <div>
                <div class="cv-stat-val" style="color:#2b6e9e"><?= $age_mois ?></div>
                <div class="cv-stat-lbl">Mois de <?= htmlspecialchars($bebe['nom_prenom']) ?></div>
              </div>
            </div>
          </div>

          <!-- Alert bar -->
          <?php
          $first_urgent_cal = null;
          foreach ($all_vaccs_cal as $v) {
            if ($v['statut'] === 'overdue' || $v['statut'] === 'upcoming') {
              $first_urgent_cal = $v;
              break;
            }
          }
          if ($first_urgent_cal):
            $jours_cal = $first_urgent_cal['date_prevue']
              ? (int) ((strtotime($first_urgent_cal['date_prevue']) - strtotime($today_str_cal)) / 86400)
              : null;
            ?>
            <div class="cv-alert-bar <?= $first_urgent_cal['statut'] === 'upcoming' ? 'upcoming-alert' : '' ?>">
              <div class="cv-alert-icon">
                <?= $first_urgent_cal['statut'] === 'overdue' ? '<i class="fas fa-exclamation-circle"></i>' : '<i class="fas fa-exclamation-triangle"></i>' ?>
              </div>
              <div class="cv-alert-content">
                <div class="cv-alert-label">
                  <?= $first_urgent_cal['statut'] === 'overdue' ? 'Vaccin en retard' : 'Vaccin imminent' ?>
                </div>
                <div class="cv-alert-title"><?= htmlspecialchars($first_urgent_cal['nom']) ?> —
                  <?= htmlspecialchars($first_urgent_cal['age']) ?>
                </div>
                <div style="font-size:12px;color:#64748b;margin-top:2px">Patient :
                  <?= htmlspecialchars($bebe['nom_prenom']) ?> — <?= $age_mois ?> mois
                </div>
              </div>
              <?php if ($jours_cal !== null): ?>
                <div class="cv-alert-days">
                  <div class="cv-alert-days-num"><?= abs($jours_cal) ?></div>
                  <div class="cv-alert-days-lbl"><?= $jours_cal < 0 ? 'jours retard' : 'jours restants' ?></div>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- Progress -->
          <div class="cv-progress-section">
            <div class="cv-prog-pct"><?= $cal_pct ?>%<br><span>complété</span></div>
            <div class="cv-prog-info">
              <div class="cv-prog-label">Progression vaccinale de <?= htmlspecialchars($bebe['nom_prenom']) ?></div>
              <div class="cv-prog-track">
                <div class="cv-prog-fill" style="width:<?= $cal_pct ?>%"></div>
              </div>
              <div class="cv-prog-sub"><?= $cal_done ?> / <?= $cal_total ?> vaccins administrés</div>
            </div>
          </div>

          <!-- Controls + Filters -->
          <div class="cv-controls">
            <div class="cv-legend">
              <div class="cv-leg">
                <div class="cv-leg-dot" style="background:#16a34a"></div>Effectué
              </div>
              <div class="cv-leg">
                <div class="cv-leg-dot" style="background:#d97706"></div>Imminent
              </div>
              <div class="cv-leg">
                <div class="cv-leg-dot" style="background:#dc2626"></div>En retard
              </div>
              <div class="cv-leg">
                <div class="cv-leg-dot" style="background:#94a3b8"></div>À venir
              </div>
            </div>
            <div class="cv-filters">
              <?php foreach (['all' => 'Tous', 'done' => 'Effectués', 'upcoming' => 'Imminents', 'overdue' => 'En retard', 'future' => 'À venir'] as $fk => $fl): ?>
                <button class="cv-filter-btn <?= $fk === 'all' ? 'active' : '' ?>"
                  data-filter="<?= $fk ?>"><?= $fl ?></button>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Timeline -->
          <div class="cv-timeline" id="cv-timeline">
            <?php
            $cv_modal_json = [];
            $cv_card_index = 0;
            $statuts_prio = ['overdue' => 0, 'upcoming' => 1, 'done' => 2, 'future' => 3];
            foreach ($cal_data as $age_grp => $vaccins_grp):
              usort($vaccins_grp, fn($a, $b) => $statuts_prio[$a['statut']] <=> $statuts_prio[$b['statut']]);
              $vaccins_grp = array_values($vaccins_grp);
              $statut_dom = $vaccins_grp[0]['statut'];
              $grp_count = count($vaccins_grp);
              ?>
              <div class="cv-tl-row" data-age="<?= htmlspecialchars($age_grp) ?>">
                <div class="cv-tl-age">
                  <div class="cv-tl-age-label"><?= htmlspecialchars($age_grp) ?></div>
                </div>
                <div class="cv-tl-dot-col">
                  <div class="cv-tl-dot <?= $statut_dom ?>"></div>
                </div>
                <div class="cv-tl-cards">
                  <?php if ($grp_count > 1): ?>
                    <div class="cv-vacc-grid"><?php endif; ?>
                    <?php foreach ($vaccins_grp as $vc):
                      $vs = $vc['statut'];
                      $vi = $cv_card_index++;
                      $cv_modal_json[$vi] = [
                        'vaccin_id' => $vc['vaccin_id'],
                        'vaccination_id' => $vc['vaccination_id'],
                        'bebe_id' => $id_bebe,
                        'nom' => $vc['nom'],
                        'age' => $vc['age'],
                        'statut' => $vs,
                        'date_prevue' => $vc['date_prevue'],
                        'date_effectuee' => $vc['date_effectuee'],
                        'note_medicale' => $vc['note_medicale'],
                        'maladies' => $vc['maladies'],
                      ];
                      ?>
                      <div class="cv-vacc-card <?= $vs ?>" id="cv-card-<?= $vi ?>" data-statut="<?= $vs ?>">
                        <div class="cv-vacc-top">
                          <div>
                            <div class="cv-vacc-name"><?= htmlspecialchars($vc['nom']) ?></div>
                            <div class="cv-vacc-sub"><?= htmlspecialchars($vc['age']) ?></div>
                          </div>
                          <span class="cv-status-badge <?= $vs ?>"><?= statutLabel_cal($vs) ?></span>
                        </div>
                        <?php if ($vc['maladies']): ?>
                          <div class="cv-vacc-tags">
                            <?php foreach (explode(',', $vc['maladies']) as $mal): ?>
                              <span class="cv-vacc-tag"><?= trim(htmlspecialchars($mal)) ?></span>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                        <div class="cv-vacc-footer">
                          <span class="cv-vacc-date">
                            <i class="fas fa-calendar-alt"></i>
                            <?= $vs === 'done' ? 'Administré le ' . dateFR_cal($vc['date_effectuee']) : 'Prévu le ' . dateFR_cal($vc['date_prevue']) ?>
                          </span>
                          <div class="cv-vacc-actions">
                            <?php if ($vs === 'done'): ?>
                              <span class="cv-btn-done-label">✓ Administré</span>
                              <button class="cv-btn-detail" onclick="cvOpenDetail(<?= $vi ?>)">Détails</button>
                            <?php else: ?>
                              <button class="cv-btn-mark-done" onclick="cvOpenValidModal(<?= $vi ?>)"><i
                                  class="fas fa-check"></i> Marquer fait</button>
                              <button class="cv-btn-detail" onclick="cvOpenDetail(<?= $vi ?>)">Détails</button>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                    <?php if ($grp_count > 1): ?>
                    </div><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="cv-source">
            <span style="font-size:22px">🇹🇳</span>
            <span><strong>Source :</strong> Programme National d'Immunisation — Ministère de la Santé Tunisien 2024. Tous
              les vaccins sont gratuits dans les Centres de Santé de Base (CSB).</span>
          </div>

        <?php endif; ?>

        <!-- ══ MODAL DÉTAIL ══ -->
        <div class="cv-overlay" id="cv-overlay-detail" onclick="if(event.target===this)this.classList.remove('open')">
          <div class="cv-modal">
            <div class="cv-modal-head">
              <h2 id="cv-d-title"></h2>
              <p id="cv-d-sub"></p>
              <button class="cv-modal-close"
                onclick="document.getElementById('cv-overlay-detail').classList.remove('open')">✕</button>
            </div>
            <div class="cv-modal-body" id="cv-d-body"></div>
            <div class="cv-modal-foot">
              <button class="cv-btn-secondary"
                onclick="document.getElementById('cv-overlay-detail').classList.remove('open')">Fermer</button>
              <button class="cv-btn-primary" id="cv-d-btn-edit" onclick="cvSwitchToValid()"><i
                  class="fas fa-syringe"></i> Marquer administré</button>
            </div>
          </div>
        </div>

        <!-- ══ MODAL VALIDATION ══ -->
        <div class="cv-overlay" id="cv-overlay-valid" onclick="if(event.target===this)this.classList.remove('open')">
          <div class="cv-modal" style="max-width:480px">
            <div class="cv-modal-head">
              <h2><i class="fas fa-check-circle"></i> Valider l'administration</h2>
              <p>Saisissez les informations d'administration</p>
              <button class="cv-modal-close"
                onclick="document.getElementById('cv-overlay-valid').classList.remove('open')">✕</button>
            </div>
            <div class="cv-modal-body">
              <div style="background:#e0f0ff;border-radius:10px;padding:11px 14px;margin-bottom:14px">
                <div style="font-size:13px;font-weight:700;color:#2b6e9e" id="cv-v-vacc-name"></div>
                <div style="font-size:12px;color:#64748b;margin-top:2px" id="cv-v-vacc-info"></div>
              </div>
              <div class="cv-form-row">
                <label><i class="fas fa-calendar-day"></i> Date d'administration *</label>
                <input type="date" id="cv-v-date" required>
              </div>
              <div class="cv-form-row">
                <label><i class="fas fa-notes-medical"></i> Note médicale</label>
                <textarea id="cv-v-note" rows="3" placeholder="Réactions observées, remarques cliniques…"></textarea>
              </div>
            </div>
            <div class="cv-modal-foot">
              <button class="cv-btn-secondary"
                onclick="document.getElementById('cv-overlay-valid').classList.remove('open')">Annuler</button>
              <button class="cv-btn-success" onclick="cvSubmitValidation()"><i class="fas fa-check-circle"></i>
                Confirmer</button>
            </div>
          </div>
        </div>

        <!-- ══ TOAST ══ -->
        <div class="cv-toast" id="cv-toast"></div>

        <script>
          (function () {
            const cvData = <?= json_encode(array_values($cv_modal_json)) ?>;
            let cvCurrentIdx = null;

            // ── FILTRE ──
            document.querySelectorAll('.cv-filter-btn').forEach(btn => {
              btn.addEventListener('click', function () {
                document.querySelectorAll('.cv-filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const f = this.dataset.filter;
                document.querySelectorAll('#cv-timeline .cv-tl-row').forEach(row => {
                  const cards = row.querySelectorAll('.cv-vacc-card');
                  let visible = 0;
                  cards.forEach(card => {
                    const show = f === 'all' || card.dataset.statut === f;
                    card.style.display = show ? '' : 'none';
                    if (show) visible++;
                  });
                  row.style.display = visible > 0 ? '' : 'none';
                });
              });
            });

            // ── MODAL DETAIL ──
            window.cvOpenDetail = function (idx) {
              const d = cvData[idx]; if (!d) return;
              cvCurrentIdx = idx;
              document.getElementById('cv-d-title').textContent = d.nom;
              document.getElementById('cv-d-sub').textContent = d.age;
              const rows = [
                ['Patient', <?= json_encode(htmlspecialchars($bebe['nom_prenom'])) ?>],
                ['Statut', cvStatutLabel(d.statut)],
                ['Date prévue', cvDateFR(d.date_prevue)],
                ['Date administrée', d.date_effectuee ? cvDateFR(d.date_effectuee) : '—'],
                ['Note médicale', d.note_medicale || '—'],
                ['Maladies cibles', d.maladies || '—'],
              ];
              document.getElementById('cv-d-body').innerHTML = rows
                .map(r => `<div class="cv-modal-row"><span class="lbl">${r[0]}</span><span class="val">${r[1]}</span></div>`)
                .join('');
              document.getElementById('cv-d-btn-edit').style.display = d.statut === 'done' ? 'none' : 'flex';
              document.getElementById('cv-overlay-detail').classList.add('open');
            };

            // ── MODAL VALID ──
            window.cvOpenValidModal = function (idx) {
              const d = cvData[idx]; if (!d) return;
              cvCurrentIdx = idx;
              document.getElementById('cv-v-vacc-name').textContent = d.nom;
              document.getElementById('cv-v-vacc-info').textContent = d.age + ' — <?= htmlspecialchars($bebe['nom_prenom']) ?> — <?= $age_mois ?> mois';
              document.getElementById('cv-v-date').value = new Date().toISOString().split('T')[0];
              document.getElementById('cv-v-note').value = d.note_medicale || '';
              document.getElementById('cv-overlay-valid').classList.add('open');
            };

            window.cvSwitchToValid = function () {
              document.getElementById('cv-overlay-detail').classList.remove('open');
              setTimeout(() => cvOpenValidModal(cvCurrentIdx), 200);
            };

            // ── SOUMETTRE ──
            window.cvSubmitValidation = function () {
              const d = cvData[cvCurrentIdx]; if (!d) return;
              const date = document.getElementById('cv-v-date').value;
              if (!date) { cvShowToast('Veuillez saisir la date.', true); return; }
              const body = new FormData();
              body.append('bebe_id', d.bebe_id);
              body.append('vaccin_id', d.vaccin_id);
              body.append('vaccination_id', d.vaccination_id || '');
              body.append('date_effectuee', date);
              body.append('note_medicale', document.getElementById('cv-v-note').value);
              body.append('pediatre_id', <?= $id_medecin ?>);
              fetch('marquer_vaccin.php', { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                  if (res.success) {
                    document.getElementById('cv-overlay-valid').classList.remove('open');
                    cvUpdateCard(cvCurrentIdx, date);
                    cvData[cvCurrentIdx].statut = 'done';
                    cvData[cvCurrentIdx].date_effectuee = date;
                    cvShowToast('✅ Vaccin marqué comme administré !');
                  } else {
                    cvShowToast('Erreur : ' + (res.message || 'inconnue'), true);
                  }
                })
                .catch(() => cvShowToast('Erreur réseau.', true));
            };

            // ── UPDATE CARD ──
            function cvUpdateCard(idx, date) {
              const card = document.getElementById('cv-card-' + idx); if (!card) return;
              const badge = card.querySelector('.cv-status-badge');
              if (badge) { badge.className = 'cv-status-badge done'; badge.innerHTML = '<i class="fas fa-check-circle"></i> Effectué'; }
              card.className = card.className.replace(/\b(upcoming|overdue|future)\b/, 'done');
              card.dataset.statut = 'done';
              const dateEl = card.querySelector('.cv-vacc-date');
              if (dateEl) dateEl.innerHTML = '<i class="fas fa-calendar-alt"></i> Administré le ' + cvDateFR(date);
              const actions = card.querySelector('.cv-vacc-actions');
              if (actions) actions.innerHTML = `<span class="cv-btn-done-label">✓ Administré</span><button class="cv-btn-detail" onclick="cvOpenDetail(${idx})">Détails</button>`;
            }

            // ── HELPERS ──
            function cvStatutLabel(s) {
              return { done: '<i class="fas fa-check-circle"></i> Effectué', upcoming: '<i class="fas fa-clock"></i> Imminent', overdue: '<i class="fas fa-times-circle"></i> En retard', future: '<i class="fas fa-calendar-alt"></i> À venir' }[s] || s;
            }
            function cvDateFR(d) {
              if (!d) return '—';
              const [y, mo, day] = d.split('-'); return `${day}/${mo}/${y}`;
            }
            function cvShowToast(msg, error = false) {
              const t = document.getElementById('cv-toast');
              t.textContent = msg; t.className = 'cv-toast' + (error ? ' error' : '');
              setTimeout(() => t.classList.add('show'), 10);
              setTimeout(() => t.classList.remove('show'), 3500);
            }
          })();
        </script>
      </div>

      <!-- ══════════════════════════════════════════════════
         TAB 5 : NOTES
    ═══════════════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-notes">
        <div class="card" style="margin-bottom:20px;">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-plus"></i> Ajouter une note</div>
          </div>
          <form method="POST" action="save_note.php" id="form-note">
            <input type="hidden" name="id_bebe" value="<?= $id_bebe ?>">
            <input type="hidden" name="id_medecin" value="<?= $id_medecin ?>">
            <div class="form-group">
              <label>Note</label>
              <textarea name="note_content" rows="4" placeholder="Observation libre, alerte, suivi particulier…"
                required></textarea>
            </div>
            <div class="btn-row">
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-save"></i> Enregistrer la note
              </button>
            </div>
          </form>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-sticky-note"></i> Notes et observations</div>
          </div>
          <?php $has_notes = false; ?>
          <div class="notes-list">
            <?php foreach ($consultations as $c):
              if (!empty($c['notes']) || !empty($c['observation'])):
                $has_notes = true;
                ?>
                <div class="note-item">
                  <div class="note-date"><i class="fas fa-calendar-alt"></i> <?= fmt_date($c['date_consultation']) ?> —
                    <?= htmlspecialchars(val2($c, 'motif', 'Consultation')) ?>
                  </div>
                  <?php if (!empty($c['notes'])): ?>
                    <strong style="font-size:12px;color:var(--blue-main);">Note :</strong>
                    <?= nl2br(htmlspecialchars($c['notes'])) ?>
                  <?php endif; ?>
                  <?php if (!empty($c['observation'])): ?>
                    <div style="margin-top:6px;"><strong style="font-size:12px;color:var(--text-secondary);">Observation
                        :</strong>
                      <?= nl2br(htmlspecialchars($c['observation'])) ?></div>
                  <?php endif; ?>
                </div>
              <?php endif; endforeach; ?>
            <?php if (!$has_notes): ?>
              <div class="empty-state">
                <i class="fas fa-sticky-note"></i> Aucune note enregistrée.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════
         TAB 6 : ORDONNANCE
    ═══════════════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-ordonnance">
        <!-- Rédaction -->
        <div class="card" style="margin-bottom:20px;">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-file-medical-alt"></i> Rédiger une ordonnance</div>
          </div>
          <form id="form-ordonnance" method="POST" action="save_ordonnance.php">
            <input type="hidden" name="id_bebe" value="<?= $id_bebe ?>">
            <input type="hidden" name="id_medecin" value="<?= $id_medecin ?>">
            <div class="form-grid">
              <div class="form-group">
                <label>Date</label>
                <input type="date" name="date_ord" value="<?= date('Y-m-d') ?>" required>
              </div>
            </div>
            <div class="form-group full" style="margin-top:14px;">
              <label>Contenu de l'ordonnance *</label>
              <textarea id="ord-contenu" name="contenu" rows="8"
                placeholder="Médicament 1 : nom — dose — fréquence — durée&#10;Médicament 2 : …" required></textarea>
            </div>
            <div class="btn-row">
              <button type="button" class="btn btn-outline btn-sm" id="btn-ai-ord">
                <i class="fas fa-magic"></i> Aide IA pour l'ordonnance
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer l'ordonnance
              </button>
            </div>
          </form>
        </div>

        <!-- Prévisualisation / Imprimer -->
        <?php if (!empty($ordonnances)):
          $last_ord = $ordonnances[0];
          ?>
          <div class="card">
            <div class="card-header">
              <div class="card-title"><i class="fas fa-print"></i> Dernière ordonnance</div>
              <button class="btn btn-outline btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimer
              </button>
            </div>
            <div class="ordonnance-preview" id="ord-print-area">
              <div class="ordonnance-header">
                <div class="ord-dr">
                  <h3>Dr. <?= htmlspecialchars(val2($last_ord, 'prenom_med', '')) ?>
                    <?= htmlspecialchars(val2($last_ord, 'nom_med', '')) ?>
                  </h3>
                  <p>Pédiatre — <?= htmlspecialchars(val2($last_ord, 'nom_cabinet', '')) ?></p>
                  <p><?= htmlspecialchars(val2($last_ord, 'adresse', '')) ?>,
                    <?= htmlspecialchars(val2($last_ord, 'ville', '')) ?>
                  </p>
                  <?php if (!empty($last_ord['tel_cabin'])): ?>
                    <p>Tél. : <?= htmlspecialchars($last_ord['tel_cabin']) ?></p>
                  <?php endif; ?>
                </div>
                <div class="ord-logo"><i class="fa-light fa-pills"></i></div>
              </div>
              <div class="ordonnance-body">
                <p style="margin-bottom:12px;">
                  <strong>Patient :</strong> <?= htmlspecialchars($bebe['nom_prenom']) ?> &nbsp;|&nbsp;
                  <strong>Âge :</strong> <?= $age_label ?> &nbsp;|&nbsp;
                  <strong>Date :</strong> <?= fmt_date($last_ord['date_ord']) ?>
                </p>
                <div class="divider"></div>
                <?= nl2br(htmlspecialchars($last_ord['contenu'])) ?>
              </div>
              <div class="ordonnance-footer">
                <div class="signature-block">
                  <div class="signature-line"></div>
                  Signature &amp; Cachet
                </div>
              </div>
            </div>
          </div>
        <?php elseif (!empty($med_info)): ?>
          <!-- Prévisualisation dynamique (avant enregistrement) -->
          <div class="card">
            <div class="card-header">
              <div class="card-title"><i class="fas fa-eye"></i> Prévisualisation</div>
              <button class="btn btn-outline btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimer
              </button>
            </div>
            <div class="ordonnance-preview" id="ord-print-area">
              <div class="ordonnance-header">
                <div class="ord-dr">
                  <h3>Dr. <?= htmlspecialchars($med_info['prenom'] . ' ' . $med_info['nom']) ?></h3>
                  <p>Pédiatre<?= !empty($med_info['diplome']) ? ' — ' . htmlspecialchars($med_info['diplome']) : '' ?></p>
                  <?php if (!empty($med_info['nom_cabinet'])): ?>
                    <p><?= htmlspecialchars($med_info['nom_cabinet']) ?> —
                      <?= htmlspecialchars(val2($med_info, 'adresse', '')) ?>,
                      <?= htmlspecialchars(val2($med_info, 'ville', '')) ?>
                    </p>
                  <?php endif; ?>
                </div>
                <div class="ord-logo"><i class="fa-light fa-pills"></i></div>
              </div>
              <div class="ordonnance-body">
                <p><strong>Patient :</strong> <?= htmlspecialchars($bebe['nom_prenom']) ?> &nbsp;|&nbsp; <strong>Âge
                    :</strong> <?= $age_label ?> &nbsp;|&nbsp; <strong>Date :</strong> <span
                    id="ord-date-preview"><?= fmt_date(date('Y-m-d')) ?></span></p>
                <div class="divider"></div>
                <div id="ord-content-preview" style="white-space:pre-wrap;color:var(--text-secondary);font-style:italic;">
                  Le contenu de l'ordonnance s'affichera ici…</div>
              </div>
              <div class="ordonnance-footer">
                <div class="signature-block">
                  <div class="signature-line"></div>
                  Signature &amp; Cachet
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /dossier-content -->
  </div><!-- /main -->

  <div class="toast" id="toast-msg"></div>

  <!-- ══════════════════════════════════════════════════
     DONNÉES PHP → JS (courbes)
═══════════════════════════════════════════════════ -->
  <script>
    const MESURES_DATA = <?= json_encode(array_map(function ($m) {
      return [
        'date' => $m['date_mesure'],
        'poids' => $m['poids'] ? (float) $m['poids'] : null,
        'taille' => $m['taille'] ? (float) $m['taille'] : null,
        'pc' => $m['pc'] ? (float) $m['pc'] : null,
      ];
    }, $mesures)) ?>;
    const BEBE_NOM = "<?= addslashes($bebe['nom_prenom']) ?>";
  </script>

  <script>
    // ── Onglets ────────────────────────────────────────────────────
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
      });
    });

    // ── Accordéon consultations ────────────────────────────────────
    function toggleConsult(id) {
      const el = document.getElementById(id);
      el.classList.toggle('open');
    }

    // ── Toast ──────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
      const t = document.getElementById('toast-msg');
      t.textContent = msg;
      t.className = 'toast ' + type;
      setTimeout(() => t.classList.add('show'), 10);
      setTimeout(() => t.classList.remove('show'), 3500);
    }

    // ── Soumission formulaire consultation via AJAX ────────────────
    document.getElementById('form-consultation')?.addEventListener('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      fetch('save_consultation.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            showToast('Consultation enregistrée !');
            setTimeout(() => location.reload(), 1500);
          } else {
            showToast((d.message || 'Erreur'), 'error');
          }
        })
        .catch(() => showToast('❌ Erreur réseau', 'error'));
    });

    // ── Soumission mesure via AJAX ─────────────────────────────────
    document.getElementById('form-mesure')?.addEventListener('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      fetch('save_mesure.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
          if (d.success) { showToast('Mesure enregistrée !'); setTimeout(() => location.reload(), 1200); }
          else showToast((d.message || 'Erreur'), 'error');
        })
        .catch(() => showToast('Erreur réseau', 'error'));
    });

    // ── Soumission vaccin via AJAX ─────────────────────────────────
    document.getElementById('form-vaccin')?.addEventListener('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      fetch('save_vaccin.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
          if (d.success) { showToast('Vaccin enregistré !'); setTimeout(() => location.reload(), 1200); }
          else showToast((d.message || 'Erreur'), 'error');
        })
        .catch(() => showToast('Erreur réseau', 'error'));
    });

    // ── Soumission ordonnance via AJAX ─────────────────────────────
    document.getElementById('form-ordonnance')?.addEventListener('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      fetch('save_ordonnance.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
          if (d.success) { showToast('Ordonnance enregistrée !'); setTimeout(() => location.reload(), 1200); }
          else showToast((d.message || 'Erreur'), 'error');
        })
        .catch(() => showToast('Erreur réseau', 'error'));
    });

    // ── Prévisualisation ordonnance live ──────────────────────────
    const ordContenu = document.getElementById('ord-contenu');
    const ordPreview = document.getElementById('ord-content-preview');
    if (ordContenu && ordPreview) {
      ordContenu.addEventListener('input', () => {
        ordPreview.style.fontStyle = 'normal';
        ordPreview.style.color = 'var(--text-primary)';
        ordPreview.textContent = ordContenu.value || 'Le contenu de l\'ordonnance s\'affichera ici…';
      });
    }

    // ── Courbes de croissance (Poids + Taille séparées) ───────────
    let chartPoids = null;
    let chartTaille = null;

    (function buildGrowthCharts() {
      if (!MESURES_DATA.length || MESURES_DATA.length < 2) return;

      const labels = MESURES_DATA.map(m => m.date);
      const dataPoids = MESURES_DATA.map(m => m.poids);
      const dataTaille = MESURES_DATA.map(m => m.taille);

      const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 10, right: 16, bottom: 4, left: 4 } },
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0a2540',
            titleColor: '#ffffff',
            bodyColor: '#e0e7ff',
            titleFont: { size: 13, weight: '600' },
            bodyFont: { size: 12 },
            padding: 12,
            cornerRadius: 10,
            displayColors: false,
            borderColor: 'rgba(255,255,255,0.1)',
            borderWidth: 1
          }
        },
        scales: {
          x: {
            grid: { color: 'rgba(15,23,42,0.06)' },
            ticks: { font: { size: 11 }, color: '#64748b' },
            border: { color: 'rgba(15,23,42,0.1)' }
          }
        }
      };

      // ---- Courbe Poids ----
      const canvasPoids = document.getElementById('chartPoids');
      if (canvasPoids) {
        const ctxP = canvasPoids.getContext('2d');
        const gradP = ctxP.createLinearGradient(0, 0, 0, 300);
        gradP.addColorStop(0, 'rgba(43,110,158,0.28)');
        gradP.addColorStop(1, 'rgba(43,110,158,0.02)');
        if (chartPoids) chartPoids.destroy();
        chartPoids = new Chart(ctxP, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Poids',
              data: dataPoids,
              borderColor: '#2b6e9e',
              backgroundColor: gradP,
              borderWidth: 3,
              fill: true,
              tension: 0.3,
              pointRadius: 5,
              pointHoverRadius: 8,
              pointBackgroundColor: '#2b6e9e',
              pointBorderColor: '#ffffff',
              pointBorderWidth: 2
            }]
          },
          options: {
            ...commonOptions,
            plugins: {
              ...commonOptions.plugins,
              tooltip: {
                ...commonOptions.plugins.tooltip,
                callbacks: { label: ctx => `Poids : ${ctx.parsed.y} kg` }
              }
            },
            scales: {
              x: commonOptions.scales.x,
              y: {
                beginAtZero: false,
                grace: '15%',
                title: { display: true, text: 'Poids (kg)', font: { size: 12, weight: '600' }, color: '#5a6e7c' },
                ticks: { font: { size: 11 }, color: '#64748b' },
                grid: { color: 'rgba(15,23,42,0.06)' },
                border: { color: 'rgba(15,23,42,0.1)' }
              }
            }
          }
        });
      }

      // ---- Courbe Taille ----
      const canvasTaille = document.getElementById('chartTaille');
      if (canvasTaille) {
        const ctxT = canvasTaille.getContext('2d');
        const gradT = ctxT.createLinearGradient(0, 0, 0, 300);
        gradT.addColorStop(0, 'rgba(91,163,217,0.30)');
        gradT.addColorStop(1, 'rgba(91,163,217,0.02)');
        if (chartTaille) chartTaille.destroy();
        chartTaille = new Chart(ctxT, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Taille',
              data: dataTaille,
              borderColor: '#5ba3d9',
              backgroundColor: gradT,
              borderWidth: 3,
              fill: true,
              tension: 0.3,
              pointRadius: 5,
              pointHoverRadius: 8,
              pointBackgroundColor: '#5ba3d9',
              pointBorderColor: '#ffffff',
              pointBorderWidth: 2
            }]
          },
          options: {
            ...commonOptions,
            plugins: {
              ...commonOptions.plugins,
              tooltip: {
                ...commonOptions.plugins.tooltip,
                callbacks: { label: ctx => `Taille : ${ctx.parsed.y} cm` }
              }
            },
            scales: {
              x: commonOptions.scales.x,
              y: {
                beginAtZero: false,
                grace: '15%',
                title: { display: true, text: 'Taille (cm)', font: { size: 12, weight: '600' }, color: '#5a6e7c' },
                ticks: { font: { size: 11 }, color: '#64748b' },
                grid: { color: 'rgba(15,23,42,0.06)' },
                border: { color: 'rgba(15,23,42,0.1)' }
              }
            }
          }
        });
      }
    })();

    // ── IA Compte-rendu ────────────────────────────────────────────
    document.getElementById('btn-ai-generate')?.addEventListener('click', async function () {
      const motif = document.querySelector('[name="motif"]')?.value || '';
      const obs = document.querySelector('[name="observation"]')?.value || '';
      const diag = document.querySelector('[name="diagnostic"]')?.value || '';
      const poids = document.querySelector('[name="poids"]')?.value || '';
      const taille = document.querySelector('[name="taille"]')?.value || '';
      const temp = document.querySelector('[name="temperature"]')?.value || '';

      if (!motif && !obs && !diag) {
        showToast('Remplissez au moins le motif ou l\'observation.', 'error');
        return;
      }

      const loading = document.getElementById('ai-loading');
      const result = document.getElementById('ai-result');
      const copyBtn = document.getElementById('btn-ai-copy');
      loading.classList.add('visible');
      result.classList.remove('visible');
      copyBtn.style.display = 'none';

      try {
        const promptText = `Tu es un assistant médical pédiatrique. Rédige un compte-rendu de consultation professionnel en français à partir des informations suivantes. Sois concis et clinique (environ 150-200 mots).

Patient : ${BEBE_NOM}
Motif : ${motif}
Mesures : Poids ${poids || '—'} kg, Taille ${taille || '—'} cm, Température ${temp || '—'}°C
Observation : ${obs || '—'}
Diagnostic provisoire : ${diag || '—'}

Rédige le compte-rendu en paragraphes structurés.`;

        const resp = await fetch('ai_proxy.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ prompt: promptText, max_tokens: 1000 })
        });
        const data = await resp.json();
        if (data.error) throw new Error(data.error);
        const text = data.text || 'Aucun résultat.';
        result.textContent = text;
        result.classList.add('visible');
        document.getElementById('compte-rendu-hidden').value = text;
        copyBtn.style.display = 'inline-flex';
      } catch (err) {
        result.textContent = 'Erreur : ' + err.message;
        result.classList.add('visible');
        console.error('AI Error:', err);
      } finally {
        loading.classList.remove('visible');
      }
    });

    document.getElementById('btn-ai-copy')?.addEventListener('click', function () {
      const text = document.getElementById('ai-result').textContent;
      navigator.clipboard.writeText(text).then(() => showToast('Copié dans le presse-papier !'));
    });

    // ── IA Aide ordonnance ─────────────────────────────────────────
    document.getElementById('btn-ai-ord')?.addEventListener('click', async function () {
      const motif = prompt('Diagnostic / motif de l\'ordonnance ?');
      if (!motif) return;
      this.disabled = true;
      this.innerHTML = '<div class="spinner" style="width:14px;height:14px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:6px;"></div> Génération…';
      try {
        const resp = await fetch('ai_proxy.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            prompt: `Suggère une ordonnance pédiatrique type pour : "${motif}" pour un enfant de ${BEBE_NOM} (âge : à compléter). Format : liste de médicaments avec nom, dose par kg, fréquence, durée. Rappelle que c'est une suggestion à adapter par le médecin. Réponds en français, concis.`,
            max_tokens: 600
          })
        });
        const data = await resp.json();
        if (data.error) throw new Error(data.error);
        const text = data.text || '';
        if (text && document.getElementById('ord-contenu')) {
          document.getElementById('ord-contenu').value = text;
          const ev = new Event('input');
          document.getElementById('ord-contenu').dispatchEvent(ev);
        }
      } catch (e) {
        showToast('Erreur IA', 'error');
      } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-magic"></i> Aide IA pour l\'ordonnance';
      }
    });
  </script>
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>

</html>