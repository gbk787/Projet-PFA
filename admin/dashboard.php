<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tinysteps");

// Protection admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Stats
$totalPediatres = $conn->query("SELECT COUNT(*) as total FROM medecin WHERE statut = 'accepte'")->fetch_assoc()['total'];
$totalPediatrEn = $conn->query("SELECT COUNT(*) as total FROM medecin WHERE statut = 'en_attente'")->fetch_assoc()['total'];
$totalPediatrRef = $conn->query("SELECT COUNT(*) as total FROM medecin WHERE statut = 'refuse'")->fetch_assoc()['total'];
$totalPediatrAll = $conn->query("SELECT COUNT(*) as total FROM medecin")->fetch_assoc()['total'];

// Graphique — inscriptions pédiatres des 6 derniers mois
$months = [];
$countsPed = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));

    $cm = $conn->query("SELECT COUNT(*) as total FROM medecin WHERE DATE_FORMAT(date_inscription,'%Y-%m')='$month'")->fetch_assoc()['total'];

    $months[] = $label;
    $countsPed[] = (int) $cm;
}

// ── Notifications : pédiatres en attente d'approbation ──────────────────────
$notif_result = $conn->query("
    SELECT 
        id,
        nom,
        prenom,
        email,
        specialite,
        date_inscription
    FROM medecin
    WHERE statut = 'en_attente'
    ORDER BY date_inscription DESC
");
$notif_count = $notif_result ? $notif_result->num_rows : 0;
$notifications = $notif_result ? $notif_result->fetch_all(MYSQLI_ASSOC) : [];

// ── Action Accepter / Refuser (AJAX ou POST direct) ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['medecin_id'])) {
    $id = (int) $_POST['medecin_id'];
    $action = $_POST['action'];

    if ($action === 'accepter') {
        $conn->query("UPDATE medecin SET statut = 'accepte' WHERE id = $id");
    } elseif ($action === 'refuser') {
        $conn->query("UPDATE medecin SET statut = 'refuse' WHERE id = $id");
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success' => true]);
        exit();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard — TinySteps</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <div id="toast"></div>

    <div class="admin-layout">
        <aside class="sidebar">
            <?php include 'sidebar.php'; ?>
        </aside>

        <!-- MAIN -->
        <main class="admin-main">

            <!-- Header -->
            <div class="admin-header">
                <h1>Tableau de bord</h1>
                <div style="display:flex; align-items:center; gap:16px;">

                    <!-- ── Cloche notification ─────────────────────── -->
                    <div class="notif-wrapper" id="notifWrapper">

                        <button class="notif-btn" onclick="toggleNotif()" title="Pédiatres en attente">
                            <i class="fa-solid fa-user-doctor"></i>
                            <?php if ($notif_count > 0): ?>
                                <span class="notif-badge" id="notifBadge">
                                    <?= $notif_count ?>
                                </span>
                            <?php endif; ?>
                        </button>

                        <div class="notif-dropdown" id="notifDropdown">

                            <div class="notif-header">
                                <span><i class="fa-solid fa-user-doctor" style="margin-right:6px"></i>Nouveaux
                                    pédiatres</span>
                                <span class="notif-count" id="notifHeaderCount">
                                    <?= $notif_count ?>
                                </span>
                            </div>

                            <div id="notifList">
                                <?php if ($notif_count > 0): ?>
                                    <?php foreach ($notifications as $n): ?>
                                        <?php
                                        $initials = strtoupper(substr($n['prenom'], 0, 1) . substr($n['nom'], 0, 1));
                                        $fullname = htmlspecialchars($n['prenom'] . ' ' . $n['nom']);
                                        $spec = htmlspecialchars($n['specialite'] ?? 'Pédiatrie');
                                        ?>
                                        <div class="notif-item" id="notif-<?= $n['id'] ?>">
                                            <div class="notif-avatar"><?= $initials ?></div>
                                            <div class="notif-text">
                                                <strong>Dr <?= $fullname ?></strong>
                                                <span class="notif-spec"><?= $spec ?></span>
                                                <div class="notif-actions">
                                                    <button class="btn-accept"
                                                        onclick="handleAction(<?= $n['id'] ?>, 'accepter', this)">
                                                        <i class="fa-solid fa-check"></i> Accepter
                                                    </button>
                                                    <button class="btn-refuse"
                                                        onclick="handleAction(<?= $n['id'] ?>, 'refuser', this)">
                                                        <i class="fa-solid fa-xmark"></i> Refuser
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="notif-empty" id="emptyState">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>Aucun pédiatre en attente</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                    <!-- ────────────────────────────────────────────── -->

                    <!-- Profil -->
                    <div class="admin-profile">
                        <a href="profile.php" class="profile-btn">
                            <div class="profile-avatar">
                                <?= strtoupper(substr($_SESSION['admin_nom'], 0, 1)) ?>
                            </div>
                            <i class="fa-solid fa-chevron-down" style="font-size:0.75rem;color:#888;"></i>
                        </a>
                    </div>

                </div>
            </div>

            <!-- Stats pédiatres uniquement -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px;">

                <!-- Total pédiatres -->
                <div class="stat-card">
                    <div class="stat-icon" style="background:#EBF4F6;color:#4DA8DA">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <div class="stat-info">
                        <span>Total Pédiatres</span>
                        <strong><?= $totalPediatrAll ?></strong>
                    </div>
                </div>

                <!-- Pédiatres actifs -->
                <div class="stat-card">
                    <div class="stat-icon" style="background:#dcfce7;color:#16a34a">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="stat-info">
                        <span>Pédiatres actifs</span>
                        <strong><?= $totalPediatres ?></strong>
                    </div>
                </div>

                <!-- Pédiatres en attente -->
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef9c3;color:#ca8a04">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <span>En attente</span>
                        <strong><?= $totalPediatrEn ?></strong>
                        <small style="font-size:.72rem;color:#ca8a04;font-weight:500;">pédiatres</small>
                    </div>
                </div>

                <!-- Pédiatres refusés -->
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fee2e2;color:#dc2626">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <div class="stat-info">
                        <span>Refusés</span>
                        <strong><?= $totalPediatrRef ?></strong>
                        <small style="font-size:.72rem;color:#dc2626;font-weight:500;">pédiatres</small>
                    </div>
                </div>

            </div>

            <!-- Graphique pédiatres uniquement -->
            <div class="admin-card" style="margin-bottom:28px;">
                <div class="admin-card-header">
                    <h2>Inscriptions pédiatres — 6 derniers mois</h2>
                    <div style="display:flex;gap:16px;align-items:center;font-size:.8rem;color:#64748b;">
                        <span>
                            <span
                                style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#16a34a;margin-right:5px;"></span>
                            Pédiatres
                        </span>
                    </div>
                </div>
                <canvas id="inscrChart" height="100"></canvas>
            </div>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
            <script>
                const ctx = document.getElementById('inscrChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($months) ?>,
                        datasets: [
                            {
                                label: 'Pédiatres',
                                data: <?= json_encode($countsPed) ?>,
                                borderColor: '#16a34a',
                                backgroundColor: 'rgba(22,163,74,0.08)',
                                borderWidth: 2.5,
                                pointBackgroundColor: '#16a34a',
                                pointRadius: 5,
                                tension: 0.4,
                                fill: true,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                padding: 10,
                                cornerRadius: 8,
                                titleFont: { size: 12 },
                                bodyFont: { size: 12 },
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1, color: '#94a3b8' },
                                grid: { color: '#f1f5f9' }
                            },
                            x: {
                                ticks: { color: '#94a3b8' },
                                grid: { display: false }
                            }
                        }
                    }
                });
            </script>

        </main>
    </div>

    <!-- ── Scripts notification ─────────────────────────────────────────────── -->
    <script>
        function toggleNotif() {
            document.getElementById('notifDropdown').classList.toggle('open');
        }
        document.addEventListener('click', function (e) {
            const wrapper = document.getElementById('notifWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                document.getElementById('notifDropdown').classList.remove('open');
            }
        });

        let remaining = <?= $notif_count ?>;

        function handleAction(id, action, btn) {
            const card = document.getElementById('notif-' + id);
            const buttons = card.querySelectorAll('button');
            buttons.forEach(b => { b.disabled = true; b.style.opacity = '.5'; });

            const formData = new FormData();
            formData.append('action', action);
            formData.append('medecin_id', id);

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        card.style.transition = 'opacity .3s, max-height .35s';
                        card.style.overflow = 'hidden';
                        card.style.opacity = '0';
                        card.style.maxHeight = '0';
                        setTimeout(() => card.remove(), 380);

                        remaining--;
                        const badge = document.getElementById('notifBadge');
                        const headerCount = document.getElementById('notifHeaderCount');

                        if (remaining > 0) {
                            if (badge) badge.textContent = remaining;
                            if (headerCount) headerCount.textContent = remaining;
                        } else {
                            if (badge) badge.remove();
                            if (headerCount) headerCount.textContent = '0';
                            setTimeout(() => {
                                document.getElementById('notifList').innerHTML = `
                                    <div class="notif-empty">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>Aucun pédiatre en attente</p>
                                    </div>`;
                            }, 400);
                        }

                        const msg = action === 'accepter'
                            ? ' Pédiatre accepté avec succès'
                            : ' Pédiatre refusé';
                        showToast(msg);
                    }
                })
                .catch(() => {
                    buttons.forEach(b => { b.disabled = false; b.style.opacity = '1'; });
                    showToast(' Erreur, veuillez réessayer.');
                });
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }
    </script>

</body>

</html>