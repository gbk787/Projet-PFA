<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tinysteps");
$conn->set_charset("utf8mb4");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// ── Actions POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)$_POST['id'];
    $action = $_POST['action'];
    $redirect_params = $_POST['redirect_params'] ?? '';

    if ($action === 'confirmer') {
        $conn->query("UPDATE rendez_vous SET statut = 'confirme' WHERE id = $id");
        // Mettre à jour pediatre_id dans bebe
        $rdvRow = $conn->query("SELECT bebe_id, pediatre_id FROM rendez_vous WHERE id = $id")->fetch_assoc();
        if ($rdvRow) {
            $bebe_id_upd     = (int) $rdvRow['bebe_id'];
            $pediatre_id_upd = (int) $rdvRow['pediatre_id'];
            $conn->query("UPDATE bebe SET pediatre_id = $pediatre_id_upd WHERE id_bebe = $bebe_id_upd");
        }
    } elseif ($action === 'annuler') {
        $conn->query("UPDATE rendez_vous SET statut = 'annule' WHERE id = $id");
    } elseif ($action === 'supprimer') {
        $conn->query("DELETE FROM rendez_vous WHERE id = $id");
    }

    header("Location: ad_rendezvous.php" . ($redirect_params ? "?$redirect_params" : ''));
    exit();
}

// ── Contexte (filtre depuis bébé ou dashboard) ────────────────────────────────
$bebe_id   = (int)($_GET['bebe_id']   ?? 0);
$parent_id = (int)($_GET['parent_id'] ?? 0);
$filtre    = $_GET['filtre']  ?? 'tous';
$search    = trim($_GET['search'] ?? '');

// Reconstruit les params de redirection
$redirect_params = http_build_query(array_filter([
    'bebe_id'   => $bebe_id   ?: null,
    'parent_id' => $parent_id ?: null,
    'filtre'    => $filtre !== 'tous' ? $filtre : null,
]));

