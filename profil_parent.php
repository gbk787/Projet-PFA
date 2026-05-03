<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: connexion.php");
    exit;
}
require_once 'connexion_bd.php';
$id_parent = $_SESSION['user_id'];

// Infos parent
$nom_prenom = $email = $telephone = $cin = $ncnam = '—';
$st = $conn->prepare("SELECT nom_prenom,email,telephone,cin,ncnam FROM parent WHERE id_parent=? LIMIT 1");
$st->bind_param("i", $id_parent);
$st->execute();
if ($p = $st->get_result()->fetch_assoc()) {
    $nom_prenom = $p['nom_prenom'];
    $email = $p['email'];
    $telephone = $p['telephone'];
    $cin = $p['cin'];
    $ncnam = $p['ncnam'] ?? '—';
}
$st->close();

// Bébé
$id_bebe = null;
$st2 = $conn->prepare("SELECT id_bebe FROM bebe WHERE id_parent=? LIMIT 1");
$st2->bind_param("i", $id_parent);
$st2->execute();
if ($b = $st2->get_result()->fetch_assoc())
    $id_bebe = $b['id_bebe'];
$st2->close();

// Stats
$nb_rdv = $nb_vaccins = 0;
if ($id_bebe) {
    $r = $conn->query("SELECT COUNT(*) AS nb FROM rendez_vous WHERE bebe_id=$id_bebe");
    if ($r)
        $nb_rdv = $r->fetch_assoc()['nb'];
    $r2 = $conn->query("SELECT COUNT(*) AS nb FROM vaccination WHERE bebe_id=$id_bebe AND statut='effectue'");
    if ($r2)
        $nb_vaccins = $r2->fetch_assoc()['nb'];
}

// Notifications non lues
$nb_notif_non_lues = 0;
$r3 = $conn->query("SELECT COUNT(*) AS nb FROM notification WHERE destinataire_id=$id_parent AND role='parent' AND lu=0");
if ($r3)
    $nb_notif_non_lues = $r3->fetch_assoc()['nb'];

// Message de confirmation
$msg_success = '';
$msg_error = '';
if (isset($_GET['updated']) && $_GET['updated'] === '1')
    $msg_success = 'Profil mis à jour avec succès.';
if (isset($_GET['pwd']) && $_GET['pwd'] === 'ok')
    $msg_success = 'Mot de passe modifié avec succès.';
if (isset($_GET['error']))
    $msg_error = htmlspecialchars($_GET['error']);

$conn->close();
$parent_nom = $_SESSION['user_nom'] ?? $nom_prenom;
$parent_email = $_SESSION['user_email'] ?? $email;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>TinySteps — Mon Profil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style_profilP.css">
    <link rel="icon" href="images/logo.png">
</head>

