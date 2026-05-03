<?php
// ══════════════════════════════════════
//  CONNEXION BD + SESSION
// ══════════════════════════════════════
$conn = new mysqli("localhost", "root", "", "tinySteps");
if ($conn->connect_error)
    die("Erreur connexion : " . $conn->connect_error);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header("Location: connexion.php?role=medecin");
    exit;
}
$pediatre_id = (int) $_SESSION['user_id'];

// ── Infos du pédiatre
$res_ped = $conn->query("SELECT * FROM medecin WHERE id = $pediatre_id");
$pediatre = $res_ped->fetch_assoc();

$today_str = date('Y-m-d');

// ── Jour sélectionné (clic agenda)
$selected_day = isset($_GET['jour']) ? $_GET['jour'] : $today_str;
// Sécurisation
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_day))
    $selected_day = $today_str;

// ══════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════
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
function ageFR($mois)
{
    if ($mois < 12)
        return $mois . ' mois';
    $ans = floor($mois / 12);
    $m = $mois % 12;
    return $ans . ' an' . ($ans > 1 ? 's' : '') . ($m ? ' ' . $m . ' mois' : '');
}
function heureFR($h)
{
    return $h ? substr($h, 0, 5) : '—';
}
function dateFR($d)
{
    return $d ? date('d/m/Y', strtotime($d)) : '—';
}
function dateLongFR($d)
{
    if (!$d)
        return '—';
    $jours = [
        'Monday' => 'Lundi',
        'Tuesday' => 'Mardi',
        'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi',
        'Friday' => 'Vendredi',
        'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche'
    ];
    $mois = [
        'January' => 'janvier',
        'February' => 'février',
        'March' => 'mars',
        'April' => 'avril',
        'May' => 'mai',
        'June' => 'juin',
        'July' => 'juillet',
        'August' => 'août',
        'September' => 'septembre',
        'October' => 'octobre',
        'November' => 'novembre',
        'December' => 'décembre'
    ];
    $ts = strtotime($d);
    return ($jours[date('l', $ts)] ?? '') . ' ' . date('j', $ts) . ' ' . ($mois[date('F', $ts)] ?? '') . ' ' . date('Y', $ts);
}
function statutRdvClass($s)
{
    return match ($s) { 'confirmé' => 'confirmed', 'en attente' => 'pending', 'annulé' => 'cancelled', default => 'pending'};
}

// ══════════════════════════════════════
//  1. RDV DU JOUR SÉLECTIONNÉ
// ══════════════════════════════════════
$sql_rdv = "
    SELECT rv.id, rv.heure_debut, rv.motif, rv.statut,
           b.id_bebe, b.nom_prenom AS bebe_nom, b.date_naissance,
           pa.nom_prenom AS parent_nom, pa.id_parent
    FROM rendez_vous rv
    JOIN bebe b    ON rv.bebe_id   = b.id_bebe
    JOIN parent pa ON b.id_parent  = pa.id_parent
    WHERE rv.pediatre_id = $pediatre_id
      AND rv.date_rdv    = '$selected_day'
    ORDER BY rv.heure_debut ASC
";
$res_rdv = $conn->query($sql_rdv);
$rdvs_selected = [];
while ($r = $res_rdv->fetch_assoc()) {
    $dob = new DateTime($r['date_naissance']);
    $diff = $dob->diff(new DateTime());
    $r['age_mois'] = $diff->y * 12 + $diff->m;
    $rdvs_selected[] = $r;
}
$nb_rdv_selected = count($rdvs_selected);