// Contexte bébé
$bebeRow = null;
if ($bebe_id) {
    $bebeRow = $conn->query("
        SELECT b.*, p.nom_prenom AS nom_parent
        FROM bebe b
        LEFT JOIN parent p ON b.id_parent = p.id_parent
        WHERE b.id_bebe = $bebe_id
    ")->fetch_assoc();
}

// ── Construction requête ──────────────────────────────────────────────────────
$where = [];
if ($bebe_id)   $where[] = "r.bebe_id = $bebe_id";
if ($parent_id) $where[] = "b.id_parent = $parent_id";
if ($filtre === 'en_attente') $where[] = "r.statut = 'en_attente'";
if ($filtre === 'confirme')   $where[] = "r.statut = 'confirme'";
if ($filtre === 'annule')     $where[] = "r.statut = 'annule'";
if ($filtre === 'effectue')   $where[] = "r.statut = 'effectue'";
if ($search !== '') $where[] = "(b.nom_prenom LIKE '%$search%' OR p.nom_prenom LIKE '%$search%' OR CONCAT(m.prenom,' ',m.nom) LIKE '%$search%')";

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

$rdvs = $conn->query("
    SELECT r.*,
           b.nom_prenom        AS nom_bebe,
           b.sexe              AS sexe_bebe,
           p.nom_prenom        AS nom_parent,
           p.email             AS email_parent,
           p.telephone         AS tel_parent,
           p.id_parent         AS id_parent,
           CONCAT(m.prenom,' ',m.nom) AS nom_pediatre,
           v.nom               AS nom_vaccin
    FROM rendez_vous r
    LEFT JOIN bebe    b ON r.bebe_id    = b.id_bebe
    LEFT JOIN parent  p ON b.id_parent  = p.id_parent
    LEFT JOIN medecin m ON r.pediatre_id = m.id
    LEFT JOIN vaccin  v ON r.vaccin_id   = v.id
    $whereSQL
    ORDER BY r.date_rdv DESC, r.heure_rdv DESC
")->fetch_all(MYSQLI_ASSOC);

// Comptes par statut (avec même filtre bébé/parent)
function countByStatut($conn, $statut, $bebe_id, $parent_id) {
    $w = [];
    if ($bebe_id)   $w[] = "r.bebe_id = $bebe_id";
    if ($parent_id) $w[] = "b.id_parent = $parent_id";
    if ($statut !== 'tous') $w[] = "r.statut = '$statut'";
    $wSQL = $w ? "WHERE " . implode(" AND ", $w) : "";
    return $conn->query("
        SELECT COUNT(*) FROM rendez_vous r
        LEFT JOIN bebe b ON r.bebe_id = b.id_bebe
        $wSQL
    ")->fetch_row()[0];
}

$cTous      = countByStatut($conn, 'tous',       $bebe_id, $parent_id);
$cAttente   = countByStatut($conn, 'en_attente', $bebe_id, $parent_id);
$cConfirme  = countByStatut($conn, 'confirme',   $bebe_id, $parent_id);
$cAnnule    = countByStatut($conn, 'annule',     $bebe_id, $parent_id);
$cEffectue  = countByStatut($conn, 'effectue',   $bebe_id, $parent_id);

// Construit l'URL de filtre en conservant le contexte
function filterUrl($filtre, $bebe_id, $parent_id, $search) {
    return 'ad_rendezvous.php?' . http_build_query(array_filter([
        'filtre'    => $filtre,
        'bebe_id'   => $bebe_id   ?: null,
        'parent_id' => $parent_id ?: null,
        'search'    => $search    ?: null,
    ]));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion Rendez-vous — TinySteps</title>
<link rel="stylesheet" href="styles/sidebar.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --indigo:      #4F46E5;
    --indigo-lt:   #EEF2FF;
    --indigo-mid:  #6366F1;
    --green:       #16A34A;
    --green-lt:    #DCFCE7;
    --amber:       #D97706;
    --amber-lt:    #FEF3C7;
    --red:         #DC2626;
    --red-lt:      #FEE2E2;
    --sky:         #0284C7;
    --sky-lt:      #E0F2FE;
    --violet:      #7C3AED;
    --violet-lt:   #F5F3FF;
    --teal:        #0D9488;
    --teal-lt:     #CCFBF1;
    --slate-50:    #F8FAFC;
    --slate-100:   #F1F5F9;
    --slate-200:   #E2E8F0;
    --slate-300:   #CBD5E1;
    --slate-400:   #94A3B8;
    --slate-600:   #475569;
    --slate-700:   #334155;
    --slate-800:   #1E293B;
    --slate-900:   #0F172A;
    --white:       #FFFFFF;
    --radius:      16px;
    --shadow:      0 2px 20px rgba(79,70,229,.07);
    --shadow-lg:   0 12px 48px rgba(79,70,229,.14);
    --font:        'Nunito', sans-serif;
    --font-disp:   'Cormorant Garamond', serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: var(--font); background: var(--slate-50); color: var(--slate-800); min-height: 100vh; }

.admin-layout { display: flex; min-height: 100vh; }
.admin-main   { flex: 1; padding: 36px 40px; overflow-y: auto; }

/* ── Breadcrumb ── */
.breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: .79rem; color: var(--slate-400); margin-bottom: 20px; flex-wrap: wrap;
}
.breadcrumb a { color: var(--indigo); text-decoration: none; font-weight: 700; }
.breadcrumb a:hover { text-decoration: underline; }
.breadcrumb .sep { font-size: .6rem; color: var(--slate-300); }

/* ── Header ── */
.page-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 28px; flex-wrap: wrap; gap: 16px;
}
.page-eyebrow {
    font-size: .7rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: var(--indigo-mid); margin-bottom: 4px;
}
.page-title {
    font-family: var(--font-disp); font-size: 2.1rem;
    font-weight: 700; color: var(--slate-900); line-height: 1.1;
}
.page-sub { font-size: .87rem; color: var(--slate-400); margin-top: 4px; }

/* Search */
.search-box {
    display: flex; align-items: center; gap: 10px;
    background: var(--white); border: 1.5px solid var(--slate-200);
    border-radius: 12px; padding: 0 16px;
    transition: border .2s, box-shadow .2s;
}
.search-box:focus-within {
    border-color: var(--indigo-mid);
    box-shadow: 0 0 0 3px rgba(99,102,241,.11);
}
.search-box i { color: var(--slate-400); font-size: .88rem; }
.search-box input {
    border: none; outline: none; background: transparent;
    font-family: var(--font); font-size: .9rem;
    color: var(--slate-800); padding: 11px 0; width: 230px;
}

/* ── Context banner (bébé) ── */
.context-banner {
    border-radius: 14px; padding: 16px 22px;
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 24px;
    background: linear-gradient(135deg, #4F46E5, #7C3AED);
    color: white;
}
.cb-icon {
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,.18);
    border: 2px solid rgba(255,255,255,.35);
    font-size: 1.15rem;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.cb-info { flex: 1; }
.cb-name { font-weight: 800; font-size: .98rem; }
.cb-sub  { font-size: .78rem; opacity: .7; margin-top: 2px; }
.cb-back {
    background: rgba(255,255,255,.18); border: none; color: white;
    padding: 8px 16px; border-radius: 8px; font-family: var(--font);
    font-size: .8rem; font-weight: 700; cursor: pointer;
    text-decoration: none; display: flex; align-items: center; gap: 6px;
    transition: background .2s;
}
.cb-back:hover { background: rgba(255,255,255,.3); }

/* ── Stat chips ── */
.stat-chips {
    display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;
}
.chip {
    display: flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 30px;
    font-size: .8rem; font-weight: 700;
    cursor: pointer; text-decoration: none;
    border: 2px solid transparent;
    transition: all .2s; white-space: nowrap;
}
.chip .cnt {
    padding: 1px 8px; border-radius: 20px;
    font-size: .75rem; font-weight: 800;
    background: currentColor; color: white;
    /* fix: use filter to invert on colored bg */
}
.chip.tous     { background: var(--slate-100); color: var(--slate-600); }
.chip.attente  { background: var(--amber-lt);  color: var(--amber); }
.chip.confirme { background: var(--green-lt);  color: var(--green); }
.chip.annule   { background: var(--red-lt);    color: var(--red); }
.chip.effectue { background: var(--indigo-lt); color: var(--indigo); }
.chip.active, .chip:hover { border-color: currentColor; transform: translateY(-1px); }

/* ── Stats summary ── */
.stats-row {
    display: flex; gap: 14px; margin-bottom: 26px; flex-wrap: wrap;
}
.stat-mini {
    flex: 1; min-width: 130px;
    background: var(--white); border: 1px solid var(--slate-200);
    border-radius: 12px; padding: 14px 18px;
    box-shadow: var(--shadow);
    display: flex; align-items: center; gap: 12px;
}
.stat-mini-ico {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; flex-shrink: 0;
}
.stat-mini-ico.indigo { background: var(--indigo-lt); color: var(--indigo); }
.stat-mini-ico.green  { background: var(--green-lt);  color: var(--green); }
.stat-mini-ico.amber  { background: var(--amber-lt);  color: var(--amber); }
.stat-mini-ico.red    { background: var(--red-lt);    color: var(--red); }
.stat-mini-lbl { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--slate-400); }
.stat-mini-val { font-size: 1.45rem; font-weight: 800; color: var(--slate-900); line-height: 1; }

/* ── Table ── */
.table-card {
    background: var(--white); border-radius: var(--radius);
    border: 1px solid var(--slate-200); box-shadow: var(--shadow); overflow: hidden;
}
.table-wrap { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; }
thead {
    background: linear-gradient(to right, #EEF2FF, #F5F3FF);
    border-bottom: 2px solid var(--slate-100);
}
th {
    padding: 14px 18px; text-align: left;
    font-size: .7rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .08em;
    color: var(--slate-400); white-space: nowrap;
}
td {
    padding: 15px 18px; font-size: .88rem;
    color: var(--slate-600); border-bottom: 1px solid var(--slate-100);
    vertical-align: middle;
}
tr:last-child td { border-bottom: none; }
tr { transition: background .12s; }
tr:hover td { background: #F8F7FF; }

/* Bébé cell */
.bebe-cell { display: flex; align-items: center; gap: 11px; }
.bebe-ava {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; flex-shrink: 0;
}
.bebe-ava.m { background: var(--sky-lt);   color: var(--sky); }
.bebe-ava.f { background: #FDF2F8;         color: #EC4899; }
.bebe-cell-name { font-weight: 800; color: var(--slate-900); font-size: .9rem; }
.bebe-cell-sub  { font-size: .76rem; color: var(--slate-400); }

/* Date cell */
.date-cell { display: flex; flex-direction: column; gap: 2px; }
.date-main { font-weight: 700; color: var(--slate-800); font-size: .9rem; }
.date-time { font-size: .78rem; color: var(--indigo); font-weight: 700; }
.date-past { color: var(--slate-400) !important; }

/* Statut badge */
.badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 20px;
    font-size: .76rem; font-weight: 800; white-space: nowrap;
}
.badge.en_attente { background: var(--amber-lt); color: var(--amber); }
.badge.confirme   { background: var(--green-lt); color: var(--green); }
.badge.annule     { background: var(--red-lt);   color: var(--red); }
.badge.effectue   { background: var(--indigo-lt); color: var(--indigo); }

/* Vaccin tag */
.vaccin-tag {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--violet-lt); color: var(--violet);
    border-radius: 8px; padding: 3px 10px;
    font-size: .76rem; font-weight: 700;
    max-width: 160px; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap;
}

/* Actions */
.actions { display: flex; gap: 6px; align-items: center; }
.btn-icon {
    width: 32px; height: 32px; border: none; border-radius: 8px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: .82rem; transition: all .17s;
}
.btn-icon.view    { background: var(--indigo-lt); color: var(--indigo); }
.btn-icon.confirm { background: var(--green-lt);  color: var(--green); }
.btn-icon.cancel  { background: var(--amber-lt);  color: var(--amber); }
.btn-icon.delete  { background: var(--red-lt);    color: var(--red); }
.btn-icon:hover   { filter: brightness(.9); transform: scale(1.1); }

/* Empty */
.empty-td { text-align: center; padding: 70px 20px !important; color: var(--slate-400); }
.empty-td i { font-size: 2.5rem; display: block; margin-bottom: 12px; opacity: .3; }

/* ── Modal ── */
.modal-backdrop {
    display: none; position: fixed; inset: 0;
    background: rgba(15,23,42,.52);
    backdrop-filter: blur(6px);
    z-index: 1000; align-items: center; justify-content: center; padding: 20px;
}
.modal-backdrop.open { display: flex; }

.modal {
    background: var(--white); border-radius: 22px;
    box-shadow: 0 28px 80px rgba(0,0,0,.2);
    width: 100%; max-width: 520px; overflow: hidden;
    animation: popIn .28s cubic-bezier(.34,1.56,.64,1);
    max-height: 92vh; overflow-y: auto;
}
@keyframes popIn {
    from { transform: scale(.87) translateY(20px); opacity: 0; }
    to   { transform: scale(1)   translateY(0);    opacity: 1; }
}

.modal-hero {
    background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
    padding: 36px 28px 60px; text-align: center;
    position: relative; overflow: hidden;
}
.modal-hero::before {
    content: '';
    position: absolute; top: -40px; right: -40px;
    width: 160px; height: 160px; border-radius: 50%;
    background: rgba(255,255,255,.06);
}
.modal-hero::after {
    content: '';
    position: absolute; bottom: -50px; left: 20px;
    width: 120px; height: 120px; border-radius: 50%;
    background: rgba(255,255,255,.04);
}
.modal-close {
    position: absolute; top: 14px; right: 14px;
    background: rgba(255,255,255,.18); border: none; color: white;
    width: 34px; height: 34px; border-radius: 50%;
    cursor: pointer; font-size: .9rem;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s; z-index: 2;
}
.modal-close:hover { background: rgba(255,255,255,.32); }

.modal-date-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 30px; padding: 6px 18px;
    color: white; font-size: .9rem; font-weight: 800;
    position: relative; z-index: 1; margin-bottom: 12px;
}
.modal-time {
    color: rgba(255,255,255,.7); font-size: .8rem;
    position: relative; z-index: 1; margin-top: 2px;
}

/* Floating status card */
.status-float {
    background: var(--white); border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,.12);
    padding: 14px 20px; margin: -28px 28px 0;
    display: flex; align-items: center; justify-content: space-between;
    position: relative; z-index: 2;
    border: 1px solid var(--slate-100);
}
.sf-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--slate-400); margin-bottom: 4px; }
.sf-val   { font-size: .9rem; font-weight: 800; }

