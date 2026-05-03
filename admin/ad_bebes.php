<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tinysteps");
$conn->set_charset("utf8mb4");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// ── Actions ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['id'];
    $action = $_POST['action'];
    $pid = (int) $_POST['parent_id'];
    if ($action === 'supprimer_bebe') {
        $conn->query("DELETE FROM bebe WHERE id_bebe = $id");
    }
    header("Location: ad_bebes.php?parent_id=$pid");
    exit();
}

// ── Contexte ─────────────────────────────────────────────────────────────────
$parent_id = (int) ($_GET['parent_id'] ?? 0);

// Si pas de parent_id, afficher tous les bébés
$parentRow = null;
if ($parent_id) {
    $parentRow = $conn->query("SELECT * FROM parent WHERE id_parent = $parent_id")->fetch_assoc();
}

$search = trim($_GET['search'] ?? '');
$where = [];
if ($parent_id)
    $where[] = "b.id_parent = $parent_id";
if ($search)
    $where[] = "(b.nom_prenom LIKE '%$search%' OR p.nom_prenom LIKE '%$search%')";
$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

$bebes = $conn->query("
    SELECT b.*,
           p.nom_prenom     AS nom_parent,
           p.email          AS email_parent,
           p.telephone      AS tel_parent,
           m.nom            AS nom_pediatre,
           m.prenom         AS prenom_pediatre,
           (SELECT COUNT(*) FROM rendez_vous r WHERE r.bebe_id = b.id_bebe) AS nb_rdv,
           (SELECT COUNT(*) FROM vaccination v WHERE v.bebe_id = b.id_bebe) AS nb_vaccins
    FROM bebe b
    LEFT JOIN parent  p ON b.id_parent  = p.id_parent
    LEFT JOIN medecin m ON b.pediatre_id = m.id
    $whereSQL
    ORDER BY b.id_bebe DESC
")->fetch_all(MYSQLI_ASSOC);

$totalBebes = count($bebes);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Bébés — TinySteps</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="styles/bebes.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Cormorant+Garamond:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>
    <div class="admin-layout">
        <aside class="sidebar"><?php include 'sidebar.php'; ?></aside>

        <main class="admin-main">

            <!-- Header -->
            <div class="page-header">
                <div>

                    <h1 class="page-title">
                        <?php echo $parentRow ? 'Bébés de ' . htmlspecialchars($parentRow['nom_prenom']) : 'Gestion des Bébés'; ?>
                    </h1>
                    <p class="page-sub"><?php echo $totalBebes; ?> bébé<?php echo $totalBebes > 1 ? 's' : ''; ?>
                        trouvé<?php echo $totalBebes > 1 ? 's' : ''; ?></p>
                </div>
                <form method="GET">
                    <?php if ($parent_id): ?><input type="hidden" name="parent_id"
                            value="<?php echo $parent_id; ?>"><?php endif; ?>
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" placeholder="Nom du bébé ou parent…"
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </form>
            </div>

            <!-- Contexte parent -->
            <?php if ($parentRow): ?>
                <div class="context-banner">
                    <div class="cb-avatar"><?php echo strtoupper(substr($parentRow['nom_prenom'], 0, 1)); ?></div>
                    <div class="cb-info">
                        <div class="cb-name"><?php echo htmlspecialchars($parentRow['nom_prenom']); ?></div>
                        <div class="cb-sub"><?php echo htmlspecialchars($parentRow['email'] ?? ''); ?> ·
                            <?php echo htmlspecialchars($parentRow['telephone'] ?? ''); ?>
                        </div>
                    </div>
                    <a href="ad_parents.php" class="cb-back">
                        <i class="fa-solid fa-arrow-left"></i> Retour aux parents
                    </a>
                </div>
            <?php endif; ?>

            <!-- Tableau -->
            <div class="table-card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Bébé</th>
                                <th>Sexe</th>
                                <th>Âge</th>
                                <th>Lieu naissance</th>
                                <th>Parent</th>
                                <th>Pédiatre</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bebes)): ?>
                                <tr class="empty-row">
                                    <td colspan="8">
                                        <i class="fa-solid fa-baby"></i>
                                        Aucun bébé trouvé.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bebes as $b):
                                    $sexe = strtolower($b['sexe']) === 'f' ? 'f' : 'm';
                                    $age = calculerAge($b['date_naissance']);
                                    $pediatre = $b['nom_pediatre'] ? 'Dr ' . $b['prenom_pediatre'] . ' ' . $b['nom_pediatre'] : '—';
                                    ?>
                                    <tr>
                                        <td style="color:var(--s400);font-size:.75rem">#
                                            <?= $b['id_bebe'] ?>
                                        </td>
                                        <td>
                                            <div class="bebe-cell">
                                                <div class="bebe-icon-wrap <?php echo $sexe; ?>">
                                                    <i class="fa-solid fa-baby"></i>
                                                </div>
                                                <div>
                                                    <div class="bebe-cell-name">
                                                        <?php echo htmlspecialchars($b['nom_prenom']); ?>
                                                    </div>
                                                    <div class="bebe-cell-sub">
                                                        <?php echo date('d/m/Y', strtotime($b['date_naissance'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="sexe-badge <?php echo $sexe; ?>">
                                                <i class="fa-solid fa-<?php echo $sexe === 'f' ? 'venus' : 'mars'; ?>"></i>
                                                <?php echo $sexe === 'f' ? 'Fille' : 'Garçon'; ?>
                                            </span>
                                        </td>
                                        <td><span class="age-badge">
                                                <?php echo $age; ?></span></td>
                                        <td><?php echo htmlspecialchars($b['lieu_naissance'] ?: '—'); ?></td>
                                        <td style="font-weight:600; color:var(--slate-800);">
                                            <?php echo htmlspecialchars($b['nom_parent'] ?: '—'); ?>
                                        </td>
                                        <td style="font-size:.83rem;">
                                            <?php echo htmlspecialchars($pediatre); ?>
                                        </td>

                                        <td>
                                            <div class="actions">
                                                <button type="button" class="btn-icon view" title="Voir détail"
                                                    onclick='openModal(<?php echo json_encode($b); ?>, "<?php echo $sexe; ?>", "<?php echo htmlspecialchars(addslashes($age)); ?>", "<?php echo htmlspecialchars(addslashes($pediatre)); ?>")'>
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <a href="ad_rendezvous.php?bebe_id=<?php echo $b['id_bebe']; ?>"
                                                    class="btn-icon rdv" title="Voir les RDV">
                                                    <i class="fa-solid fa-calendar-check"></i>
                                                </a>
                                                <form method="POST" style="display:inline"
                                                    onsubmit="return confirm('Supprimer ce bébé ?')">
                                                    <input type="hidden" name="id" value="<?php echo $b['id_bebe']; ?>">
                                                    <input type="hidden" name="parent_id" value="<?php echo $parent_id; ?>">
                                                    <input type="hidden" name="action" value="supprimer_bebe">
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

    <!-- Modal Détail Bébé -->
    <div class="modal-backdrop" id="modalBackdrop" onclick="closeOut(event)">
        <div class="modal" id="modalBox">
            <div class="modal-hero" id="mHero">
                <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
                <div class="modal-bebe-icon" id="mIcon"></div>
                <div class="modal-hero-name" id="mName"></div>
                <div class="modal-hero-sub" id="mSub"></div>
            </div>
            <div class="modal-body" id="mBody"></div>
            <div class="modal-footer" id="mFooter"></div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast">
        <i id="toastIcon"></i>
        <span id="toastMsg"></span>
    </div>

    <?php
    function calculerAge($dateNaissance)
    {
        if (!$dateNaissance)
            return '—';
        $now = new DateTime();
        $nais = new DateTime($dateNaissance);
        $diff = $now->diff($nais);
        if ($diff->y > 0)
            return $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
        if ($diff->m > 0)
            return $diff->m . ' mois';
        return $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    }
    ?>
    <script>
        const parentId = <?php echo $parent_id ?: 0; ?>;

        function openModal(b, sexe, age, pediatre) {
            const isFemme = sexe === 'f';
            // Hero
            document.getElementById('mHero').className = 'modal-hero ' + sexe;
            document.getElementById('mIcon').innerHTML = '<i class="fa-solid fa-baby"></i>';
            document.getElementById('mName').textContent = esc(b.nom_prenom);
            document.getElementById('mSub').textContent = (isFemme ? 'Fille' : 'Garçon') + ' · ' + age;

            // Body
            const rdvCount = parseInt(b.nb_rdv) || 0;
            const vaccinCount = parseInt(b.nb_vaccins) || 0;

            document.getElementById('mBody').innerHTML = `
        <div class="stat-row">
          
            <div class="stat-box blue">
                <div class="val">${age}</div>
                <div class="lbl">Âge</div>
            </div>
        </div>

        <div class="section-title">Informations</div>
        <div class="detail-grid">
            <div class="detail-item">
                <label>Date de naissance</label>
                <span>${fmtDate(b.date_naissance)}</span>
            </div>
            <div class="detail-item">
                <label>Lieu de naissance</label>
                <span>${esc(b.lieu_naissance || '—')}</span>
            </div>
            <div class="detail-item">
                <label>Sexe</label>
                <span>${isFemme ? '♀ Fille' : '♂ Garçon'}</span>
            </div>
            <div class="detail-item">
                <label>Pédiatre assigné</label>
                <span>${esc(pediatre)}</span>
            </div>
        </div>

        <div class="section-title" style="margin-top:18px;">Parent</div>
        <div class="parent-info-box">
            <div class="pib-icon"><i class="fa-solid fa-user"></i></div>
            <div>
                <div class="pib-name">${esc(b.nom_parent || '—')}</div>
                <div class="pib-sub">${esc(b.email_parent || '')} ${b.tel_parent ? '· ' + esc(b.tel_parent) : ''}</div>
            </div>
        </div>
    `;

            // Footer
            document.getElementById('mFooter').innerHTML = `
       
        <form method="POST" style="flex:1"
              onsubmit="return confirm('Supprimer ce bébé définitivement ?')">
            <input type="hidden" name="id"        value="${b.id_bebe}">
            <input type="hidden" name="parent_id" value="${parentId}">
            <input type="hidden" name="action"    value="supprimer_bebe">
            <button type="submit" class="mf-btn red" style="width:100%">
                <i class="fa-solid fa-trash"></i> Supprimer
            </button>
        </form>`;

            document.getElementById('modalBackdrop').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() { document.getElementById('modalBackdrop').classList.remove('open'); document.body.style.overflow = ''; }
        function closeOut(e) { if (e.target === document.getElementById('modalBackdrop')) closeModal(); }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        function fmtDate(d) {
            if (!d) return '—';
            const [y, m, j] = d.split('-'); return `${j}/${m}/${y}`;
        }
        function esc(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
    </script>
</body>

</html>