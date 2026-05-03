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

// ── 1. Bébé du parent
$nom_bb = '—';
$date_naissance = '—';
$age = '—';
$id_bebe = null;
$id_pediatre = null;

$stmt = $conn->prepare("SELECT id_bebe, nom_prenom, date_naissance, pediatre_id FROM bebe WHERE id_parent = ? LIMIT 1");
$stmt->bind_param("i", $id_parent);
$stmt->execute();
$res = $stmt->get_result();
if ($bebe = $res->fetch_assoc()) {
    $id_bebe = $bebe['id_bebe'];
    $id_pediatre = $bebe['pediatre_id'];
    $nom_bb = $bebe['nom_prenom'];
    $date_naissance = date('d/m/Y', strtotime($bebe['date_naissance']));
    $naissance = new DateTime($bebe['date_naissance']);
    $diff = (new DateTime())->diff($naissance);
    if ($diff->y > 0)
        $age = $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    elseif ($diff->m > 0)
        $age = $diff->m . ' mois';
    else
        $age = $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
}
$stmt->close();

// ── 2. Pédiatre associé
$nom_pediatre = '—';
$cabinet_med = '—';
if ($id_pediatre) {
    $stmt2 = $conn->prepare("SELECT CONCAT('Dr. ', nom, ' ', prenom) AS nom_med, cabinet FROM medecin WHERE id = ? LIMIT 1");
    $stmt2->bind_param("i", $id_pediatre);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($med = $res2->fetch_assoc()) {
        $nom_pediatre = $med['nom_med'];
        $cabinet_med = $med['cabinet'] ?? '—';
    }
    $stmt2->close();
}

// ── 3. Prochain rendez-vous
$date_rdv = '—';
$motif_rdv = '—';
$statut_rdv = '—';
$heure_rdv = '—';
if ($id_bebe) {
    $stmt3 = $conn->prepare("SELECT date_rdv, heure_debut, motif, statut FROM rendez_vous WHERE bebe_id = ? AND date_rdv >= CURDATE() ORDER BY date_rdv ASC, heure_debut ASC LIMIT 1");
    $stmt3->bind_param("i", $id_bebe);
    $stmt3->execute();
    $res3 = $stmt3->get_result();
    if ($rdv = $res3->fetch_assoc()) {
        $date_rdv = date('d/m/Y', strtotime($rdv['date_rdv']));
        $heure_rdv = substr($rdv['heure_debut'], 0, 5);
        $motif_rdv = $rdv['motif'] ?? '—';
        $statut_rdv = $rdv['statut'];
    }
    $stmt3->close();
}

// ── 4. Prochain vaccin
$nom_vaccin = '—';
$date_vaccin = '—';
$jours_vaccin = null;
if ($id_bebe) {
    $stmt4 = $conn->prepare("SELECT v.nom, va.date_prevue FROM vaccination va JOIN vaccin v ON va.vaccin_id = v.id WHERE va.bebe_id = ? AND va.statut != 'effectue' AND va.date_prevue >= CURDATE() ORDER BY va.date_prevue ASC LIMIT 1");
    $stmt4->bind_param("i", $id_bebe);
    $stmt4->execute();
    $res4 = $stmt4->get_result();
    if ($vac = $res4->fetch_assoc()) {
        $nom_vaccin = $vac['nom'];
        $d = new DateTime($vac['date_prevue']);
        $date_vaccin = $d->format('d/m/Y');
        $jours_vaccin = (new DateTime())->diff($d)->days;
    }
    $stmt4->close();
}

// ── 5. Stats
$nb_rdv_total = 0;
$nb_vaccins_effectues = 0;
$nb_notif_non_lues = 0;
if ($id_bebe) {
    $r = $conn->query("SELECT COUNT(*) AS nb FROM rendez_vous WHERE bebe_id=$id_bebe");
    if ($r)
        $nb_rdv_total = $r->fetch_assoc()['nb'];
    $r2 = $conn->query("SELECT COUNT(*) AS nb FROM vaccination WHERE bebe_id=$id_bebe AND statut='effectue'");
    if ($r2)
        $nb_vaccins_effectues = $r2->fetch_assoc()['nb'];
}
$r3 = $conn->query("SELECT COUNT(*) AS nb FROM notification WHERE destinataire_id=$id_parent AND role='parent' AND lu=0");
if ($r3)
    $nb_notif_non_lues = $r3->fetch_assoc()['nb'];