.modal-body { padding: 22px 28px 10px; }
.section-title {
    font-size: .7rem; font-weight: 800; letter-spacing: .1em;
    text-transform: uppercase; color: var(--slate-400); margin-bottom: 12px;
}
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px; }
.ig-item label { display: block; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--slate-400); margin-bottom: 3px; }
.ig-item span  { font-size: .88rem; color: var(--slate-800); font-weight: 700; }
.ig-item.full  { grid-column: 1/-1; }

/* Persons row */
.person-row {
    display: flex; align-items: center; gap: 12px;
    background: var(--slate-50); border: 1px solid var(--slate-100);
    border-radius: 11px; padding: 12px 14px; margin-bottom: 8px;
}
.pr-ava {
    width: 38px; height: 38px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .88rem; flex-shrink: 0;
}
.pr-ava.baby    { background: var(--sky-lt);    color: var(--sky); }
.pr-ava.parent  { background: var(--teal-lt);   color: var(--teal); }
.pr-ava.doctor  { background: var(--green-lt);  color: var(--green); }
.pr-name { font-weight: 800; font-size: .88rem; color: var(--slate-900); }
.pr-sub  { font-size: .76rem; color: var(--slate-400); }

/* Motif box */
.motif-box {
    background: var(--indigo-lt); border-radius: 10px;
    padding: 12px 14px; margin-bottom: 18px;
    border-left: 3px solid var(--indigo);
}
.motif-box p { font-size: .87rem; color: var(--slate-700); font-weight: 600; }

