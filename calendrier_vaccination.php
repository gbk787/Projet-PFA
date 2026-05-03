<?php
// ══════════════════════════════════════════════════════════════════════════════
//  calendrier_vaccination.php  —  TinySteps
//  Accès : Parents connectés uniquement.
//  Session attendue (définie par connexion_traitement.php) :
//    $_SESSION['user_id']   → id_parent
//    $_SESSION['user_role'] → 'parent'
// ══════════════════════════════════════════════════════════════════════════════

session_start();

// ── 1. Garde d'accès : connecté + rôle parent ────────────────────────────────
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'parent') {
    // Redirige vers la page de connexion (avec paramètre rôle pour pré-cocher)
    header("Location: connexion.php?role=parent");
    exit;
}

$parent_id = (int) $_SESSION['user_id'];

// ══════════════════════════════════════════════════════════════════════════════
//  MODE TEST — À UTILISER SEULEMENT SI LA PAGE DE CONNEXION N'EST PAS ENCORE
//  OPÉRATIONNELLE. Décommenter les deux lignes ci-dessous et commenter le bloc
//  "Garde d'accès" au-dessus.
//  ⚠️  SUPPRIMER AVANT LA MISE EN PRODUCTION.
// ──────────────────────────────────────────────────────────────────────────────
//  $_SESSION['user_id']   = 1;      // ← id_parent existant dans la BDD
//  $_SESSION['user_role'] = 'parent';
//  $parent_id = 1;
// ══════════════════════════════════════════════════════════════════════════════

$conn = new mysqli("localhost", "root", "", "tinySteps");
if ($conn->connect_error) {
    die("Erreur connexion : " . $conn->connect_error);
}

// ── 2. Données du parent connecté ────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM parent WHERE id_parent = ?");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si le parent n'existe plus en BDD (compte supprimé, etc.) → déconnexion
if (!$parent) {
    session_destroy();
    header("Location: connexion.php?role=parent");
    exit;
}

// ── 3. Bébés du parent connecté uniquement ───────────────────────────────────
$stmt2 = $conn->prepare("SELECT * FROM bebe WHERE id_parent = ? ORDER BY date_naissance DESC");
$stmt2->bind_param("i", $parent_id);
$stmt2->execute();
$res_bebes = $stmt2->get_result();
$bebes = [];
while ($b = $res_bebes->fetch_assoc())
    $bebes[] = $b;
$stmt2->close();

// ── 4. Sélection du bébé actif (GET ou premier de la liste) ──────────────────
$bebe_id = isset($_GET['bebe_id']) ? (int) $_GET['bebe_id'] : 0;

// Sécurité : vérifier que le bébé appartient bien à ce parent
if ($bebe_id) {
    $chk = $conn->prepare("SELECT id_bebe FROM bebe WHERE id_bebe = ? AND id_parent = ?");
    $chk->bind_param("ii", $bebe_id, $parent_id);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0)
        $bebe_id = 0; // Accès refusé → reset
    $chk->close();
}

if (!$bebe_id && !empty($bebes))
    $bebe_id = $bebes[0]['id_bebe'];

$bebe = null;
foreach ($bebes as $b) {
    if ($b['id_bebe'] == $bebe_id) {
        $bebe = $b;
        break;
    }
}

// ── 5. Calcul de l'âge ───────────────────────────────────────────────────────
$age_mois = 0;
if ($bebe) {
    $dob = new DateTime($bebe['date_naissance']);
    $today = new DateTime();
    $diff = $dob->diff($today);
    $age_mois = $diff->y * 12 + $diff->m;
}

// ── 6. Helpers ───────────────────────────────────────────────────────────────
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

function dateFR($d)
{
    if (!$d)
        return '—';
    return date('d/m/Y', strtotime($d));
}

function statutLabel($s)
{
    return match ($s) {
        'done' => '<i class="fas fa-check-circle"></i> Effectué',
        'upcoming' => '<i class="fas fa-clock"></i> Imminent',
        'overdue' => '<i class="fas fa-times-circle"></i> En retard',
        default => '<i class="fas fa-calendar-alt"></i> À venir'
    };
}

