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
    $id = (int) $_POST['id'];
    $action = $_POST['action'];

    if ($action === 'accepter') {
        $conn->query("UPDATE medecin SET statut = 'accepte' WHERE id = $id");
    } elseif ($action === 'refuser') {
        $conn->query("UPDATE medecin SET statut = 'refuse' WHERE id = $id");
    } elseif ($action === 'supprimer') {
        $conn->query("DELETE FROM medecin WHERE id = $id");
    }

    header("Location: pediatres.php");
    exit();
}

// ── Filtres ───────────────────────────────────────────────────────────────────
$filtre = $_GET['filtre'] ?? 'tous';
$search = trim($_GET['search'] ?? '');

$where = [];
if ($filtre === 'nouveau')
    $where[] = "(statut = 'en_attente' OR statut IS NULL OR statut = '')";
if ($filtre === 'accepte')
    $where[] = "statut = 'accepte'";
if ($filtre === 'refuse')
    $where[] = "statut = 'refuse'";
if ($search !== '')
    $where[] = "(nom LIKE '%$search%' OR prenom LIKE '%$search%' OR email LIKE '%$search%')";

$sql = "SELECT * FROM medecin";
if ($where)
    $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY id DESC";

$result = $conn->query($sql);
$pediatres = $result->fetch_all(MYSQLI_ASSOC);