.modal-actions {
    padding: 14px 28px 24px; display: flex; gap: 10px; flex-wrap: wrap;
}
.ma-btn {
    flex: 1; padding: 11px 14px; border: none; border-radius: 11px;
    font-family: var(--font); font-weight: 700; font-size: .87rem;
    cursor: pointer; display: flex; align-items: center;
    justify-content: center; gap: 7px; transition: all .18s; min-width: 100px;
}
.ma-btn:hover { transform: translateY(-1px); filter: brightness(.92); }
.ma-btn.green  { background: var(--green-lt); color: var(--green); }
.ma-btn.amber  { background: var(--amber-lt); color: var(--amber); }
.ma-btn.red    { background: var(--red-lt);   color: var(--red); }

/* Toast */
.toast {
    position: fixed; bottom: 28px; right: 28px;
    background: var(--slate-900); color: white;
    padding: 13px 20px; border-radius: 12px; font-size: .87rem;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,.2); z-index: 2000;
    transform: translateY(80px); opacity: 0; transition: all .3s;
    max-width: 300px;
}
.toast.show { transform: translateY(0); opacity: 1; }
.toast.success { border-left: 4px solid var(--green); }
.toast.warning { border-left: 4px solid var(--amber); }
.toast.error   { border-left: 4px solid var(--red); }