// ── 7. Requête vaccins ───────────────────────────────────────────────────────
$today_str = date('Y-m-d');

$sql = "
    SELECT c.age, v.nom, v.maladies,
           vac.date_prevue, vac.date_effectuee, vac.statut,
           CONCAT(p.prenom, ' ', p.nom) AS pediatre_nom
    FROM calendrier c
    JOIN vaccin v ON c.vaccin_id = v.id
    LEFT JOIN vaccination vac
        ON vac.vaccin_id = v.id AND vac.bebe_id = ?
    LEFT JOIN bebe b ON b.id_bebe = ?
    LEFT JOIN medecin p ON b.pediatre_id = p.id
    ORDER BY c.id
";
$stmt3 = $conn->prepare($sql);
$stmt3->bind_param("ii", $bebe_id, $bebe_id);
$stmt3->execute();
$result = $stmt3->get_result();

$data = [];
$all_vaccs = [];

while ($row = $result->fetch_assoc()) {
    $age = $row['age'];
    $date_prevue = $row['date_prevue'];

    // Calculer date prévue si absente de la table vaccination
    if (empty($date_prevue) && $bebe) {
        $nb_mois = ageMoisDepuisLabel($age);
        $dob2 = new DateTime($bebe['date_naissance']);
        $dob2->modify("+{$nb_mois} months");
        $date_prevue = $dob2->format('Y-m-d');
    }

    // Déterminer le statut
    if (!empty($row['date_effectuee'])) {
        $statut = 'done';
    } elseif (!empty($date_prevue) && $date_prevue < $today_str) {
        $statut = 'overdue';
    } elseif (!empty($date_prevue)) {
        $diff_days = (strtotime($date_prevue) - strtotime($today_str)) / 86400;
        $statut = $diff_days <= 30 ? 'upcoming' : 'future';
    } else {
        $statut = 'future';
    }

    $vacc = [
        'nom' => $row['nom'],
        'maladies' => $row['maladies'] ?? '',
        'age' => $age,
        'statut' => $statut,
        'date_prevue' => $date_prevue,
        'date_effectuee' => $row['date_effectuee'] ?? '',
        'medecin' => $row['pediatre_nom'] ?? 'Centre de Santé',
    ];

    $data[$age][] = $vacc;
    $all_vaccs[] = $vacc;
}
$stmt3->close();

// ── 8. Statistiques ──────────────────────────────────────────────────────────
$total = count($all_vaccs);
$done = count(array_filter($all_vaccs, fn($v) => $v['statut'] === 'done'));
$upcoming = count(array_filter($all_vaccs, fn($v) => $v['statut'] === 'upcoming'));
$overdue = count(array_filter($all_vaccs, fn($v) => $v['statut'] === 'overdue'));
$future = count(array_filter($all_vaccs, fn($v) => $v['statut'] === 'future'));
$pct = $total > 0 ? round(($done / $total) * 100) : 0;

$next_vacc = null;
foreach ($all_vaccs as $v) {
    if ($v['statut'] === 'overdue' || $v['statut'] === 'upcoming') {
        $next_vacc = $v;
        break;
    }
}

$jours = null;
if ($next_vacc && $next_vacc['date_prevue']) {
    $jours = (int) ((strtotime($next_vacc['date_prevue']) - strtotime($today_str)) / 86400);
}