// Compte par statut
$cNouveaux = $conn->query("SELECT COUNT(*) FROM medecin WHERE statut = 'en_attente' OR statut IS NULL OR statut = ''")->fetch_row()[0];
$cAcceptes = $conn->query("SELECT COUNT(*) FROM medecin WHERE statut = 'accepte'")->fetch_row()[0];
$cRefuses = $conn->query("SELECT COUNT(*) FROM medecin WHERE statut = 'refuse'")->fetch_row()[0];
$cTous = $conn->query("SELECT COUNT(*) FROM medecin")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Pédiatres — TinySteps</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="styles/pediatres.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>

    <div class="admin-layout">
        <aside class="sidebar">
            <?php include 'sidebar.php'; ?>
        </aside>

        <main class="admin-main">

            <!-- Header -->
            <div class="page-header">
                <h2 class="page-title">

                    Gestion des Pédiatres
                </h2>
                <form method="GET" class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Rechercher un pédiatre…"
                        value="<?php echo htmlspecialchars($search); ?>">
                    <?php if ($filtre !== 'tous'): ?>
                        <input type="hidden" name="filtre" value="<?php echo $filtre; ?>">
                    <?php endif; ?>
                </form>
            </div>

            <!-- Filtres -->
            <div class="stat-chips">
                <a href="?filtre=tous" class="chip tous <?php echo $filtre === 'tous' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-list"></i> Tous <span>
                        <?php echo $cTous; ?>
                    </span>
                </a>
                <a href="?filtre=nouveau" class="chip nouveau <?php echo $filtre === 'nouveau' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-clock"></i> En attente <span>
                        <?php echo $cNouveaux; ?>
                    </span>
                </a>
                <a href="?filtre=accepte" class="chip accepte <?php echo $filtre === 'accepte' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-check-circle"></i> Acceptés <span>
                        <?php echo $cAcceptes; ?>
                    </span>
                </a>
                <a href="?filtre=refuse" class="chip refuse <?php echo $filtre === 'refuse' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-times-circle"></i> Refusés <span>
                        <?php echo $cRefuses; ?>
                    </span>
                </a>
            </div>

            <!-- Tableau -->
            <div class="table-card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pédiatre</th>
                                <th>Téléphone</th>
                                <th>Expérience</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pediatres)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty">
                                            <i class="fa-solid fa-user-doctor"></i>
                                            <p>Aucun pédiatre trouvé.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pediatres as $p):
                                    $statut = $p['statut'] ?? 'en_attente';
                                    if (!in_array($statut, ['accepte', 'refuse']))
                                        $statut = 'en_attente';
                                    $initiale = strtoupper(substr($p['prenom'] ?? $p['nom'] ?? '?', 0, 1));
                                    $isFemme = ($p['sexe'] ?? '') === 'Femme';
                                    $isNew = $statut === 'en_attente';
                                    ?>
                                    <tr>
                                        <td style="color:var(--gray-400); font-size:.82rem;">#
                                            <?php echo $p['id']; ?>
                                        </td>
                                        <td>
                                            <div class="doc-info">
                                                <div class="avatar <?php echo $isFemme ? 'femme' : ''; ?>">
                                                    <?php echo $initiale; ?>
                                                </div>
                                                <div>
                                                    <div class="doc-name">Dr
                                                        <?php echo htmlspecialchars($p['prenom'] . ' ' . $p['nom']); ?>
                                                    </div>
                                                    <div class="doc-mail">
                                                        <?php echo htmlspecialchars($p['email'] ?? '—'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($p['tel'] ?? '—'); ?>
                                        </td>
                                        <td>
                                            <?php echo $p['ann_expr'] ? $p['ann_expr'] . ' ans' : '—'; ?>
                                        </td>
                                        <td>
                                            <?php if ($statut === 'en_attente'): ?>
                                                <span class="badge en_attente"><i class="fa-solid fa-clock"></i> En attente</span>
                                            <?php elseif ($statut === 'accepte'): ?>
                                                <span class="badge accepte"><i class="fa-solid fa-check"></i> Accepté</span>
                                            <?php else: ?>
                                                <span class="badge refuse"><i class="fa-solid fa-xmark"></i> Refusé</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <!-- Voir profil -->
                                                <button type="button" class="btn-icon view" title="Voir le profil"
                                                    onclick='openModal(<?php echo json_encode($p); ?>)'>
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>

                                                <?php if ($isNew): ?>
                                                    <!-- Accepter -->
                                                    <form method="POST" style="display:inline"
                                                        onsubmit="showToast('Pédiatre accepté avec succès.','success')">
                                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                        <input type="hidden" name="action" value="accepter">
                                                        <button type="submit" class="btn-icon accept" title="Accepter">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <!-- Refuser -->
                                                    <form method="POST" style="display:inline"
                                                        onsubmit="showToast('Compte refusé.','error')">
                                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                        <input type="hidden" name="action" value="refuser">
                                                        <button type="submit" class="btn-icon refuse" title="Refuser">
                                                            <i class="fa-solid fa-xmark"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Supprimer -->
                                                <form method="POST" style="display:inline"
                                                    onsubmit="return confirm('Supprimer ce pédiatre définitivement ?')">
                                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                    <input type="hidden" name="action" value="supprimer">
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

    <!-- ── Modal Profil ─────────────────────────────────────────────────────────── -->
    <div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOutside(event)">
        <div class="modal" id="modalBox">
            <div class="modal-hero">
                <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
                <div class="modal-avatar" id="mAvatar"></div>
                <div class="modal-name" id="mName"></div>
                <div class="modal-spec" id="mSpec"></div>
            </div>
            <div class="modal-body">
                <div class="info-grid" id="mGrid"></div>
                <div class="modal-actions" id="mActions"></div>
            </div>
        </div>
    </div>

    <!-- ── Toast ── -->
    <div class="toast" id="toast">
        <i class="fa-solid fa-circle-check" id="toastIcon"></i>
        <span id="toastMsg"></span>
    </div>

    <script>
        // ── Modal ─────────────────────────────────────────────────────────────────────
        function openModal(p) {
            const initiale = (p.prenom || p.nom || '?')[0].toUpperCase();
            const isFemme = p.sexe === 'Femme';
            const statut = p.statut && ['accepte', 'refuse'].includes(p.statut) ? p.statut : 'en_attente';
            const isNew = statut === 'en_attente';

            document.getElementById('mAvatar').textContent = initiale;
            document.getElementById('mAvatar').style.background = isFemme ? 'rgba(252,231,243,.3)' : 'rgba(255,255,255,.2)';
            document.getElementById('mName').textContent = 'Dr ' + (p.prenom || '') + ' ' + (p.nom || '');
            document.getElementById('mSpec').textContent = p.specialite || 'Pédiatre';

            const fields = [
                { label: 'Email', val: p.email || '—' },
                { label: 'Téléphone', val: p.tel || '—' },
                { label: 'Sexe', val: p.sexe || '—' },
                { label: 'Date naissance', val: p.date_nai ? formatDate(p.date_nai) : '—' },
                { label: 'Inscription', val: p.date_inscription ? formatDate(p.date_inscription) : '—' },
                { label: 'Expérience', val: p.ann_expr ? p.ann_expr + ' ans' : '—' },
                { label: 'N° inscription', val: p.num_inscri_med || '—', full: true },
                { label: 'Diplôme', val: p.diplome || '—', full: true },
            ];

            document.getElementById('mGrid').innerHTML = fields.map(f =>
                `<div class="info-item${f.full ? ' full' : ''}">
            <label>${f.label}</label>
            <span>${escHtml(f.val)}</span>
         </div>`
            ).join('');

            let actions = '';
            if (isNew) {
                actions += `
        <form method="POST" style="flex:1">
            <input type="hidden" name="id"     value="${p.id}">
            <input type="hidden" name="action" value="accepter">
            <button type="submit" class="btn green" style="width:100%">
                <i class="fa-solid fa-check"></i> Accepter
            </button>
        </form>
        <form method="POST" style="flex:1">
            <input type="hidden" name="id"     value="${p.id}">
            <input type="hidden" name="action" value="refuser">
            <button type="submit" class="btn amber" style="width:100%">
                <i class="fa-solid fa-xmark"></i> Refuser
            </button>
        </form>`;
            }
            actions += `
        <form method="POST" style="flex:1"
              onsubmit="return confirm('Supprimer ce pédiatre définitivement ?')">
            <input type="hidden" name="id"     value="${p.id}">
            <input type="hidden" name="action" value="supprimer">
            <button type="submit" class="btn red" style="width:100%">
                <i class="fa-solid fa-trash"></i> Supprimer
            </button>
        </form>`;

            document.getElementById('mActions').innerHTML = actions;
            document.getElementById('modalBackdrop').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('modalBackdrop').classList.remove('open');
            document.body.style.overflow = '';
        }
        function closeModalOutside(e) {
            if (e.target === document.getElementById('modalBackdrop')) closeModal();
        }

        // ── Toast ─────────────────────────────────────────────────────────────────────
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            const i = document.getElementById('toastIcon');
            document.getElementById('toastMsg').textContent = msg;
            t.className = `toast ${type}`;
            i.className = type === 'success'
                ? 'fa-solid fa-circle-check'
                : 'fa-solid fa-circle-xmark';
            setTimeout(() => t.classList.add('show'), 50);
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        // ── Helpers ───────────────────────────────────────────────────────────────────
        function formatDate(d) {
            if (!d) return '—';
            const parts = d.split('-');
            if (parts.length !== 3) return d;
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        function escHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // Échap ferme le modal
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    </script>
</body>

</html>