/* Animations lignes tableau */
tbody tr {
    animation: fadeRow .35s ease both;
}
@keyframes fadeRow {
    from { opacity: 0; transform: translateX(-8px); }
    to   { opacity: 1; transform: translateX(0); }
}
<?php foreach(range(0,29) as $i): ?>
tbody tr:nth-child(<?= $i+1 ?>) { animation-delay: <?= $i * .03 ?>s; }
<?php endforeach; ?>
</style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar"><?php include 'sidebar.php'; ?></aside>

    <main class="admin-main">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
            <?php if ($bebeRow): ?>
                <a href="ad_parents.php">Parents</a>
                <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
                <a href="ad_bebes.php?parent_id=<?php echo $bebeRow['id_parent']; ?>">Bébés</a>
                <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
                <span><?php echo htmlspecialchars($bebeRow['nom_prenom']); ?></span>
                <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
            <?php elseif ($parent_id): ?>
                <a href="ad_parents.php">Parents</a>
                <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
            <?php endif; ?>
            <span>Rendez-vous</span>
        </div>

        <!-- Header -->
        <div class="page-header">
            <div>
                <div class="page-eyebrow"><i class="fa-solid fa-calendar-check"></i> &nbsp;Administration</div>
                <h1 class="page-title">
                    <?php if ($bebeRow): ?>
                        RDV · <?php echo htmlspecialchars($bebeRow['nom_prenom']); ?>
                    <?php else: ?>
                        Gestion des Rendez-vous
                    <?php endif; ?>
                </h1>
                <p class="page-sub"><?php echo count($rdvs); ?> rendez-vous<?php echo $filtre!=='tous' ? ' · filtre : '.$filtre : ''; ?></p>
            </div>
            <form method="GET">
                <?php if ($bebe_id):   ?><input type="hidden" name="bebe_id"   value="<?php echo $bebe_id; ?>"><?php endif; ?>
                <?php if ($parent_id): ?><input type="hidden" name="parent_id" value="<?php echo $parent_id; ?>"><?php endif; ?>
                <?php if ($filtre !== 'tous'): ?><input type="hidden" name="filtre" value="<?php echo $filtre; ?>"><?php endif; ?>
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Bébé, parent, pédiatre…"
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </form>
        </div>

        <!-- Contexte bébé -->
        <?php if ($bebeRow): ?>
        <div class="context-banner">
            <div class="cb-icon"><i class="fa-solid fa-baby"></i></div>
            <div class="cb-info">
                <div class="cb-name"><?php echo htmlspecialchars($bebeRow['nom_prenom']); ?></div>
                <div class="cb-sub">Parent : <?php echo htmlspecialchars($bebeRow['nom_parent'] ?? '—'); ?></div>
            </div>
            <a href="ad_bebes.php?parent_id=<?php echo $bebeRow['id_parent']; ?>" class="cb-back">
                <i class="fa-solid fa-arrow-left"></i> Retour aux bébés
            </a>
        </div>
        <?php endif; ?>

        <!-- Mini stats -->
        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-ico indigo"><i class="fa-solid fa-calendar-days"></i></div>
                <div><div class="stat-mini-lbl">Total</div><div class="stat-mini-val"><?php echo $cTous; ?></div></div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-ico amber"><i class="fa-solid fa-clock"></i></div>
                <div><div class="stat-mini-lbl">En attente</div><div class="stat-mini-val"><?php echo $cAttente; ?></div></div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-ico green"><i class="fa-solid fa-check-circle"></i></div>
                <div><div class="stat-mini-lbl">Confirmés</div><div class="stat-mini-val"><?php echo $cConfirme; ?></div></div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-ico red"><i class="fa-solid fa-times-circle"></i></div>
                <div><div class="stat-mini-lbl">Annulés</div><div class="stat-mini-val"><?php echo $cAnnule; ?></div></div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="stat-chips">
            <a href="<?php echo filterUrl('tous',       $bebe_id, $parent_id, $search); ?>" class="chip tous     <?php echo $filtre==='tous'?'active':''; ?>">
                <i class="fa-solid fa-list"></i> Tous <span class="cnt"><?php echo $cTous; ?></span>
            </a>
            <a href="<?php echo filterUrl('en_attente', $bebe_id, $parent_id, $search); ?>" class="chip attente  <?php echo $filtre==='en_attente'?'active':''; ?>">
                <i class="fa-solid fa-clock"></i> En attente <span class="cnt"><?php echo $cAttente; ?></span>
            </a>
            <a href="<?php echo filterUrl('confirme',   $bebe_id, $parent_id, $search); ?>" class="chip confirme <?php echo $filtre==='confirme'?'active':''; ?>">
                <i class="fa-solid fa-check"></i> Confirmés <span class="cnt"><?php echo $cConfirme; ?></span>
            </a>
            <a href="<?php echo filterUrl('annule',     $bebe_id, $parent_id, $search); ?>" class="chip annule   <?php echo $filtre==='annule'?'active':''; ?>">
                <i class="fa-solid fa-xmark"></i> Annulés <span class="cnt"><?php echo $cAnnule; ?></span>
            </a>
            <a href="<?php echo filterUrl('effectue',   $bebe_id, $parent_id, $search); ?>" class="chip effectue <?php echo $filtre==='effectue'?'active':''; ?>">
                <i class="fa-solid fa-circle-check"></i> Effectués <span class="cnt"><?php echo $cEffectue; ?></span>
            </a>
        </div>

        <!-- Tableau -->
        <div class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Bébé</th>
                            <th>Date & Heure</th>
                            <th>Parent</th>
                            <th>Pédiatre</th>
                            <th>Vaccin</th>
                            <th>Motif</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rdvs)): ?>
                        <tr>
                            <td colspan="9" class="empty-td">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                Aucun rendez-vous trouvé.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rdvs as $r):
                            $statut  = $r['statut'] ?: 'en_attente';
                            $sexe    = strtolower($r['sexe_bebe'] ?? '') === 'f' ? 'f' : 'm';
                            $isPast  = strtotime($r['date_rdv']) < strtotime('today');
                            $isNew   = $statut === 'en_attente';
                        ?>
                        <tr>
                            <td style="color:var(--slate-400); font-size:.8rem; font-weight:700;">#<?php echo $r['id']; ?></td>
                            <td>
                                <div class="bebe-cell">
                                    <div class="bebe-ava <?php echo $sexe; ?>">
                                        <i class="fa-solid fa-baby"></i>
                                    </div>
                                    <div>
                                        <div class="bebe-cell-name"><?php echo htmlspecialchars($r['nom_bebe'] ?? '—'); ?></div>
                                        <div class="bebe-cell-sub">
                                            <?php echo $sexe==='f' ? '♀ Fille' : '♂ Garçon'; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="date-cell">
                                    <span class="date-main <?php echo $isPast?'date-past':''; ?>">
                                        <?php echo date('d/m/Y', strtotime($r['date_rdv'])); ?>
                                    </span>
                                    <span class="date-time"><?php echo substr($r['heure_rdv'],0,5); ?></span>
                                </div>
                            </td>
                            <td style="font-weight:700; color:var(--slate-800);"><?php echo htmlspecialchars($r['nom_parent'] ?? '—'); ?></td>
                            <td style="font-size:.84rem;">Dr <?php echo htmlspecialchars($r['nom_pediatre'] ?? '—'); ?></td>
                            <td>
                                <?php if ($r['nom_vaccin']): ?>
                                <span class="vaccin-tag" title="<?php echo htmlspecialchars($r['nom_vaccin']); ?>">
                                    <i class="fa-solid fa-syringe"></i>
                                    <?php echo htmlspecialchars($r['nom_vaccin']); ?>
                                </span>
                                <?php else: echo '<span style="color:var(--slate-300);">—</span>'; endif; ?>
                            </td>
                            <td style="font-size:.83rem; max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?php echo htmlspecialchars($r['motif'] ?: '—'); ?>
                            </td>
                            <td>
                                <?php
                                $icons = [
                                    'en_attente' => 'fa-clock',
                                    'confirme'   => 'fa-check',
                                    'annule'     => 'fa-xmark',
                                    'effectue'   => 'fa-circle-check',
                                ];
                                $icon = $icons[$statut] ?? 'fa-circle';
                                ?>
                                <span class="badge <?php echo $statut; ?>">
                                    <i class="fa-solid <?php echo $icon; ?>"></i>
                                    <?php echo ucfirst(str_replace('_',' ', $statut)); ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <!-- Voir détail -->
                                    <button type="button" class="btn-icon view" title="Détail"
                                            onclick='openModal(<?php echo json_encode($r); ?>)'>
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <?php if ($isNew): ?>
                                    <!-- Confirmer -->
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="id"              value="<?php echo $r['id']; ?>">
                                        <input type="hidden" name="action"          value="confirmer">
                                        <input type="hidden" name="redirect_params" value="<?php echo htmlspecialchars($redirect_params); ?>">
                                        <button type="submit" class="btn-icon confirm" title="Confirmer">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                    <!-- Annuler -->
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="id"              value="<?php echo $r['id']; ?>">
                                        <input type="hidden" name="action"          value="annuler">
                                        <input type="hidden" name="redirect_params" value="<?php echo htmlspecialchars($redirect_params); ?>">
                                        <button type="submit" class="btn-icon cancel" title="Annuler">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <!-- Supprimer -->
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Supprimer ce rendez-vous ?')">
                                        <input type="hidden" name="id"              value="<?php echo $r['id']; ?>">
                                        <input type="hidden" name="action"          value="supprimer">
                                        <input type="hidden" name="redirect_params" value="<?php echo htmlspecialchars($redirect_params); ?>">
                                        <button type="submit" class="btn-icon delete" title="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- ── Modal Détail RDV ──────────────────────────────────────────────────────── -->
