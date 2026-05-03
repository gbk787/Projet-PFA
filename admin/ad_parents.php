<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tinysteps");
$conn->set_charset("utf8mb4");

if (!isset($_SESSION['admin_id'])) {
  header("Location: login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int) $_POST['id'];
  if ($_POST['action'] === 'supprimer')
    $conn->query("DELETE FROM parent WHERE id_parent=$id");
  header("Location: ad_parents.php");
  exit();
}

$search = trim($_GET['search'] ?? '');
$where = $search !== '' ? "WHERE p.nom_prenom LIKE '%$search%' OR p.email LIKE '%$search%' OR p.telephone LIKE '%$search%'" : '';

$parents = $conn->query("
    SELECT p.*, COUNT(DISTINCT b.id_bebe) AS nb_bebes, COUNT(DISTINCT r.id) AS nb_rdv
    FROM parent p
    LEFT JOIN bebe b ON b.id_parent=p.id_parent
    LEFT JOIN rendez_vous r ON r.bebe_id=b.id_bebe
    $where GROUP BY p.id_parent ORDER BY p.id_parent DESC
")->fetch_all(MYSQLI_ASSOC);

$total = $conn->query("SELECT COUNT(*) FROM parent")->fetch_row()[0];
$totalBebes = $conn->query("SELECT COUNT(*) FROM bebe")->fetch_row()[0];
$totalRdv = $conn->query("SELECT COUNT(*) FROM rendez_vous")->fetch_row()[0];

$allBebes = $conn->query("SELECT b.*, m.nom AS nom_ped, m.prenom AS prenom_ped FROM bebe b LEFT JOIN medecin m ON b.pediatre_id=m.id")->fetch_all(MYSQLI_ASSOC);
$byParent = [];
foreach ($allBebes as $b)
  $byParent[$b['id_parent']][] = $b;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Parents — TinySteps Admin</title>
  <link rel="stylesheet" href="styles/sidebar.css">
  <link rel="stylesheet" href="styles/parents.css">
  <link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:wght@700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>
  <div class="admin-layout">
    <aside class="sidebar"><?php include 'sidebar.php'; ?></aside>
    <main class="admin-main">

      <div class="top-bar">
        <div>
          <h2 class="page-title">Gestion des Parents</h2>
          <p class="page-sub"><?= $total ?> parent<?= $total > 1 ? 's' : '' ?> enregistré<?= $total > 1 ? 's' : '' ?>
          </p>
        </div>
        <form method="GET" class="sf">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" name="search" placeholder="Nom, email, téléphone…"
            value="<?= htmlspecialchars($search) ?>">
        </form>
      </div>



      <div class="tc">

        <div class="tw">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Parent</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>CIN</th>
                <th>Bébés</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($parents)): ?>
                <tr class="empty-r">
                  <td colspan="8"><i class="fa-solid fa-users-slash"></i>Aucun parent trouvé.</td>
                </tr>
              <?php else:
                foreach ($parents as $p):
                  $nb = (int) $p['nb_bebes'];
                  $rdv = (int) $p['nb_rdv']; ?>
                  <tr>
                    <td style="color:var(--s400);font-size:.75rem">#<?= $p['id_parent'] ?></td>
                    <td>
                      <div class="pc">
                        <div class="av"><?= strtoupper(substr($p['nom_prenom'] ?? '?', 0, 1)) ?></div>
                        <div>
                          <div class="pc-n"><?= htmlspecialchars($p['nom_prenom']) ?></div>

                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($p['email'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($p['telephone'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($p['cin'] ?: '—') ?></td>
                    <td><span class="badge <?= $nb > 0 ? 'baby' : 'zero' ?>"><i class="fa-solid fa-baby"></i>
                        <?= $nb ?></span></td>

                    <td>
                      <div class="actions">
                        <button class="bic v" title="Profil" onclick='openModal(<?= json_encode($p) ?>)'><i
                            class="fa-solid fa-eye"></i></button>
                        <a class="bic bb" href="ad_bebes.php?parent_id=<?= $p['id_parent'] ?>" title="Bébés"><i
                            class="fa-solid fa-baby"></i></a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce parent ?')">
                          <input type="hidden" name="id" value="<?= $p['id_parent'] ?>">
                          <input type="hidden" name="action" value="supprimer">
                          <button type="submit" class="bic d" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <div class="overlay" id="overlay" onclick="co(event)">
    <div class="modal">
      <div class="mh">
        <button class="mc" onclick="cm()"><i class="fa-solid fa-xmark"></i></button>
        <div class="mav" id="mAv"></div>
        <div class="mn" id="mN"></div>
        <div class="ms" id="mS"></div>
      </div>
      <div class="mb" id="mB"></div>
      <div class="mfoot" id="mF"></div>
    </div>
  </div>

  <script>
    const BD = <?= json_encode($byParent) ?>;
    function openModal(p) {
      document.getElementById('mAv').textContent = (p.nom_prenom || '?')[0].toUpperCase();
      document.getElementById('mN').textContent = p.nom_prenom || '—';
      document.getElementById('mS').textContent = 'Parent · ID #' + p.id_parent;
      const ff = [{ l: 'Email', v: p.email || '—' }, { l: 'Téléphone', v: p.telephone || '—' }, { l: 'CIN', v: p.cin || '—' }, { l: 'N° CNAM', v: p.ncnam || '—' }];
      const bebes = BD[p.id_parent] || [];
      let h = `<div class="msec">Informations</div><div class="mg">${ff.map(f => `<div class="mf"><label>${f.l}</label><span>${esc(f.v)}</span></div>`).join('')}</div>
    <div class="msec">Bébés (${bebes.length})</div>`;
      h += bebes.length ? bebes.map(b => {
        const s = (b.sexe || '').toLowerCase() === 'f' ? 'f' : 'm';
        const pd = b.nom_ped ? `Dr ${esc(b.prenom_ped)} ${esc(b.nom_ped)}` : '—';
        return `<div class="bchip"><div class="bch-ic ${s}"><i class="fa-solid fa-baby"></i></div><div><div class="bch-n">${esc(b.nom_prenom)}</div><div class="bch-i">Né(e) ${fd(b.date_naissance)} · ${pd}</div></div></div>`;
      }).join('') : `<p style="color:var(--s400);font-size:.82rem">Aucun bébé.</p>`;
      document.getElementById('mB').innerHTML = h;
      document.getElementById('mF').innerHTML = `
    <a href="ad_bebes.php?parent_id=${p.id_parent}" class="mbtn blue"><i class="fa-solid fa-baby"></i> Bébés</a>
    <form method="POST" style="flex:1" onsubmit="return confirm('Supprimer ?')">
      <input type="hidden" name="id" value="${p.id_parent}"><input type="hidden" name="action" value="supprimer">
      <button type="submit" class="mbtn red" style="width:100%"><i class="fa-solid fa-trash"></i> Supprimer</button>
    </form>`;
      document.getElementById('overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function cm() { document.getElementById('overlay').classList.remove('open'); document.body.style.overflow = ''; }
    function co(e) { if (e.target === document.getElementById('overlay')) cm(); }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cm(); });
    function fd(d) { if (!d) return '—'; const [y, m, j] = d.split('-'); return `${j}/${m}/${y}`; }
    function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
  </script>
</body>

</html>