<body>

    <!-- ══ SIDEBAR ══ -->
    <aside class="sidebar">
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
            <a href="profil_parent.php" class="active"><i class="fas fa-user"></i> Profil</a>
            <a href="recherche_pediatre.php"><i class="fas fa-stethoscope"></i> Recherche pédiatre</a>
            <a href="croissance_simple.php"><i class="fas fa-chart-line"></i> Courbe de croissance</a>
            <a href="calendrier_vaccination.php"><i class="fas fa-syringe"></i> Calendrier vaccinal</a>
            <a href="conseils.php"><i class="fas fa-lightbulb"></i> Conseils personnalisés</a>
        </nav>
        <div class="sidebar-footer">
            <div class="parent-info">
                <div class="parent-avatar"><?= strtoupper(substr($parent_nom, 0, 1)) ?></div>
                <div>
                    <div class="parent-name"><?= htmlspecialchars($parent_nom) ?></div>
                    <div class="parent-email"><?= htmlspecialchars($parent_email) ?></div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- ══ PAGE WRAPPER ══ -->
    <div class="page-wrapper">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Mon Profil</h1>
                <p>Gérez vos informations personnelles et préférences</p>
            </div>
            <div class="topbar-right">
                <span class="today-pill"><i class="fas fa-calendar-day"></i> <?= date('d/m/Y') ?></span>
                <div class="notif-wrapper">
                    <button class="icon-btn" id="btn-notif" title="Notifications" onclick="toggleNotif()">
                        <i class="fas fa-bell"></i>
                        <span class="badge-notif" id="notifBadge"
                            style="<?= $nb_notif_non_lues > 0 ? '' : 'display:none' ?>">
                            <?= $nb_notif_non_lues > 9 ? '9+' : $nb_notif_non_lues ?>
                        </span>
                    </button>
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
                </div>
            </div>
        </div>

        <!-- Main -->
        <div class="main">

            <?php if ($msg_success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg_success ?></div>
            <?php endif; ?>
            <?php if ($msg_error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $msg_error ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--amber-l)"><i class="fas fa-calendar-check"
                            style="color:var(--amber)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--amber)"><?= $nb_rdv ?></div>
                        <div class="stat-lbl">Rendez-vous</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--green-l)"><i class="fas fa-syringe"
                            style="color:var(--green)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--green)"><?= $nb_vaccins ?></div>
                        <div class="stat-lbl">Vaccins effectués</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--primary-l)"><i class="fas fa-heart"
                            style="color:var(--blue-main)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--blue-main)">—</div>
                        <div class="stat-lbl">Jours d'activité</div>
                    </div>
                </div>
            </div>

            <!-- Profile Grid -->
            <div class="profile-grid">

                <!-- Colonne gauche : fiche identité -->
                <div class="card">
                    <div class="profile-photo">
                        <div class="avatar-circle"><?= strtoupper(mb_substr($nom_prenom, 0, 1)) ?></div>
                        <div class="profile-name"><?= htmlspecialchars($nom_prenom) ?></div>
                        <div class="profile-role">Parent</div>
                    </div>

                    <div class="card-header">
                        <i class="fas fa-id-card"></i>
                        <h3>Informations personnelles</h3>
                    </div>

                    <ul class="info-list">
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($email) ?></span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span class="info-label">Téléphone</span>
                            <span class="info-value"><?= htmlspecialchars($telephone) ?></span>
                        </li>
                        <li>
                            <i class="fas fa-id-card"></i>
                            <span class="info-label">CIN</span>
                            <span class="info-value"><?= htmlspecialchars($cin) ?></span>
                        </li>
                        <li>
                            <i class="fas fa-notes-medical"></i>
                            <span class="info-label">N° CNAM</span>
                            <span class="info-value"><?= htmlspecialchars($ncnam) ?></span>
                        </li>
                    </ul>
                </div>

                <!-- Colonne droite : onglets -->
                <div class="card">
                    <div class="tabs">
                        <button class="tab active" onclick="switchTab('informations',this)">
                            <i class="fas fa-edit"></i> Informations
                        </button>
                        <button class="tab" onclick="switchTab('securite',this)">
                            <i class="fas fa-lock"></i> Sécurité
                        </button>
                    </div>

                    <!-- Onglet Informations -->
                    <div class="tab-content" id="informations">
                        <form method="POST" action="modifier_profil.php">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">
                            </div>
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input type="tel" name="telephone" value="<?= htmlspecialchars($telephone) ?>">
                            </div>
                            <div class="form-group">
                                <label>Bio <span style="color:var(--muted);font-weight:400">(optionnel)</span></label>
                                <textarea name="bio" rows="3" placeholder="Quelques mots sur vous..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Rappel vaccin</label>
                                <select name="rappel">
                                    <option value="7">7 jours avant</option>
                                    <option value="3" selected>3 jours avant</option>
                                    <option value="1">1 jour avant</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="reset" class="btn btn-outline btn-sm">Annuler</button>
                                <button type="submit" class="btn btn-sm"><i class="fas fa-save"></i>
                                    Enregistrer</button>
                            </div>
                        </form>
                    </div>

                    <!-- Onglet Sécurité -->
                    <div class="tab-content" id="securite" style="display:none">
                        <form method="POST" action="changer_mdp.php">
                            <div class="form-group">
                                <label>Mot de passe actuel</label>
                                <input type="password" name="mdp_actuel" placeholder="••••••••">
                            </div>
                            <div class="form-group">
                                <label>Nouveau mot de passe</label>
                                <input type="password" name="nouveau_mdp" id="newPwd" placeholder="••••••••">
                            </div>
                            <div class="form-group">
                                <label>Confirmer le mot de passe</label>
                                <input type="password" name="confirmer_mdp" id="confirmPwd" placeholder="••••••••">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-sm"><i class="fas fa-key"></i> Changer le mot de
                                    passe</button>
                            </div>
                        </form>

                        <div class="danger-zone">
                            <h4><i class="fas fa-exclamation-triangle"></i> Zone de danger</h4>
                            <p>Supprimer définitivement votre compte et toutes vos données. Cette action est
                                irréversible.</p>
                            <button class="btn btn-danger btn-sm"
                                onclick="return confirm('Êtes-vous sûr ? Cette action est irréversible.')">
                                <i class="fas fa-trash-alt"></i> Supprimer mon compte
                            </button>
                        </div>
                    </div>
                </div>

            </div><!-- /profile-grid -->
        </div><!-- /main -->
    </div><!-- /page-wrapper -->

    <script>
        function switchTab(name, btn) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            btn.classList.add('active');
            document.getElementById(name).style.display = 'block';
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
            const btn = document.getElementById('btn-notif');
            if (panel.classList.contains('open') && !panel.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
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
                    ${unread ? `<div class="notif-unread-dot" title="Marquer lu" onclick="marquerLu(${n.id}, event)"></div>` : ''}
                </div>`;
                    }).join('');
                })
                .catch(err => console.error('Erreur notifications:', err));
        }

        function marquerLu(id, e) {
            e.stopPropagation();
            fetch(`get_notifications_parent.php?action=mark_read&notif_id=${id}`)
                .then(r => r.json()).then(res => { if (res.success) loadNotifications(); });
        }

        function marquerToutLu() {
            fetch('get_notifications_parent.php?action=mark_all_read')
                .then(r => r.json()).then(res => { if (res.success) loadNotifications(); });
        }

        loadNotifications();
        setInterval(loadNotifications, 30000);
    </script>
</body>

</html>