<div class="modal-backdrop" id="modalBackdrop" onclick="closeOut(event)">
    <div class="modal" id="modalBox">

        <div class="modal-hero" id="mHero">
            <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            <div class="modal-date-badge" id="mDate"></div>
            <div class="modal-time" id="mTime"></div>
        </div>

        <div class="status-float" id="mStatusFloat"></div>

        <div class="modal-body" id="mBody"></div>
        <div class="modal-actions" id="mActions"></div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
    <i id="toastIcon"></i><span id="toastMsg"></span>
</div>

<script>
const redirectParams = "<?php echo $redirect_params; ?>";

function openModal(r) {
    const statut  = r.statut || 'en_attente';
    const isNew   = statut === 'en_attente';
    const dateStr = fmtDate(r.date_rdv);
    const heureStr = (r.heure_rdv || '').substring(0,5);

    // Hero
    document.getElementById('mDate').innerHTML = `<i class="fa-solid fa-calendar"></i> ${dateStr}`;
    document.getElementById('mTime').innerHTML = `<i class="fa-solid fa-clock"></i> ${heureStr}`;

    // Status float
    const statusLabels = { en_attente: 'En attente', confirme: 'Confirmé', annule: 'Annulé', effectue: 'Effectué' };
    const statusColors = { en_attente: 'var(--amber)', confirme: 'var(--green)', annule: 'var(--red)', effectue: 'var(--indigo)' };
    document.getElementById('mStatusFloat').innerHTML = `
        <div>
            <div class="sf-label">Statut du RDV</div>
            <div class="sf-val" style="color:${statusColors[statut]||'var(--slate-800)'}">
                ${statusLabels[statut] || statut}
            </div>
        </div>
        <span class="badge ${statut}">
            <i class="fa-solid fa-circle"></i> RDV #${r.id}
        </span>`;

    // Body
    const motifHtml = r.motif
        ? `<div class="section-title">Motif</div>
           <div class="motif-box"><p>${esc(r.motif)}</p></div>`
        : '';

    document.getElementById('mBody').innerHTML = `
        <div class="section-title">Participants</div>
        <div class="person-row">
            <div class="pr-ava baby"><i class="fa-solid fa-baby"></i></div>
            <div>
                <div class="pr-name">${esc(r.nom_bebe || '—')}</div>
                <div class="pr-sub">Bébé</div>
            </div>
        </div>
        <div class="person-row">
            <div class="pr-ava parent"><i class="fa-solid fa-user"></i></div>
            <div>
                <div class="pr-name">${esc(r.nom_parent || '—')}</div>
                <div class="pr-sub">Parent · ${esc(r.email_parent||'')} ${r.tel_parent ? '· '+esc(r.tel_parent) : ''}</div>
            </div>
        </div>
        <div class="person-row">
            <div class="pr-ava doctor"><i class="fa-solid fa-user-doctor"></i></div>
            <div>
                <div class="pr-name">Dr ${esc(r.nom_pediatre || '—')}</div>
                <div class="pr-sub">Pédiatre</div>
            </div>
        </div>

        <div class="section-title" style="margin-top:16px;">Détails</div>
        <div class="info-grid">
            <div class="ig-item">
                <label>Date</label><span>${fmtDate(r.date_rdv)}</span>
            </div>
            <div class="ig-item">
                <label>Heure</label><span>${heureStr}</span>
            </div>
            <div class="ig-item">
                <label>Vaccin</label>
                <span>${r.nom_vaccin ? '<i class="fa-solid fa-syringe" style="color:var(--violet)"></i> '+esc(r.nom_vaccin) : '—'}</span>
            </div>
            <div class="ig-item">
                <label>Créé le</label>
                <span>${r.date_creation ? fmtDate(r.date_creation.split(' ')[0]) : '—'}</span>
            </div>
        </div>
        ${motifHtml}
    `;

    // Actions (seulement si en attente)
    let actionsHtml = '';
    if (isNew) {
        actionsHtml = `
        <form method="POST" style="flex:1">
            <input type="hidden" name="id"              value="${r.id}">
            <input type="hidden" name="action"          value="confirmer">
            <input type="hidden" name="redirect_params" value="${redirectParams}">
            <button type="submit" class="ma-btn green" style="width:100%">
                <i class="fa-solid fa-check"></i> Confirmer
            </button>
        </form>
        <form method="POST" style="flex:1">
            <input type="hidden" name="id"              value="${r.id}">
            <input type="hidden" name="action"          value="annuler">
            <input type="hidden" name="redirect_params" value="${redirectParams}">
            <button type="submit" class="ma-btn amber" style="width:100%">
                <i class="fa-solid fa-xmark"></i> Annuler
            </button>
        </form>`;
    }
    actionsHtml += `
        <form method="POST" style="flex:1"
              onsubmit="return confirm('Supprimer ce rendez-vous ?')">
            <input type="hidden" name="id"              value="${r.id}">
            <input type="hidden" name="action"          value="supprimer">
            <input type="hidden" name="redirect_params" value="${redirectParams}">
            <button type="submit" class="ma-btn red" style="width:100%">
                <i class="fa-solid fa-trash"></i> Supprimer
            </button>
        </form>`;

    document.getElementById('mActions').innerHTML = actionsHtml;
    document.getElementById('modalBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}
function closeOut(e) {
    if (e.target === document.getElementById('modalBackdrop')) closeModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

function fmtDate(d) {
    if (!d) return '—';
    const parts = d.split('-');
    if (parts.length < 3) return d;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>