// ── 5. Notifications récentes
$notifications = [];
$stmt5 = $conn->prepare("SELECT type, message, lu, date_creation FROM notification WHERE destinataire_id = ? AND role = 'parent' ORDER BY date_creation DESC LIMIT 5");
$stmt5->bind_param("i", $id_parent);
$stmt5->execute();
$res5 = $stmt5->get_result();
while ($notif = $res5->fetch_assoc())
    $notifications[] = $notif;
$stmt5->close();

$parent_nom = $_SESSION['user_nom'] ?? $nom_prenom;
$parent_email = $_SESSION['user_email'] ?? $email;

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinySteps — Accueil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style_accueilP.css?v=999">
    <link rel="icon" href="images/logo1.png">
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
            <a href="accueil_parent.php" class="active"><i class="fas fa-home"></i> Accueil</a>
            <a href="profil_parent.php"><i class="fas fa-user"></i> Profil</a>
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
                <h1>Tableau de bord</h1>
                <p>Bonjour <?= htmlspecialchars($parent_nom) ?> — <?= (new DateTime())->format('d/m/Y') ?></p>
            </div>
            <div class="topbar-right">
                <span class="today-pill"><i class="fas fa-calendar-day"></i> <?= date('d/m/Y') ?></span>
                <div class="notif-wrapper">
                    <button class="icon-btn" id="btn-notif" title="Notifications" onclick="toggleNotif()">
                        <i class="fas fa-bell"></i>
                        <?php if ($nb_notif_non_lues > 0): ?>
                            <span class="badge-notif"
                                id="notifBadge"><?= $nb_notif_non_lues > 9 ? '9+' : $nb_notif_non_lues ?></span>
                        <?php else: ?>
                            <span class="badge-notif" id="notifBadge" style="display:none"></span>
                        <?php endif; ?>
                    </button>
                    <div class="notif-panel" id="notif-panel">
                        <div class="notif-panel-head">
                            <span><i class="fas fa-bell"></i> Notifications</span>
                            <span class="notif-panel-badge" id="notif-badge-txt">Chargement...</span>
                        </div>
                        <div class="notif-panel-body" id="notif-list">
                            <div class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>
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
                    <h2>Bienvenue, <?= htmlspecialchars($parent_nom) ?> !</h2>
                    <p>Suivez la santé de <strong><?= htmlspecialchars($nom_bb) ?></strong> en toute sérénité.</p>
                </div>
                <div class="hero-next">
                    <?php if ($date_rdv !== '—'): ?>
                        <div class="hero-next-label">Prochain RDV</div>
                        <div class="hero-next-time"><?= htmlspecialchars($heure_rdv) ?></div>
                        <div class="hero-next-name"><?= htmlspecialchars($date_rdv) ?></div>
                    <?php else: ?>
                        <div class="hero-next-label">Prochain RDV</div>
                        <div class="hero-next-time" style="font-size:1rem">Aucun</div>
                        <div class="hero-next-name">Pas de RDV prévu</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--primary-l)"><i class="fas fa-baby"
                            style="color:var(--blue-main)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--blue-main)"><?= htmlspecialchars($nom_bb) ?></div>
                        <div class="stat-lbl">Votre bébé</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--green-l)"><i class="fas fa-calendar-check"
                            style="color:var(--green)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--green)"><?= $nb_rdv_total ?></div>
                        <div class="stat-lbl">Rendez-vous</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--amber-l)"><i class="fas fa-syringe"
                            style="color:var(--amber)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--amber)"><?= $nb_vaccins_effectues ?></div>
                        <div class="stat-lbl">Vaccins effectués</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--red-l)"><i class="fas fa-bell"
                            style="color:var(--red)"></i></div>
                    <div>
                        <div class="stat-val" style="color:var(--red)"><?= $nb_notif_non_lues ?></div>
                        <div class="stat-lbl">Notifications</div>
                    </div>
                </div>
            </div>

            <!-- Grid principale -->
            <div class="grid-2">

                <!-- Bébé -->
                <div class="card">
                    <div class="card-head">
                        <div class="card-title"><i class="fas fa-baby"></i> Informations bébé</div>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Nom</div>
                                <div class="info-value"><?= htmlspecialchars($nom_bb) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Date de naissance</div>
                                <div class="info-value"><?= htmlspecialchars($date_naissance) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Âge</div>
                                <div class="info-value"><?= htmlspecialchars($age) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Groupe sanguin</div>
                                <div class="info-value">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pédiatre -->
                <div class="card">
                    <div class="card-head">
                        <div class="card-title"><i class="fas fa-user-md"></i> Mon pédiatre</div>
                    </div>
                    <div class="card-body">
                        <div class="info-grid" style="margin-bottom:14px">
                            <div class="info-item">
                                <div class="info-label">Nom</div>
                                <div class="info-value"><?= htmlspecialchars($nom_pediatre) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Cabinet</div>
                                <div class="info-value"><?= htmlspecialchars($cabinet_med) ?></div>
                            </div>
                        </div>
                        <button onclick="openModal()" class="btn btn-primary btn-full">
                            <i class="fas fa-calendar-plus"></i> Prendre rendez-vous
                        </button>
                    </div>
                </div>

            </div><!-- /grid-2 -->

            <!-- Grid bas -->
            <div class="grid-bottom">

                <!-- Prochain RDV + Prochain vaccin -->
                <div class="right-col" style="grid-column:1">
                    <!-- RDV -->
                    <div class="card">
                        <div class="card-head">
                            <div class="card-title"><i class="fas fa-calendar-check"></i> Prochain rendez-vous</div>
                            <?php if ($statut_rdv !== '—'): ?>
                                <span class="badge <?= $statut_rdv === 'confirmé' ? 'confirmed' : 'pending' ?>">
                                    <i class="fas fa-<?= $statut_rdv === 'confirmé' ? 'check-circle' : 'clock' ?>"></i>
                                    <?= htmlspecialchars(ucfirst($statut_rdv)) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Date</div>
                                    <div class="info-value"><?= htmlspecialchars($date_rdv) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Heure</div>
                                    <div class="info-value"><?= htmlspecialchars($heure_rdv) ?></div>
                                </div>
                                <div class="info-item" style="grid-column:1/-1">
                                    <div class="info-label">Motif</div>
                                    <div class="info-value"><?= htmlspecialchars($motif_rdv) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vaccin -->
                    <div class="card">
                        <div class="card-head">
                            <div class="card-title"><i class="fas fa-syringe"></i> Prochain vaccin</div>
                            <?php if ($jours_vaccin !== null): ?>
                                <span class="badge <?= $jours_vaccin <= 7 ? 'warning' : 'pending' ?>">
                                    <i class="fas fa-clock"></i> Dans <?= $jours_vaccin ?>
                                    jour<?= $jours_vaccin > 1 ? 's' : '' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Vaccin</div>
                                    <div class="info-value"><?= htmlspecialchars($nom_vaccin) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Date prévue</div>
                                    <div class="info-value"><?= htmlspecialchars($date_vaccin) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="card" style="grid-column:2">
                    <div class="card-head">
                        <div class="card-title"><i class="fas fa-bell"></i> Notifications récentes</div>
                        <span class="card-badge"><?= count($notifications) ?></span>
                    </div>
                    <div class="card-body" style="padding:0">
                        <?php if (!empty($notifications)):
                            foreach ($notifications as $notif):
                                $fa_icon = 'fa-calendar-check';
                                $type_cls = 'confirmed';
                                if ($notif['type'] === 'rdv_annule') {
                                    $fa_icon = 'fa-times-circle';
                                    $type_cls = 'cancelled';
                                } elseif ($notif['type'] === 'rdv_en_attente') {
                                    $fa_icon = 'fa-clock';
                                    $type_cls = 'pending';
                                }
                                $notif_date = date('d/m/Y à H:i', strtotime($notif['date_creation']));
                                ?>
                                <div class="rdv-mini-row">
                                    <div class="p-avatar" style="width:32px;height:32px;font-size:12px;flex-shrink:0">
                                        <i class="fas <?= $fa_icon ?>" style="font-size:12px"></i>
                                    </div>
                                    <div class="p-info">
                                        <div class="p-name"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div class="p-sub"><?= $notif_date ?></div>
                                    </div>
                                    <span class="p-status <?= $type_cls ?>">
                                        <?= match ($notif['type']) { 'rdv_confirme' => 'Confirmé', 'rdv_annule' => 'Annulé', 'rdv_en_attente' => 'En attente', default => 'Nouveau'} ?>
                                    </span>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                Aucune notification récente
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /grid-bottom -->

            <!-- Actions rapides -->
            <div class="card" style="margin-top:18px">
                <div class="card-head">
                    <div class="card-title"><i class="fas fa-bolt"></i> Actions rapides</div>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="croissance_simple.php" class="qa-btn"><i class="fas fa-chart-line"></i> Courbe de
                            croissance</a>
                        <a href="calendrier_vaccination.php" class="qa-btn"><i class="fas fa-syringe"></i> Calendrier
                            vaccinal</a>
                        <a href="conseils.php" class="qa-btn"><i class="fas fa-lightbulb"></i> Conseils
                            personnalisés</a>
                        <a href="messagerie.php" class="qa-btn"><i class="fas fa-envelope"></i> Messagerie</a>
                    </div>
                </div>
            </div>

        </div><!-- /main -->
    </div><!-- /page-wrapper -->

    <!-- ══ MODAL RDV ══ -->
    <div id="rdvModal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);z-index:300;align-items:center;justify-content:center;">
        <div class="modal-rdv">
            <div class="modal-rdv-head">
                <h2><i class="fas fa-calendar-plus"></i> Prendre rendez-vous</h2>
                <button class="modal-rdv-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-rdv-body">
                <form id="rdvForm">
                    <input type="hidden" id="doctorId" value="<?= $id_pediatre ?>">
                    <input type="hidden" id="doctorName" value="<?= htmlspecialchars($nom_pediatre) ?>">
                    <div class="form-row form-row-2">
                        <div>
                            <label><i class="fas fa-calendar-day"></i> Date souhaitée</label>
                            <input type="date" id="rdvDate" required>
                        </div>
                        <div>
                            <label><i class="fas fa-clock"></i> Heure souhaitée</label>
                            <input type="time" id="rdvTime" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <label><i class="fas fa-notes-medical"></i> Motif de la consultation</label>
                        <textarea id="motif" rows="3" placeholder="Décrivez brièvement le motif..."></textarea>
                    </div>
                    <div class="modal-rdv-foot" style="border:none;padding:0;margin-top:8px">
                        <button type="button" class="btn-refuser" onclick="closeModal()">Annuler</button>
                        <button type="submit" class="btn-accepter"><i class="fas fa-paper-plane"></i> Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══ MODAL PROPOSITION ══ -->
    <div id="propositionModal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);z-index:300;align-items:center;justify-content:center;">
        <div class="modal-rdv">
            <div class="modal-rdv-head">
                <h2><i class="fas fa-exchange-alt"></i> Proposition de créneau</h2>
                <button class="modal-rdv-close" onclick="closePropositionModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-rdv-body">
                <div class="rdv-info-box" id="propositionMessage"></div>
                <div class="modal-rdv-foot" style="border:none;padding:0">
                    <button class="btn-refuser" id="btn-refuse"><i class="fas fa-times"></i> Refuser</button>
                    <button class="btn-accepter" id="acceptPropositionBtn"><i class="fas fa-check"></i>
                        Accepter</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div id="notification"></div>

    <script src="accueil_parent.js"></script>
    <script>
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
                        nb === 0 ? 'Tout est à jour' : nb + ' non lue' + (nb > 1 ? 's' : '');
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

        loadNotifications();
        setInterval(loadNotifications, 30000);
    </script>
</body>

</html>