// RDV aujourd'hui (pour héro)
$rdvs_today = ($selected_day === $today_str) ? $rdvs_selected : [];
if ($selected_day !== $today_str) {
    $res_today = $conn->query("
        SELECT rv.*, b.nom_prenom AS bebe_nom, b.date_naissance, pa.nom_prenom AS parent_nom
        FROM rendez_vous rv
        JOIN bebe b ON rv.bebe_id = b.id_bebe
        JOIN parent pa ON b.id_parent = pa.id_parent
        WHERE rv.pediatre_id = $pediatre_id AND rv.date_rdv = '$today_str'
        ORDER BY rv.heure_debut ASC
    ");
    while ($r = $res_today->fetch_assoc())
        $rdvs_today[] = $r;
}
$nb_rdv_today = count($rdvs_today);

// Prochain RDV (aujourd'hui, non annulé)
$next_rdv = null;
foreach ($rdvs_today as $rv) {
    if ($rv['statut'] !== 'annulé') {
        $next_rdv = $rv;
        break;
    }
}

// ══════════════════════════════════════
//  2. ALERTES VACCINS EN RETARD
// ══════════════════════════════════════
$alerts = [];
$res_all_bebes = $conn->query("SELECT id_bebe, nom_prenom, date_naissance FROM bebe WHERE pediatre_id = $pediatre_id");
while ($b = $res_all_bebes->fetch_assoc()) {
    $bid = $b['id_bebe'];
    $res_v = $conn->query("
        SELECT c.age, v.id AS vaccin_id, v.nom, vac.date_prevue, vac.date_effectuee
        FROM calendrier c
        JOIN vaccin v ON c.vaccin_id = v.id
        LEFT JOIN vaccination vac ON vac.vaccin_id = v.id AND vac.bebe_id = $bid
        ORDER BY c.id
    ");
    while ($row = $res_v->fetch_assoc()) {
        if (!empty($row['date_effectuee']))
            continue;
        $date_prevue = $row['date_prevue'];
        if (empty($date_prevue)) {
            $nb_mois = ageMoisDepuisLabel($row['age']);
            $dob = new DateTime($b['date_naissance']);
            $dob->modify("+{$nb_mois} months");
            $date_prevue = $dob->format('Y-m-d');
        }
        if (!empty($date_prevue) && $date_prevue < $today_str) {
            $jours_retard = (int) ((strtotime($today_str) - strtotime($date_prevue)) / 86400);
            $alerts[] = ['id_bebe' => $bid, 'bebe_nom' => $b['nom_prenom'], 'vaccin_nom' => $row['nom'], 'date_prevue' => $date_prevue, 'jours_retard' => $jours_retard];
        }
    }
}
usort($alerts, fn($a, $b) => $b['jours_retard'] - $a['jours_retard']);
$alerts = array_slice($alerts, 0, 5);
$nb_alerts = count($alerts);

// ══════════════════════════════════════
//  3. STATS
// ══════════════════════════════════════
$nb_patients = $conn->query("SELECT COUNT(*) AS c FROM bebe WHERE pediatre_id = $pediatre_id")->fetch_assoc()['c'] ?? 0;
$nb_vaccins_done = $conn->query("SELECT COUNT(*) AS c FROM vaccination vac JOIN bebe b ON vac.bebe_id = b.id_bebe WHERE b.pediatre_id = $pediatre_id AND vac.date_effectuee IS NOT NULL")->fetch_assoc()['c'] ?? 0;
$nb_rdv_semaine = $conn->query("SELECT COUNT(*) AS c FROM rendez_vous WHERE pediatre_id = $pediatre_id AND YEARWEEK(date_rdv,1)=YEARWEEK(CURDATE(),1)")->fetch_assoc()['c'] ?? 0;
$nb_msgs = 0; // décommentez quand messagerie prête

// ══════════════════════════════════════
//  4. AGENDA 14 JOURS
// ══════════════════════════════════════
$sql_agenda = "
    SELECT DATE_FORMAT(date_rdv,'%Y-%m-%d') AS jour, COUNT(*) AS nb
    FROM rendez_vous
    WHERE pediatre_id = $pediatre_id
      AND date_rdv BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 13 DAY)
    GROUP BY jour ORDER BY jour ASC
";
$res_agenda = $conn->query($sql_agenda);
$agenda_map = [];
while ($ag = $res_agenda->fetch_assoc())
    $agenda_map[$ag['jour']] = (int) $ag['nb'];

$agenda_days = [];
for ($i = 0; $i < 14; $i++) {
    $d = date('Y-m-d', strtotime("+$i days"));
    $agenda_days[] = [
        'date' => $d,
        'label' => date('D', strtotime($d)),
        'num' => date('d', strtotime($d)),
        'nb' => $agenda_map[$d] ?? 0,
        'today' => ($d === $today_str),
        'selected' => ($d === $selected_day),
    ];
}

// ══════════════════════════════════════
//  5. VACCINS STAT
// ══════════════════════════════════════
$nb_total_poss = $conn->query("SELECT COUNT(*) AS c FROM vaccination vac JOIN bebe b ON vac.bebe_id = b.id_bebe WHERE b.pediatre_id = $pediatre_id")->fetch_assoc()['c'] ?? 0;
$pct_global = $nb_total_poss > 0 ? round(($nb_vaccins_done / $nb_total_poss) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinySteps — Tableau de bord Pédiatre</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,400&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard_pediatre.css">
</head>

<body>

    <!-- ══ MODAL ACCEPTER / REFUSER ══ -->
    <div class="overlay-rdv" id="overlay-rdv" onclick="if(event.target===this)closeRdvModal()">
        <div class="modal-rdv">
            <div class="modal-rdv-head">
                <h2 id="rdv-modal-title">Gérer le rendez-vous</h2>
                <button onclick="closeRdvModal()"
                    style="background:rgba(255,255,255,.2);border:none;cursor:pointer;font-size:16px;color:#fff;width:30px;height:30px;border-radius:8px">✕</button>
            </div>
            <div class="modal-rdv-body">
                <div class="rdv-info-box" id="rdv-info-box"></div>
                <div class="form-row" id="refus-row" style="display:none">
                    <label><i class="fas fa-comment-medical"></i> Motif du refus (optionnel)</label>
                    <textarea id="rdv-message" rows="3" placeholder="Ex : Créneau non disponible…"></textarea>
                </div>
            </div>
            <div class="modal-rdv-foot">
                <button class="btn-refuser" id="btn-refuser" onclick="confirmAction('refuser')">
                    <i class="fas fa-times-circle"></i> Refuser
                </button>
                <button class="btn-accepter" onclick="confirmAction('accepter')">
                    <i class="fas fa-check-circle"></i> Accepter
                </button>
            </div>
        </div>
    </div>

    <!-- ══ SIDEBAR ══ -->
    <aside class="sidebar" id="sidebar">
        <a href="home.php" class="sidebar-logo-link">
            <div class="sidebar-header">
                <div class="logo-img">
                    <img src="images/logo1.png" alt="TinySteps Logo">
                </div>
                <h2>TinySteps</h2>
                <p style="font-size:12px;opacity:.7;margin-top:8px;">Espace pediatre</p>
            </div>
        </a>
        <nav class="sidebar-nav">
            <a href="dashboard_pediatre.php" class="active"><i class="fas fa-home"></i> Tableau de bord</a>
            <a href="liste_patients.php"><i class="fas fa-baby"></i> Mes patients</a>
            <a href="agenda.php"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
            <a href="profil_pediatre.php"><i class="fas fa-user-md"></i> Mon profil</a>
        </nav>
        <div class="sidebar-footer">
            <div class="doctor-badge">
                <div class="doctor-avatar">
                    <?= strtoupper(substr($pediatre['prenom'] ?? $pediatre['nom'] ?? 'P', 0, 1)) ?>
                </div>
                <div>
                    <a href="profil_pediatre.php" class="doctor-name" style="text-decoration:none;color:inherit;">
                        Dr.
                        <?= htmlspecialchars(($pediatre['prenom'] ?? '') . ' ' . ($pediatre['nom'] ?? 'Pédiatre')) ?>
                    </a>
                    <div class="doctor-role" style="font-size:11px;opacity:0.75;margin-top:2px;">
                        <?= htmlspecialchars($pediatre['email'] ?? 'Pédiatre') ?>
                    </div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
    </aside>



    <!-- ══ PAGE WRAPPER ══ -->
    <div class="page-wrapper">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Tableau de bord</h1>
                <p>Bonjour Dr. <?= htmlspecialchars($pediatre['nom'] ?? '') ?> — <?= dateLongFR($today_str) ?></p>
            </div>
            <div class="topbar-right">
                <span class="today-pill"><i class="fas fa-calendar-day"></i> Aujourd'hui</span>

                <!-- Notifications RDV en attente -->
                <div class="notif-wrapper">
                    <button class="icon-btn" id="btn-notif" title="Demandes de RDV">
                        <i class="fas fa-bell"></i>
                        <span class="badge-notif" id="notif-count" style="display:none">0</span>
                    </button>
                    <div class="notif-panel" id="notif-panel">
                        <div class="notif-panel-head">
                            <span><i class="fas fa-calendar-clock"></i> Demandes de RDV</span>
                            <span class="notif-panel-badge" id="notif-badge-txt">0 en attente</span>
                        </div>
                        <div class="notif-panel-body" id="notif-list">
                            <div class="notif-empty"><i class="fas fa-check-circle"></i>Aucune demande en attente</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main -->
        <div class="main">

            <!-- Hero -->
            <div class="hero">
                <div class="hero-text">
                    <h2>Bienvenue, Dr. <?= htmlspecialchars($pediatre['prenom'] ?? $pediatre['nom'] ?? 'Docteur') ?>
                    </h2>
                    <p>Vous avez <strong><?= $nb_rdv_today ?> rendez-vous</strong> aujourd'hui
                        <?php if ($nb_alerts > 0): ?>et <strong><?= $nb_alerts ?> alerte<?= $nb_alerts > 1 ? 's' : '' ?>
                                vaccin</strong><?php endif; ?>.
                    </p>
                </div>
                <?php if ($next_rdv): ?>
                    <div class="hero-next">
                        <div class="hero-next-label">Prochain patient</div>
                        <div class="hero-next-time"><?= heureFR($next_rdv['heure_debut']) ?></div>
                        <div class="hero-next-name"><?= htmlspecialchars($next_rdv['bebe_nom']) ?></div>
                    </div>
                <?php else: ?>
                    <div class="hero-next">
                        <div class="hero-next-label">Agenda du jour</div>
                        <div class="hero-next-time" style="font-size:1.1rem">Libre</div>
                        <div class="hero-next-name">Aucun RDV prévu</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--blue-light)"><i class="fas fa-baby"
                            style="color:var(--blue-main)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--blue-main)"><?= $nb_patients ?></div>
                        <div class="stat-lbl">Total patients</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--green-l)"><i class="fas fa-calendar-check"
                            style="color:var(--green)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--green)"><?= $nb_rdv_today ?></div>
                        <div class="stat-lbl">RDV aujourd'hui</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--amber-l)"><i class="fas fa-calendar-week"
                            style="color:var(--amber)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--amber)"><?= $nb_rdv_semaine ?></div>
                        <div class="stat-lbl">RDV cette semaine</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--red-l)"><i class="fas fa-exclamation-triangle"
                            style="color:var(--red)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--red)"><?= $nb_alerts ?></div>
                        <div class="stat-lbl">Alertes vaccins</div>
                    </div>
                </div>
            </div>

            <!-- Patients jour sélectionné + Alertes -->
            <div class="grid-2">

                <!-- Patients du jour sélectionné -->
                <div class="card">
                    <div class="card-head">
                        <div class="card-title">
                            <i class="fas fa-calendar-day"></i>
                            Patients — <?= $selected_day === $today_str ? "Aujourd'hui" : dateFR($selected_day) ?>
                        </div>
                        <span class="card-badge"><?= $nb_rdv_selected ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rdvs_selected)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                Aucun rendez-vous ce jour
                            </div>
                        <?php else:
                            foreach ($rdvs_selected as $rv): ?>
                                <div class="patient-row"
                                    onclick="window.location='mes_patients.php?bebe_id=<?= $rv['id_bebe'] ?>'">
                                    <div class="p-time"><?= heureFR($rv['heure_debut']) ?></div>
                                    <div class="p-avatar"><?= strtoupper(substr($rv['bebe_nom'], 0, 1)) ?></div>
                                    <div class="p-info">
                                        <div class="p-name"><?= htmlspecialchars($rv['bebe_nom']) ?></div>
                                        <div class="p-sub"><?= ageFR($rv['age_mois']) ?> —
                                            <?= htmlspecialchars($rv['parent_nom']) ?>         <?php if ($rv['motif']): ?> ·
                                                <?= htmlspecialchars(mb_substr($rv['motif'], 0, 28)) ?>…<?php endif; ?>
                                        </div>
                                    </div>
                                    <span
                                        class="p-status <?= statutRdvClass($rv['statut']) ?>"><?= ucfirst($rv['statut'] ?? 'en attente') ?></span>
                                </div>
                            <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Alertes vaccins -->
                <div class="card">
                    <div class="card-head">
                        <div class="card-title"><i class="fas fa-syringe"></i> Alertes vaccins</div>
                        <?php if ($nb_alerts > 0): ?>
                            <span class="card-badge red"><?= $nb_alerts ?> en retard</span>
                        <?php else: ?>
                            <span class="card-badge" style="background:var(--green-l);color:var(--green)">Tout à jour
                                ✓</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($alerts)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle" style="color:var(--green);opacity:.5"></i>
                                Aucun vaccin en retard
                            </div>
                        <?php else:
                            foreach ($alerts as $al): ?>
                                <a href="calendriervaccin_pediatre.php?bebe_id=<?= $al['id_bebe'] ?>" class="alert-row">
                                    <div class="alert-dot"></div>
                                    <div class="alert-info">
                                        <div class="alert-bebe"><?= htmlspecialchars($al['bebe_nom']) ?></div>
                                        <div class="alert-vaccin"><?= htmlspecialchars($al['vaccin_nom']) ?> — prévu le
                                            <?= dateFR($al['date_prevue']) ?>
                                        </div>
                                    </div>
                                    <div class="alert-days"><?= $al['jours_retard'] ?>j retard</div>
                                </a>
                            <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- Agenda + Colonne droite -->
            <div class="grid-bottom">

                <!-- Agenda cliquable 14 jours -->
                <div class="card card-agenda">
                    <div class="card-head">
                        <div class="card-title"><i class="fas fa-calendar-week"></i> Agenda — 14 jours</div>
                        <a href="rendez_vous.php"
                            style="font-size:12px;color:var(--blue-main);text-decoration:none;font-weight:700">Tout voir
                            →</a>
                    </div>
                    <div class="card-body">
                        <!-- Jours cliquables -->
                        <div class="agenda-scroll">
                            <?php foreach ($agenda_days as $day): ?>
                                <a href="dashboard_pediatre.php?jour=<?= $day['date'] ?>"
                                    class="agenda-day <?= $day['today'] ? 'today' : '' ?> <?= $day['selected'] ? 'selected' : '' ?>"
                                    title="<?= dateLongFR($day['date']) ?>">
                                    <div class="day-label"><?= $day['label'] ?></div>
                                    <div class="day-num"><?= $day['num'] ?></div>
                                    <div class="day-count"><?= $day['nb'] > 0 ? $day['nb'] . ' rdv' : '—' ?></div>
                                    <?php if ($day['nb'] > 0 && !$day['selected']): ?>
                                        <div class="day-bar"></div><?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <!-- RDV du jour sélectionné -->
                        <div class="rdv-mini">
                            <div class="rdv-mini-head">
                                <span><?= $selected_day === $today_str ? "Rendez-vous d'aujourd'hui" : "Rendez-vous du " . dateFR($selected_day) ?></span>
                                <span style="color:var(--blue-main)"><?= $nb_rdv_selected ?> rdv</span>
                            </div>
                            <?php if (empty($rdvs_selected)): ?>
                                <div style="padding:16px;font-size:12px;color:var(--muted);text-align:center">Aucun
                                    rendez-vous ce jour.</div>
                            <?php else:
                                foreach (array_slice($rdvs_selected, 0, 6) as $rv): ?>
                                    <div class="rdv-mini-row"
                                        onclick="window.location='mes_patients.php?bebe_id=<?= $rv['id_bebe'] ?>'"
                                        style="cursor:pointer">
                                        <div class="rdv-mini-time"><?= heureFR($rv['heure_debut']) ?></div>
                                        <div class="rdv-mini-name"><?= htmlspecialchars($rv['bebe_nom']) ?></div>
                                        <div class="rdv-mini-motif">
                                            <?= htmlspecialchars(mb_substr($rv['motif'] ?? '', 0, 24)) ?>
                                        </div>
                                        <span class="p-status <?= statutRdvClass($rv['statut']) ?>"
                                            style="font-size:10px"><?= ucfirst($rv['statut'] ?? '') ?></span>
                                    </div>
                                <?php endforeach;
                                if ($nb_rdv_selected > 6): ?>
                                    <div style="padding:8px 16px;font-size:11px;color:var(--muted)">+
                                        <?= $nb_rdv_selected - 6 ?> autres rendez-vous
                                    </div>
                                <?php endif; endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Colonne droite -->
                <div class="right-col">

                    <!-- Vaccins administrés -->
                    <div class="card">
                        <div class="card-head">
                            <div class="card-title"><i class="fas fa-check-circle"></i> Vaccins administrés</div>
                        </div>
                        <div class="card-body">
                            <div class="vacc-stat-title">Total administrés (tous patients)</div>
                            <div class="vacc-num"><?= $nb_vaccins_done ?> <span>doses</span></div>
                            <div class="prog-track">
                                <div class="prog-fill" style="width:<?= $pct_global ?>%"></div>
                            </div>
                            <div class="prog-sub"><?= $pct_global ?>% complété · <?= $nb_patients ?> patients</div>
                        </div>
                    </div>

                    <!-- Actions rapides -->
                    <div class="card">
                        <div class="card-head">
                            <div class="card-title"><i class="fas fa-bolt"></i> Actions rapides</div>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="mes_patients.php" class="qa-btn"><i class="fas fa-baby"></i> Mes patients</a>
                                <a href="calendriervaccin_pediatre.php" class="qa-btn"><i class="fas fa-syringe"></i>
                                    Calendrier vaccinal</a>
                                <a href="rendez_vous.php" class="qa-btn"><i class="fas fa-calendar-plus"></i>
                                    Rendez-vous</a>
                                <a href="messagerie.php" class="qa-btn"><i class="fas fa-paper-plane"></i>
                                    Messagerie</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div><!-- /main -->
    </div><!-- /page-wrapper -->

    <div class="toast-rdv" id="toast-rdv-el"></div>

    <script>
        let currentRdvId = null;

        // ══════════════════════════════════════
        //  NOTIFICATIONS
        // ══════════════════════════════════════
        loadNotifications();
        setInterval(loadNotifications, 30000);

        function loadNotifications() {
            fetch('get_notifications_pediatre.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const nb = data.nb;
                    const countEl = document.getElementById('notif-count');
                    const badgeTxt = document.getElementById('notif-badge-txt');

                    if (nb > 0) { countEl.textContent = nb; countEl.style.display = 'flex'; countEl.style.animation = 'pulse 1.5s infinite'; }
                    else { countEl.style.display = 'none'; }
                    badgeTxt.textContent = nb + ' en attente';

                    const list = document.getElementById('notif-list');
                    if (nb === 0) {
                        list.innerHTML = '<div class="notif-empty"><i class="fas fa-check-circle"></i>Aucune demande en attente</div>';
                        return;
                    }
                    list.innerHTML = data.rdvs.map(rdv => {
                        const age = ageMoisFR(rdv.age_mois);
                        const dateStr = rdv.date_rdv.split('-').reverse().join('/');
                        const heure = rdv.heure_debut ? rdv.heure_debut.substring(0, 5) : '';
                        return `
                <div class="notif-item" id="notif-rdv-${rdv.id}">
                    <div class="notif-item-top">
                        <div>
                            <div class="notif-bebe"><i class="fas fa-baby" style="font-size:10px;margin-right:4px"></i>${rdv.bebe_nom}</div>
                            <div class="notif-parent">Parent : ${rdv.parent_nom} · ${age}</div>
                        </div>
                        <div class="notif-date-pill">${dateStr} à ${heure}</div>
                    </div>
                    ${rdv.motif ? `<div class="notif-motif"><i class="fas fa-notes-medical" style="font-size:10px"></i> ${rdv.motif}</div>` : ''}
                    <div class="notif-actions">
                        <button class="btn-notif-refuse" onclick="openRdvModal(${rdv.id},'${rdv.bebe_nom}','${rdv.parent_nom}','${dateStr}','${heure}',event)">
                            <i class="fas fa-times"></i> Refuser
                        </button>
                        <button class="btn-notif-accept" onclick="actionDirecte(${rdv.id},'accepter',event)">
                            <i class="fas fa-check"></i> Accepter
                        </button>
                    </div>
                </div>`;
                    }).join('');
                })
                .catch(err => console.error('Erreur notifications:', err));
        }

        // Toggle panneau
        document.getElementById('btn-notif').addEventListener('click', function (e) {
            e.stopPropagation();
            document.getElementById('notif-panel').classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            const panel = document.getElementById('notif-panel');
            if (!panel.contains(e.target) && e.target.id !== 'btn-notif')
                panel.classList.remove('open');
        });

        // ══════════════════════════════════════
        //  ACTIONS RDV
        // ══════════════════════════════════════
        function actionDirecte(rdvId, action, e) {
            e.stopPropagation();
            sendAction(rdvId, action, '');
        }

        function openRdvModal(rdvId, bebeNom, parentNom, date, heure, e) {
            if (e) e.stopPropagation();
            currentRdvId = rdvId;
            document.getElementById('rdv-modal-title').textContent = 'Gérer le rendez-vous';
            document.getElementById('rdv-info-box').innerHTML = `
        <strong>Patient :</strong> ${bebeNom}<br>
        <strong>Parent :</strong> ${parentNom}<br>
        <strong>Date :</strong> ${date} à ${heure}`;
            document.getElementById('rdv-message').value = '';
            document.getElementById('refus-row').style.display = 'none';
            document.getElementById('btn-refuser').dataset.confirm = '';
            document.getElementById('btn-refuser').innerHTML = '<i class="fas fa-times-circle"></i> Refuser';
            document.getElementById('overlay-rdv').classList.add('open');
        }

        function confirmAction(action) {
            if (action === 'refuser') {
                document.getElementById('refus-row').style.display = 'block';
                if (document.getElementById('btn-refuser').dataset.confirm === '1') {
                    sendAction(currentRdvId, 'refuser', document.getElementById('rdv-message').value);
                    closeRdvModal(); return;
                }
                document.getElementById('btn-refuser').dataset.confirm = '1';
                document.getElementById('btn-refuser').innerHTML = '<i class="fas fa-times-circle"></i> Confirmer le refus';
            } else {
                sendAction(currentRdvId, 'accepter', '');
                closeRdvModal();
            }
        }

        function closeRdvModal() {
            document.getElementById('overlay-rdv').classList.remove('open');
            currentRdvId = null;
        }

        function sendAction(rdvId, action, message) {
            const body = new FormData();
            body.append('rdv_id', rdvId);
            body.append('action', action);
            body.append('message', message);
            fetch('gerer_rdv.php', { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const el = document.getElementById('notif-rdv-' + rdvId);
                        if (el) { el.style.transition = 'all .3s'; el.style.opacity = '0'; el.style.maxHeight = '0'; setTimeout(() => el.remove(), 300); }
                        showToast(action === 'accepter' ? '✅ RDV confirmé !' : '❌ RDV refusé.', action === 'accepter' ? 'success' : 'error');
                        setTimeout(() => { loadNotifications(); location.reload(); }, 1000);
                    } else {
                        showToast('Erreur : ' + (res.message || 'inconnue'), 'error');
                    }
                })
                .catch(() => showToast('Erreur réseau.', 'error'));
        }

        // ══════════════════════════════════════
        //  HELPERS
        // ══════════════════════════════════════
        function ageMoisFR(m) {
            if (m < 12) return m + ' mois';
            const ans = Math.floor(m / 12), mo = m % 12;
            return ans + ' an' + (ans > 1 ? 's' : '') + (mo ? ' ' + mo + ' mois' : '');
        }
        function showToast(msg, type = 'success') {
            let t = document.getElementById('toast-rdv-el');
            t.textContent = msg;
            t.className = 'toast-rdv ' + type;
            setTimeout(() => t.classList.add('show'), 10);
            setTimeout(() => t.classList.remove('show'), 3500);
        }
    </script>

</body>

</html>
<?php $conn->close(); ?>