$filtre = $_GET['filtre'] ?? 'all';
$next_medecin = htmlspecialchars($next_vacc['medecin'] ?? 'Centre de Santé');
$next_statut = $next_vacc['statut'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinySteps — Calendrier Vaccinal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles/calendriervaccin.css">
</head>

<body>

    <!-- ══ SIDEBAR ══ -->
    <aside class="sidebar" id="sidebar">
        <a href="home.php" class="lien">
            <div class="sidebar-header">
                <div class="logo-img">
                    <img src="images/logo1.png" alt="TinySteps Logo">
                </div>
                <h2>TinySteps</h2>
                <p style="font-size:12px;opacity:.7;margin-top:8px;">Espace parent</p>
            </div>
        </a>
        <nav class="sidebar-nav">
            <a href="accueil_parent.php"><i class="fas fa-home"></i> Accueil</a>
            <a href="profil_parent.php"><i class="fas fa-user"></i> Profil</a>
            <a href="recherche_pediatre.php"><i class="fas fa-stethoscope"></i> Recherche pédiatre</a>
            <a href="croissance_simple.php"><i class="fas fa-chart-line"></i> Courbe de croissance</a>
            <a href="calendrier_vaccination.php" class="active"><i class="fas fa-syringe"></i> Calendrier vaccinal</a>
            <a href="conseils.php"><i class="fas fa-lightbulb"></i> Conseils personnalisés</a>
        </nav>
        <div class="sidebar-footer">
            <div class="parent-info">
                <div class="parent-avatar">
                    <?= strtoupper(substr($parent['nom_prenom'] ?? 'P', 0, 1)) ?>
                </div>
                <div>
                    <div class="parent-name"><?= htmlspecialchars($parent['nom_prenom'] ?? '') ?></div>
                    <div class="parent-email"><?= htmlspecialchars($parent['email'] ?? '') ?></div>
                </div>
            </div>
            <!-- Déconnexion : détruit la session proprement -->
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <!-- ══ PAGE WRAPPER ══ -->
    <div class="page-wrapper">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <h1><i class="fas fa-syringe"></i> Calendrier Vaccinal</h1>
                <p>Programme National d'Immunisation — Tunisie 2024 · Suivi de
                    <?= htmlspecialchars($bebe['nom_prenom'] ?? 'votre bébé') ?>
                </p>
            </div>
            <div class="page-header-actions">
                <button class="header-icon-btn" title="Notifications" onclick="toggleNotif()">
                    <i class="fas fa-bell"></i>
                    <span id="notifBadge" class="notif-badge" style="display:none;">0</span>
                </button>
                <button class="header-icon-btn" title="Aide"><i class="fas fa-question-circle"></i></button>
            </div>
        </div>

        <!-- ══ PANNEAU NOTIFICATIONS ══ -->
        <div id="notif-panel">
            <div class="notif-header">
                <div>
                    <div class="notif-header-title"><i class="fas fa-bell"></i> Notifications</div>
                    <div class="notif-header-sub" id="notif-badge-txt">Chargement...</div>
                </div>
                <button class="notif-mark-all" onclick="marquerToutLu()">
                    <i class="fas fa-check-double"></i> Tout lu
                </button>
            </div>
            <div class="notif-list" id="notif-list">
                <div class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main">

            <!-- Baby Selector -->
            <?php if (!empty($bebes)): ?>
                <div class="baby-selector">
                    <span class="baby-selector-label">Bébé :</span>
                    <?php foreach ($bebes as $b): ?>
                        <a href="?bebe_id=<?= $b['id_bebe'] ?>&filtre=<?= $filtre ?>"
                            class="baby-tab <?= $b['id_bebe'] == $bebe_id ? 'active' : '' ?>">
                            <?= htmlspecialchars($b['nom_prenom']) ?>
                            <?php
                            // Badge retard pour chaque bébé
                            $n_overdue = 0;
                            if ($b['id_bebe'] == $bebe_id) {
                                $n_overdue = $overdue;
                            } else {
                                $chk_ov = $conn->prepare(
                                    "SELECT COUNT(*) AS c FROM vaccination vac
                             JOIN calendrier c ON c.vaccin_id = vac.vaccin_id
                             WHERE vac.bebe_id = ?
                               AND vac.date_effectuee IS NULL
                               AND vac.date_prevue < ?"
                                );
                                $chk_ov->bind_param("is", $b['id_bebe'], $today_str);
                                $chk_ov->execute();
                                $n_overdue = $chk_ov->get_result()->fetch_assoc()['c'] ?? 0;
                                $chk_ov->close();
                            }
                            if ($n_overdue > 0): ?>
                                <span style="display:inline-flex;align-items:center;justify-content:center;
                             background:#E74C3C;color:#fff;border-radius:50%;width:16px;height:16px;
                             font-size:10px;font-weight:700;margin-left:4px">
                                    <?= $n_overdue ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($all_vaccs)): ?>
                <div class="empty">
                    <div style="font-size:60px;margin-bottom:16px"><i class="fas fa-syringe"></i></div>
                    <h3>Aucun vaccin trouvé</h3>
                    <p>Vérifiez que le calendrier vaccinal est bien chargé dans la base de données.</p>
                </div>

            <?php else: ?>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--green-l)"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="stat-info-val" style="color:var(--green)"><?= $done ?></div>
                            <div class="stat-info-lbl">Effectués</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--amber-l)"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="stat-info-val" style="color:var(--amber)"><?= $upcoming ?></div>
                            <div class="stat-info-lbl">Imminents</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--red-l)"><i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div>
                            <div class="stat-info-val" style="color:var(--red)"><?= $overdue ?></div>
                            <div class="stat-info-lbl">En retard</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--primary-l)"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <div class="stat-info-val" style="color:var(--blue-main)"><?= $age_mois ?></div>
                            <div class="stat-info-lbl">Mois de <?= htmlspecialchars($bebe['nom_prenom'] ?? 'bébé') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Alerte prochain vaccin -->
                <?php if ($next_vacc): ?>
                    <div class="alert <?= $next_statut === 'overdue' ? 'overdue-alert' : '' ?>">
                        <div class="alert-icon">
                            <?= $next_statut === 'overdue'
                                ? '<i class="fas fa-exclamation-circle"></i>'
                                : '<i class="fas fa-exclamation-triangle"></i>' ?>
                        </div>
                        <div class="alert-text">
                            <div class="alert-label">
                                <?= $next_statut === 'overdue' ? 'Vaccin en retard' : 'Prochain vaccin urgent' ?>
                            </div>
                            <div class="alert-title">
                                <?= htmlspecialchars($next_vacc['nom']) ?> — <?= htmlspecialchars($next_vacc['age']) ?>
                            </div>
                            <div class="alert-sub">
                                <?= $next_vacc['medecin'] ? 'Dr. ' . htmlspecialchars($next_vacc['medecin']) : 'Centre de Santé de Base' ?>
                            </div>
                        </div>
                        <?php if ($jours !== null): ?>
                            <div class="alert-days">
                                <div class="alert-days-num"><?= abs($jours) ?></div>
                                <div class="alert-days-lbl"><?= $jours < 0 ? 'jours de retard' : 'jours restants' ?></div>
                            </div>
                        <?php endif; ?>
                        <button class="alert-btn" onclick="openRdvModal('<?= $next_medecin ?>')">
                            <?= $next_statut === 'overdue' ? 'Rattraper →' : 'Prendre RDV →' ?>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Contrôles + légende -->
                <div class="controls">
                    <div class="legend">
                        <div class="leg">
                            <div class="leg-dot" style="background:var(--green)"></div>Effectué
                        </div>
                        <div class="leg">
                            <div class="leg-dot" style="background:#D97706"></div>Imminent
                        </div>
                        <div class="leg">
                            <div class="leg-dot" style="background:var(--red)"></div>En retard
                        </div>
                        <div class="leg">
                            <div class="leg-dot" style="background:var(--slate)"></div>À venir
                        </div>
                    </div>
                    <div class="filters">
                        <?php
                        $filtres = ['all' => 'Tous', 'done' => 'Effectués', 'upcoming' => 'Imminents', 'overdue' => 'En retard', 'future' => 'À venir'];
                        foreach ($filtres as $key => $label): ?>
                            <a href="?bebe_id=<?= $bebe_id ?>&filtre=<?= $key ?>"
                                class="<?= $filtre === $key ? 'active' : '' ?>">
                                <?= $label ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Progression -->
                <div class="progress-wrap">
                    <div class="prog-pct"><?= $pct ?>%<br><span>complété</span></div>
                    <div class="prog-info">
                        <div class="prog-title">Progression de <?= htmlspecialchars($bebe['nom_prenom'] ?? 'bébé') ?></div>
                        <div class="prog-track">
                            <div class="prog-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                        <div class="prog-labels">
                            <span>Naissance</span>
                            <span><?= $done ?> / <?= $total ?> vaccins administrés</span>
                        </div>
                    </div>
                    <div class="prog-mini">
                        <div class="pms" style="background:var(--green-l)">
                            <div class="pms-val" style="color:var(--green)"><?= $done ?></div>
                            <div class="pms-lbl" style="color:var(--green)">Faits</div>
                        </div>
                        <div class="pms" style="background:var(--amber-l)">
                            <div class="pms-val" style="color:var(--amber)"><?= $upcoming ?></div>
                            <div class="pms-lbl" style="color:var(--amber)">Imminents</div>
                        </div>
                        <?php if ($overdue > 0): ?>
                            <div class="pms" style="background:var(--red-l)">
                                <div class="pms-val" style="color:var(--red)"><?= $overdue ?></div>
                                <div class="pms-lbl" style="color:var(--red)">Retard</div>
                            </div>
                        <?php endif; ?>
                        <div class="pms" style="background:var(--slate-l)">
                            <div class="pms-val" style="color:var(--slate)"><?= $future ?></div>
                            <div class="pms-lbl" style="color:var(--slate)">À venir</div>
                        </div>
                    </div>
                </div>

                <!-- ══ TIMELINE ══ -->
                <div class="timeline">
                    <?php
                    $modal_json = [];
                    $card_index = 0;

                    foreach ($data as $age => $vaccins):
                        $vaccins_filtres = $filtre === 'all'
                            ? $vaccins
                            : array_filter($vaccins, fn($v) => $v['statut'] === $filtre);

                        if (empty($vaccins_filtres))
                            continue;

                        $statuts_priorite = ['overdue' => 0, 'upcoming' => 1, 'done' => 2, 'future' => 3];
                        usort($vaccins_filtres, fn($a, $b) =>
                            $statuts_priorite[$a['statut']] <=> $statuts_priorite[$b['statut']]);
                        $vaccins_filtres = array_values($vaccins_filtres);
                        $statut_dominant = $vaccins_filtres[0]['statut'];
                        $count = count($vaccins_filtres);
                        ?>
                        <div class="tl-row">
                            <div class="tl-age">
                                <div class="tl-age-label"><?= htmlspecialchars($age) ?></div>
                            </div>
                            <div class="tl-dot-col">
                                <div class="tl-dot <?= $statut_dominant ?>"></div>
                            </div>
                            <div class="tl-cards">
                                <?php if ($count > 1): ?>
                                    <div class="vacc-grid"><?php endif; ?>

                                    <?php foreach ($vaccins_filtres as $v):
                                        $s = $v['statut'];
                                        $idx = $card_index++;
                                        $modal_json[$idx] = [
                                            't' => $v['nom'],
                                            's' => $v['age'],
                                            'medecin' => $v['medecin'] ?: 'Centre de Santé de Base',
                                            'r' => [
                                                ['Bébé', ($bebe['nom_prenom'] ?? '—') . ' — ' . $v['age']],
                                                ['Statut', statutLabel($s)],
                                                ['Date prévue', dateFR($v['date_prevue'])],
                                                ['Date effectuée', $v['date_effectuee'] ? dateFR($v['date_effectuee']) : '—'],
                                                ['Médecin', $v['medecin'] ?: 'Centre de Santé de Base'],
                                                ['Maladies', $v['maladies'] ?: '—'],
                                                ['Source', 'Programme National Vaccination Tunisie 2024'],
                                            ]
                                        ];
                                        ?>
                                        <div class="vacc-card <?= $s ?>" onclick="openModal(<?= $idx ?>)">
                                            <div class="vacc-top">
                                                <div>
                                                    <div class="vacc-name"><?= htmlspecialchars($v['nom']) ?></div>
                                                    <div class="vacc-sub"><?= htmlspecialchars($v['age']) ?></div>
                                                </div>
                                                <span class="status-badge <?= $s ?>"><?= statutLabel($s) ?></span>
                                            </div>
                                            <?php if ($v['maladies']): ?>
                                                <div class="vacc-tags">
                                                    <?php foreach (explode(',', $v['maladies']) as $m): ?>
                                                        <span class="vacc-tag"><?= trim(htmlspecialchars($m)) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="vacc-footer">
                                                <span class="vacc-date">
                                                    <i class="fas fa-calendar-day"></i>
                                                    <?= $s === 'done'
                                                        ? 'Administré le ' . dateFR($v['date_effectuee'])
                                                        : 'Prévu le ' . dateFR($v['date_prevue']) ?>
                                                </span>
                                                <button class="vacc-btn <?= $s ?>">
                                                    <?= match ($s) {
                                                        'done' => '✓ Détails',
                                                        'upcoming' => 'Planifier →',
                                                        'overdue' => 'Rattraper →',
                                                        default => 'Voir →'
                                                    } ?>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if ($count > 1): ?>
                                    </div><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Source -->
                <div class="source">
                    <span style="font-size:24px">🇹🇳</span>
                    <span><strong>Source :</strong> Programme National d'Immunisation — Ministère de la Santé Tunisien 2024.
                        Tous les vaccins listés sont gratuits dans les Centres de Santé de Base (CSB).
                        Consultez votre pédiatre pour un suivi personnalisé.</span>
                </div>

            <?php endif; ?>
        </div><!-- /main -->
    </div><!-- /page-wrapper -->

    <!-- ══ MODAL DÉTAIL VACCIN ══ -->
    <div class="overlay" id="overlay" onclick="if(event.target===this)this.classList.remove('open')">
        <div class="modal">
            <div class="modal-head">
                <h2 id="m-title"></h2>
                <p id="m-sub"></p>
                <button class="modal-close"
                    onclick="document.getElementById('overlay').classList.remove('open')">✕</button>
            </div>
            <div class="modal-body" id="m-body"></div>
            <div class="modal-foot">
                <button class="btn-secondary"
                    onclick="document.getElementById('overlay').classList.remove('open')">Fermer</button>
                <button class="btn-primary" onclick="openRdvFromDetail()">Prendre RDV</button>
            </div>
        </div>
    </div>

    <!-- ══ MODAL PRISE DE RDV ══ -->
    <div id="rdvModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45);
     z-index:9999; align-items:center; justify-content:center; padding:20px;">
        <div class="modal" style="max-width:480px; width:90%;">
            <div class="modal-head">
                <h2><i class="fas fa-calendar-day"></i> Prendre un rendez-vous</h2>
                <p id="rdv-doctor-label"></p>
                <button class="modal-close" onclick="closeRdvModal()">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="doctorId">
                <input type="hidden" id="doctorName">
                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Date souhaitée</label>
                    <input type="date" id="rdvDate" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Heure souhaitée</label>
                    <input type="time" id="rdvTime" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-notes-medical"></i> Motif de la consultation</label>
                    <textarea id="motif" rows="3"
                        placeholder="Décrivez brièvement le motif de la consultation..."></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn-secondary" onclick="closeRdvModal()">Annuler</button>
                <button class="btn-primary" onclick="submitRdv()">
                    <i class="fas fa-paper-plane"></i> Envoyer la demande
                </button>
            </div>
        </div>
    </div>

    <script>
        const vaccData = <?= json_encode($modal_json) ?>;
        let currentVaccIdx = null;

        // ══ MODAL DÉTAIL VACCIN ══
        function openModal(idx) {
            const d = vaccData[idx];
            if (!d) return;
            currentVaccIdx = idx;
            document.getElementById('m-title').textContent = d.t;
            document.getElementById('m-sub').textContent = d.s;
            document.getElementById('m-body').innerHTML = d.r
                .map(r => `<div class="modal-row"><span class="lbl">${r[0]}</span><span class="val">${r[1]}</span></div>`)
                .join('');
            document.getElementById('overlay').classList.add('open');
        }

        function openRdvFromDetail() {
            const medecin = currentVaccIdx !== null && vaccData[currentVaccIdx]
                ? vaccData[currentVaccIdx].medecin
                : '<?= $next_medecin ?>';
            document.getElementById('overlay').classList.remove('open');
            openRdvModal(medecin);
        }

        // ══ MODAL PRISE DE RDV ══
        function openRdvModal(doctorName) {
            const name = doctorName || 'Centre de Santé de Base';
            document.getElementById('doctorName').value = name;
            document.getElementById('rdv-doctor-label').textContent = 'Médecin : ' + name;
            document.getElementById('rdvDate').value = '';
            document.getElementById('rdvTime').value = '';
            document.getElementById('motif').value = '';
            document.getElementById('rdvModal').style.display = 'flex';
        }

        function closeRdvModal() {
            document.getElementById('rdvModal').style.display = 'none';
        }

        function submitRdv() {
            const date = document.getElementById('rdvDate').value;
            const heure = document.getElementById('rdvTime').value;
            const motif = document.getElementById('motif').value;
            const doctor = document.getElementById('doctorName').value;
            const vaccin = currentVaccIdx !== null && vaccData[currentVaccIdx]
                ? vaccData[currentVaccIdx].t : '';

            if (!date || !heure) { alert("Veuillez renseigner la date et l'heure."); return; }

            const formData = new URLSearchParams();
            formData.append('bebe_id', <?= $bebe_id ?>);
            formData.append('doctor', doctor);
            formData.append('date', date);
            formData.append('heure', heure);
            formData.append('motif', motif);
            formData.append('vaccin', vaccin);

            fetch('save_rdv.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) { alert('Rendez-vous enregistré avec succès !'); closeRdvModal(); }
                    else { alert('Erreur : ' + data.message); }
                })
                .catch(err => alert('Erreur réseau : ' + err.message));
        }

        // ══ NOTIFICATIONS ══
        function toggleNotif() {
            const panel = document.getElementById('notif-panel');
            const isOpen = panel.classList.contains('open');
            if (isOpen) { panel.classList.remove('open'); }
            else { panel.classList.add('open'); loadNotifications(); }
        }

        document.addEventListener('click', function (e) {
            const panel = document.getElementById('notif-panel');
            const cloche = document.querySelector('[onclick="toggleNotif()"]');
            if (panel.classList.contains('open')
                && !panel.contains(e.target)
                && e.target !== cloche
                && !cloche.contains(e.target)) {
                panel.classList.remove('open');
            }
        });

        function loadNotifications() {
            fetch('get_notifications_parent.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const badge = document.getElementById('notifBadge');
                    const nb = data.nb;
                    if (nb > 0) { badge.textContent = nb > 9 ? '9+' : nb; badge.style.display = 'flex'; }
                    else { badge.style.display = 'none'; }

                    document.getElementById('notif-badge-txt').textContent =
                        nb === 0 ? 'Aucune nouvelle notification' : nb + ' non lue' + (nb > 1 ? 's' : '');

                    const list = document.getElementById('notif-list');
                    const notifs = data.notifs;
                    if (!notifs || notifs.length === 0) {
                        list.innerHTML = `<div class="notif-empty"><i class="fas fa-check-circle"></i> Tout est à jour !</div>`;
                        return;
                    }
                    list.innerHTML = notifs.map(n => {
                        const isConfirme = n.type === 'rdv_confirme';
                        const iconClass = isConfirme ? 'fa-check-circle' : 'fa-times-circle';
                        const typeClass = isConfirme ? 'confirme' : 'annule';
                        const unread = n.lu == 0;
                        return `
                <div class="notif-item ${unread ? 'unread' : ''}" id="notif-item-${n.id}">
                    <div class="notif-icon ${typeClass}"><i class="fas ${iconClass}"></i></div>
                    <div class="notif-body">
                        <div class="notif-msg">${n.message}</div>
                        <div class="notif-meta"><i class="fas fa-clock" style="font-size:10px"></i> ${n.date_fr}</div>
                    </div>
                    ${unread ? `<div class="notif-unread-dot" title="Non lu" onclick="marquerLu(${n.id}, event)"></div>` : ''}
                </div>`;
                    }).join('');
                })
                .catch(err => console.error('Erreur notifications:', err));
        }

        function marquerLu(id, e) {
            e.stopPropagation();
            fetch(`get_notifications_parent.php?action=mark_read&notif_id=${id}`)
                .then(r => r.json())
                .then(res => { if (res.success) loadNotifications(); });
        }

        function marquerToutLu() {
            fetch('get_notifications_parent.php?action=mark_all_read')
                .then(r => r.json())
                .then(res => { if (res.success) loadNotifications(); });
        }

        loadNotifications();
        setInterval(loadNotifications, 30000);
    </script>

</body>

</html>
<?php $conn->close(); ?>