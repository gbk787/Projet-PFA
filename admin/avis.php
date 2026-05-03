<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tinysteps");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Supprimer un avis
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM avis WHERE id = " . (int) $_GET['delete']);
    header("Location: avis.php");
    exit();
}

// ── TRI ────────────────────────────────────────────────
$sort = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'asc' : 'desc';
$next = $sort === 'desc' ? 'asc' : 'desc';
$icon = $sort === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down';

// ── RÉCUPÉRATION DES AVIS ─────────────────────────────────────
$avis = $conn->query("
    SELECT 
        a.id,
        a.note,
        a.commentaire,
        a.date_creation,
        p.nom_prenom   AS parent_nom,
        CONCAT('Dr. ', m.prenom, ' ', m.nom) AS medecin_nom,
        m.cabinet      AS medecin_cabinet
    FROM avis a
    JOIN parent  p ON a.parent_id  = p.id_parent
    JOIN medecin m ON a.medecin_id = m.id
    ORDER BY a.date_creation $sort
");
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Avis — TinySteps Admin</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="styles/avis.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>
    <div class="admin-layout">

        <aside class="sidebar">
            <?php include 'sidebar.php'; ?>
        </aside>

        <main class="admin-main">

            <div class="admin-header">
                <h1>Gestion des Avis</h1>
                <div style="display:flex; align-items:center; gap:16px;">
                    <div class="admin-profile">
                        <a href="profile.php" class="profile-btn">

                            <div class="profile-avatar">
                                <?= strtoupper(substr($_SESSION['admin_nom'], 0, 1)) ?>
                            </div>
                            <i class="fa-solid fa-chevron-down" style="font-size:0.75rem; color:#888;"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h2><i class="fas fa-comment-dots"></i> Tous les avis</h2>
                    <span><?= $avis->num_rows ?> résultat(s)</span>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Parent</th>
                            <th>Médecin</th>
                            <th>Cabinet</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>
                                <a href="avis.php?sort=<?= $next ?>" class="sort-link">
                                    Date <i class="fa <?= $icon ?>"></i>
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($avis->num_rows === 0): ?>
                            <tr>
                                <td colspan="8" style="text-align:center; color:#aaa; padding:30px;">
                                    <i class="fas fa-inbox" style="font-size:2rem; display:block; margin-bottom:8px;"></i>
                                    Aucun avis trouvé.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $avis->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>

                                    <td class="user-n">
                                        <span class="user-avatar">
                                            <?= strtoupper(substr($row['parent_nom'], 0, 1)) ?>
                                        </span>
                                        <span class="user-name">
                                            <?= htmlspecialchars($row['parent_nom']) ?>
                                        </span>
                                    </td>

                                    <td><?= htmlspecialchars($row['medecin_nom']) ?></td>

                                    <td><?= htmlspecialchars($row['medecin_cabinet']) ?></td>

                                    <td>
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fa-<?= $i <= $row['note'] ? 'solid' : 'regular' ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </td>

                                    <td class="comment-cell" title="<?= htmlspecialchars($row['commentaire']) ?>">
                                        <?= htmlspecialchars($row['commentaire']) ?>
                                    </td>

                                    <td><?= date('d M Y', strtotime($row['date_creation'])) ?></td>

                                    <td>
                                        <a href="avis.php?delete=<?= $row['id'] ?>&sort=<?= $sort ?>"
                                            class="btn-action btn-delete" title="Supprimer"
                                            onclick="return confirm('Supprimer définitivement cet avis ?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</body>

</html>