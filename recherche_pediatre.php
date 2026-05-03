<?php
session_start();

// ── Vérification session : parent doit être connecté ──
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: connexion.php?role=parent");
    exit;
}

$parent_id = $_SESSION['user_id'];

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "tinysteps");
if ($conn->connect_error) {
    die("Erreur connexion: " . $conn->connect_error);
}
$id_parent = $_SESSION['user_id'] ?? null;
$nom_prenom = $email = '—';

if ($id_parent) {
    $st = $conn->prepare("SELECT nom_prenom, email FROM parent WHERE id_parent=? LIMIT 1");
    $st->bind_param("i", $id_parent);
    $st->execute();
    if ($p = $st->get_result()->fetch_assoc()) {
        $nom_prenom = $p['nom_prenom'];
        $email = $p['email'];
    }
    $st->close();
}

$parent_nom = $_SESSION['user_nom'] ?? $nom_prenom;
$parent_email = $_SESSION['user_email'] ?? $email;
$conn->set_charset("utf8mb4");

// Récupérer infos du parent connecté
$parent = [];
$stmtP = $conn->prepare("SELECT nom_prenom, email FROM parent WHERE id_parent = ?");
if ($stmtP) {
    $stmtP->bind_param("i", $parent_id);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    $parent = $resP->fetch_assoc() ?? [];
    $stmtP->close();
}

// ============================================
// ACTION : AJOUTER AVIS
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_avis') {
    header('Content-Type: application/json');
    $medecin_id = (int) $_POST['medecin_id'];
    $note = (int) $_POST['rating'];
    $commentaire = $conn->real_escape_string($_POST['commentaire']);

    if ($note < 1 || $note > 5) {
        echo json_encode(['success' => false, 'message' => 'Note invalide']);
        exit;
    }

    $sql = "INSERT INTO avis (medecin_id, parent_id, note, commentaire, statut) 
            VALUES ($medecin_id, $parent_id, $note, '$commentaire', 'approuve')";

    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Avis ajouté avec succès !']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $conn->error]);
    }
    exit;
}

// ============================================
// ACTION : PRENDRE RDV
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'prendre_rdv') {
    header('Content-Type: application/json');

    $pediatre_id = (int) $_POST['pediatre_id'];
    $date_rdv = $conn->real_escape_string($_POST['date_rdv']);
    $heure_debut = $conn->real_escape_string($_POST['heure_debut']);
    $heure_fin = date('H:i:s', strtotime($heure_debut . ' +30 minutes'));
    $type_rdv = $conn->real_escape_string($_POST['type_rdv'] ?? 'consult');
    $motif_rdv = $conn->real_escape_string($_POST['motif'] ?? '');

    // Récupérer le bébé du parent connecté
    $stmtBebe = $conn->prepare("SELECT id_bebe FROM bebe WHERE id_parent = ? LIMIT 1");
    $stmtBebe->bind_param("i", $parent_id);
    $stmtBebe->execute();
    $resBebe = $stmtBebe->get_result()->fetch_assoc();
    $stmtBebe->close();

    if (!$resBebe) {
        echo json_encode(['success' => false, 'message' => 'Aucun bébé associé à votre compte.']);
        exit;
    }
    $bebe_id = $resBebe['id_bebe'];

    // Étape 1 : Le pédiatre travaille-t-il ce jour ?
    // date('N') : 1=Lundi … 7=Dimanche (ISO-8601)
    $jour = date('N', strtotime($date_rdv));
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM disponibilites 
                            WHERE medecin_id=? AND jour_semaine=? AND actif=1");
    $stmt->bind_param("ii", $pediatre_id, $jour);
    $stmt->execute();
    $travaille = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    if (!$travaille) {
        echo json_encode([
            'success' => false,
            'message' => 'Le pédiatre ne consulte pas ce jour-là. Veuillez choisir un autre jour.'
        ]);
        exit;
    }

    // Étape 2 : L'heure est-elle dans une plage de disponibilité ?
    $stmtHeure = $conn->prepare(
        "SELECT COUNT(*) as c FROM disponibilites 
         WHERE medecin_id=? AND jour_semaine=? AND actif=1
         AND heure_debut <= ? AND heure_fin > ?"
    );
    $stmtHeure->bind_param("iiss", $pediatre_id, $jour, $heure_debut, $heure_debut);
    $stmtHeure->execute();
    $dansPlage = $stmtHeure->get_result()->fetch_assoc()['c'];
    $stmtHeure->close();

    if (!$dansPlage) {
        // Récupérer les plages disponibles pour ce jour pour informer le parent
        $stmtPlages = $conn->prepare(
            "SELECT heure_debut, heure_fin FROM disponibilites 
             WHERE medecin_id=? AND jour_semaine=? AND actif=1 ORDER BY heure_debut"
        );
        $stmtPlages->bind_param("ii", $pediatre_id, $jour);
        $stmtPlages->execute();
        $resPlages = $stmtPlages->get_result();
        $plagesMsg = [];
        while ($p = $resPlages->fetch_assoc()) {
            $plagesMsg[] = substr($p['heure_debut'], 0, 5) . ' – ' . substr($p['heure_fin'], 0, 5);
        }
        $stmtPlages->close();
        $plagesStr = implode(', ', $plagesMsg);
        echo json_encode([
            'success' => false,
            'message' => "Le pédiatre n'est pas disponible à cette heure. Plages disponibles : $plagesStr."
        ]);
        exit;
    }

    // Étape 3 : Le créneau est-il libre ?
    $stmt2 = $conn->prepare("SELECT COUNT(*) as c FROM rendez_vous 
                             WHERE pediatre_id=? AND date_rdv=? 
                             AND heure_debut=? AND statut != 'annulé'");
    $stmt2->bind_param("iss", $pediatre_id, $date_rdv, $heure_debut);
    $stmt2->execute();
    $occupe = $stmt2->get_result()->fetch_assoc()['c'];
    $stmt2->close();

    if ($occupe > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ce créneau est déjà pris. Veuillez choisir un autre horaire.'
        ]);
        exit;
    }

    // Étape 4 : Enregistrer le rendez-vous
    $stmt3 = $conn->prepare("INSERT INTO rendez_vous 
        (pediatre_id, bebe_id, date_rdv, heure_debut, heure_fin, type, motif, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'attente')");
    $stmt3->bind_param("iisssss", $pediatre_id, $bebe_id, $date_rdv, $heure_debut, $heure_fin, $type_rdv, $motif_rdv);

    if ($stmt3->execute()) {
        $rdv_id = $conn->insert_id;
        $stmt3->close();

        // Étape 5 : Créer une notification pour le pédiatre
        $nom_parent = $parent['nom_prenom'] ?? 'Un parent';
        $msg_notif = "Nouvelle demande de RDV de $nom_parent le $date_rdv à " . substr($heure_debut, 0, 5) . ".";
        $stmtNotif = $conn->prepare(
            "INSERT INTO notification (destinataire_id, role, type, message, rdv_id, lu)
             VALUES (?, 'medecin', 'rdv_en_attente', ?, ?, 0)"
        );
        $stmtNotif->bind_param("isi", $pediatre_id, $msg_notif, $rdv_id);
        $stmtNotif->execute();
        $stmtNotif->close();

        echo json_encode([
            'success' => true,
            'message' => 'Rendez-vous enregistré avec succès ! Le pédiatre recevra une notification et confirmera sous peu.'
        ]);
    } else {
        $stmt3->close();
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement.']);
    }
    exit;
}

// ============================================
// FONCTIONS
// ============================================

function getMedecinRating($conn, $medecin_id)
{
    $sql = "SELECT AVG(note) as moyenne, COUNT(*) as total FROM avis WHERE medecin_id = $medecin_id AND statut = 'approuve'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return ['moyenne' => round($row['moyenne'] ?? 0, 1), 'total' => $row['total'] ?? 0];
    }
    return ['moyenne' => 0, 'total' => 0];
}

// ============================================
// RÉCUPÉRATION DES MÉDECINS
// ============================================

$sql = "SELECT 
            m.id, 
            CONCAT('Dr. ', m.prenom, ' ', m.nom) as nom,
            m.specialite,
            c.nom_cabinet as cabinet,
            m.tel as telephone,
            m.email,
            c.ville
        FROM medecin m
        LEFT JOIN cabinet c ON c.medecin_id = m.id
        WHERE m.statut = 'accepte'
        ORDER BY m.nom";

$result = $conn->query($sql);
$medecins = [];

while ($row = $result->fetch_assoc()) {
    $rating = getMedecinRating($conn, $row['id']);

    $row['photo'] = strtoupper(substr($row['nom'], 4, 2));
    $row['distance'] = rand(1, 15);
    $row['rating'] = $rating['moyenne'];
    $row['nb_avis'] = $rating['total'];


    $today = date('N');
    $stmt = $conn->prepare("SELECT COUNT(*) as dispo FROM disponibilites WHERE medecin_id = ? AND jour_semaine = ? AND actif = 1");
    $stmt->bind_param("ii", $row['id'], $today);
    $stmt->execute();
    $dispoCount = $stmt->get_result()->fetch_assoc()['dispo'];
    $stmt->close();

    $tomorrow = date('N', strtotime('+1 day'));
    $stmtTomorrow = $conn->prepare("SELECT COUNT(*) as dispo FROM disponibilites WHERE medecin_id = ? AND jour_semaine = ? AND actif = 1");
    $stmtTomorrow->bind_param("ii", $row['id'], $tomorrow);
    $stmtTomorrow->execute();
    $dispoTomorrow = $stmtTomorrow->get_result()->fetch_assoc()['dispo'];
    $stmtTomorrow->close();

    if ($dispoCount > 0) {
        $row['disponibilite'] = "Aujourd'hui";
    } elseif ($dispoTomorrow > 0) {
        $row['disponibilite'] = 'Demain';
    } else {
        $row['disponibilite'] = 'Cette semaine';
    }
    $medecins[] = $row;
}

$parent_nom = $_SESSION['user_nom'] ?? $nom_prenom;
$parent_email = $_SESSION['user_email'] ?? $email;
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinySteps - Recherche pédiatre</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/recherche_pediatre.css?v=999">
    <link rel="stylesheet" href="styles/style.css">
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
            <a href="recherche_pediatre.php" class="active"><i class="fas fa-stethoscope"></i> Recherche pédiatre</a>
            <a href="croissance_simple.php"><i class="fas fa-chart-line"></i> Courbe de croissance</a>
            <a href="calendrier_vaccination.php"><i class="fas fa-syringe"></i> Calendrier vaccinal</a>
            <a href="conseils.php"><i class="fas fa-lightbulb"></i> Conseils personnalisés</a>

        </nav>
        <div class="sidebar-footer">
            <div class="parent-info">
                <div class="parent-avatar">
                    <?= strtoupper(substr($parent_nom, 0, 1)) ?>
                </div>
                <div>
                    <div class="parent-name"><?= htmlspecialchars($parent_nom) ?></div>
                    <div class="parent-email"><?= htmlspecialchars($parent_email) ?></div>
                </div>
            </div>
            <a href="home.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>

        </div>
    </aside>

    <div class="page-wrapper">
        <main class="main-content">
            <header>
                <div class="header-title">
                    <h1><i class="fas fa-search"></i> Recherche pédiatre</h1>
                    <p>Trouvez un pédiatre près de chez vous, disponible rapidement</p>
                </div>
                <div class="header-actions">
                    <button class="icon-btn"><i class="fas fa-user-circle"></i></button>
                </div>
            </header>

            <div class="filters-card">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-map-marker-alt"></i> VILLE/CODE POSTAL</label>
                        <select id="locationInput" placeholder="Ex: Tunis, Sousse">

                            <option value="ville" selected>Ville</option>
                            <option value="tunis">Tunis</option>
                            <option value="ariana">Ariana</option>
                            <option value="nabeul">Nabeul</option>
                            <option value="bizerte">Bizerte</option>
                            <option value="sousse">Sousse</option>
                            <option value="monastir">Monastir</option>
                            <option value="mahdia">Mahdia</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-route"></i> DISTANCE MAX</label>
                        <select id="distanceSelect">
                            <option value="5">5 km</option>
                            <option value="10" selected>10 km</option>
                            <option value="15">15 km</option>
                            <option value="20">20 km</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> DISPONIBILITÉ</label>
                        <select id="disponibiliteSelect">
                            <option value="aujourdhui">Aujourd'hui</option>
                            <option value="demain">Demain</option>
                            <option value="cettesemaine">Cette semaine</option>
                            <option value="toujours" selected>Toujours</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button class="search-btn" id="searchBtn">
                            <i class="fas fa-search"></i> RECHERCHER
                        </button>
                    </div>
                </div>
            </div>

            <div class="results-container">
                <div class="results-header">
                    <h2><span class="result-count" id="resultCount">0</span> pédiatres trouvés</h2>
                    <div class="sort-options">
                        <span>Trier par :</span>
                        <select id="sortSelect">
                            <option value="distance" selected>Distance (plus proche)</option>
                            <option value="rating">Note (les mieux notés)</option>
                            <option value="disponibilite">Disponibilité (plus rapide)</option>
                            <option value="nom">Nom (A-Z)</option>
                        </select>
                    </div>
                </div>
                <div class="results-layout">
                    <div class="doctors-list" id="doctorsList"></div>
                    <div class="map-sidebar">
                        <div class="map-card">
                            <div class="map-header">
                                <i class="fas fa-map-marked-alt"></i>
                                <h3>Carte interactive</h3>
                                <p>Pédiatres à proximité</p>
                            </div>
                            <div class="map-container">
                                <div class="map-placeholder">
                                    <i class="fas fa-map"></i>
                                    <span>Carte des pédiatres</span>
                                    <small>Rechercher par ville pour voir les pédiatres</small>
                                </div>
                            </div>
                            <div class="map-stats">
                                <span class="visible-count" id="visibleCount">0</span>
                                <span>pédiatres dans votre zone</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pagination" id="pagination"></div>
        </main>
    </div>

    <!-- Modal RDV -->
    <div id="rdvModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-check"></i> Prendre rendez-vous</h3>
                <span class="close-modal" onclick="closeModal('rdvModal')">&times;</span>
            </div>
            <form id="rdvForm">
                <input type="hidden" id="doctorId">
                <input type="hidden" id="doctorName">

                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Date souhaitée</label>
                    <input type="date" id="rdvDate" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Heure souhaitée</label>
                    <input type="time" id="rdvTime" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-stethoscope"></i> Type de consultation</label>
                    <select id="typeSelect">
                        <option value="" disabled selected>— Choisir un type —</option>
                        <option value="consult">Consultation</option>
                        <option value="suivi">Suivi</option>
                        <option value="urgence">Urgent !</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-notes-medical"></i> Motif de la consultation</label>
                    <textarea id="motif" rows="3" placeholder="Décrivez brièvement le motif..."></textarea>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Envoyer la demande</button>
            </form>
        </div>
    </div>

    <!-- Modal Avis -->
    <div id="avisModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-star"></i> Donner votre avis</h3>
                <span class="close-modal" onclick="closeModal('avisModal')">&times;</span>
            </div>
            <form id="avisForm">
                <input type="hidden" id="avisDoctorId">
                <div class="form-group">
                    <label><i class="fas fa-star"></i> Votre note</label>
                    <div class="rating-input">
                        <input type="radio" name="rating" value="5" id="star5" required><label for="star5">★★★★★</label>
                        <input type="radio" name="rating" value="4" id="star4"><label for="star4">★★★★</label>
                        <input type="radio" name="rating" value="3" id="star3"><label for="star3">★★★</label>
                        <input type="radio" name="rating" value="2" id="star2"><label for="star2">★★</label>
                        <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Votre commentaire</label>
                    <textarea id="avisCommentaire" rows="4" placeholder="Partagez votre expérience..."></textarea>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Publier l'avis</button>
            </form>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <script>
        const medecinsData = <?php echo json_encode($medecins); ?>;

        let currentPage = 1;
        const itemsPerPage = 5;
        let filteredDoctors = [];

        const locationInput = document.getElementById('locationInput');
        const distanceSelect = document.getElementById('distanceSelect');
        const disponibiliteSelect = document.getElementById('disponibiliteSelect');
        const sortSelect = document.getElementById('sortSelect');
        const searchBtn = document.getElementById('searchBtn');
        const doctorsList = document.getElementById('doctorsList');
        const resultCountSpan = document.getElementById('resultCount');
        const visibleCountSpan = document.getElementById('visibleCount');
        const paginationDiv = document.getElementById('pagination');

        function renderStars(rating) {
            let stars = '';
            const fullStars = Math.floor(rating);
            const hasHalf = (rating - fullStars) >= 0.5;
            const emptyStars = 5 - fullStars - (hasHalf ? 1 : 0);
            for (let i = 0; i < fullStars; i++) stars += '<i class="fas fa-star" style="color:#f59e0b;"></i>';
            if (hasHalf) stars += '<i class="fas fa-star-half-alt" style="color:#f59e0b;"></i>';
            for (let i = 0; i < emptyStars; i++) stars += '<i class="far fa-star" style="color:#f59e0b;"></i>';
            return stars;
        }

        function filterDoctors() {
            const searchLocation = locationInput?.value.trim() || '';
            const maxDistance = parseFloat(distanceSelect?.value || 10);
            const disponibilite = disponibiliteSelect?.value || 'toujours';

            if (!searchLocation) {
                filteredDoctors = [];
                updateDisplay();
                return;
            }

            filteredDoctors = medecinsData.filter(doctor => {
                const cityMatch = !searchLocation || searchLocation === 'ville' ||
                    (doctor.ville?.toLowerCase() === searchLocation.toLowerCase()) ||
                    (doctor.cabinet?.toLowerCase().includes(searchLocation.toLowerCase()));
                if (!cityMatch) return false;
                if (doctor.distance > maxDistance) return false;
                if (disponibilite === 'aujourdhui' && doctor.disponibilite !== "Aujourd'hui") return false;
                if (disponibilite === 'demain' && doctor.disponibilite !== 'Demain') return false;
                return true;
            });

            sortDoctors();
            currentPage = 1;
            updateDisplay();
        }

        // Trier les médecins
        function sortDoctors() {
            const sortBy = sortSelect?.value || 'distance';

            switch (sortBy) {
                case 'distance':
                    filteredDoctors.sort((a, b) => a.distance - b.distance); break;
                case 'rating':
                    filteredDoctors.sort((a, b) => (b.rating || 0) - (a.rating || 0)); break;
                case 'disponibilite':
                    const order = { "Aujourd'hui": 1, 'Demain': 2, 'Cette semaine': 3 };
                    filteredDoctors.sort((a, b) => (order[a.disponibilite] || 99) - (order[b.disponibilite] || 99)); break;
                default:
                    filteredDoctors.sort((a, b) => a.nom.localeCompare(b.nom)); break;
            }
        }

        // Afficher les médecins
        function displayDoctors() {
            if (!doctorsList) return;

            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const pageDoctors = filteredDoctors.slice(start, end);

            if (pageDoctors.length === 0) {
                doctorsList.innerHTML = `<div class="loading">Aucun pédiatre trouvé</div>`;
                return;
            }

            doctorsList.innerHTML = pageDoctors.map(doctor => `
                <div class="doctor-card">
                    <div class="card-left">
                        <div class="doctor-avatar">${doctor.photo || 'DR'}</div>
                        <div class="doctor-rating">
                            ${renderStars(doctor.rating || 0)}
                            <span class="rating-value">${(doctor.rating || 0).toFixed(1)}</span>
                            <span class="avis-count">(${doctor.nb_avis || 0} avis)</span>
                        </div>
                        <span class="disponibility-badge 
                            ${doctor.disponibilite === "Aujourd'hui" ? 'today' :
                    doctor.disponibilite === 'Demain' ? 'demain' : 'semaine'}">
                            ${doctor.disponibilite}
                        </span>
                    </div>
                    <div class="card-right">
                        <div class="doctor-header">
                            <h3>${doctor.nom}</h3>
                            <span class="specialty-badge">${doctor.specialite || 'Pédiatre'}</span>
                        </div>
                        <div class="location">
                            <i class="fas fa-map-marker-alt"></i> ${doctor.cabinet || 'Non spécifié'}
                        </div>
                        <div class="doctor-details">
                            <div class="detail"><i class="fas fa-road"></i> ${doctor.distance} km</div>
                            <div class="detail"><i class="fas fa-phone"></i> ${doctor.telephone || 'Non renseigné'}</div>
                        </div>
                        <div class="card-buttons">
                            <a href="profilRecherche_pediatre.php?id=${doctor.id}" class="profile-btn" style="font-weight:bold;">
                                <i class="fas fa-user"></i> Voir profil
                            </a>
                            <button class="profile-btn" onclick="openRdvModal(${doctor.id}, '${doctor.nom.replace(/'/g, "\\'")}')">
                                <i class="fas fa-calendar-plus"></i> Prendre rendez-vous
                            </button>
                            <button class="review-btn" onclick="openAvisModal(${doctor.id})">
                                <i class="fas fa-star"></i> Donner mon avis
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }


        function displayPagination() {
            if (!paginationDiv) return;
            const totalPages = Math.ceil(filteredDoctors.length / itemsPerPage);
            paginationDiv.innerHTML = '';
            if (totalPages <= 1) return;

            const prevBtn = document.createElement('button');
            prevBtn.className = 'page-btn';
            prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; updateDisplay(); } };
            paginationDiv.appendChild(prevBtn);

            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
                btn.textContent = i;
                btn.onclick = () => { currentPage = i; updateDisplay(); };
                paginationDiv.appendChild(btn);
            }

            const nextBtn = document.createElement('button');
            nextBtn.className = 'page-btn';
            nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.onclick = () => { if (currentPage < totalPages) { currentPage++; updateDisplay(); } };
            paginationDiv.appendChild(nextBtn);
        }

        function updateDisplay() {
            displayDoctors();
            displayPagination();
            if (resultCountSpan) resultCountSpan.textContent = filteredDoctors.length;
            if (visibleCountSpan) visibleCountSpan.textContent = filteredDoctors.length;
        }

        // Modals
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openRdvModal(doctorId, doctorName) {
            document.getElementById('doctorId').value = doctorId;
            document.getElementById('doctorName').value = doctorName;
            openModal('rdvModal');
        }

        function openAvisModal(doctorId) {
            document.getElementById('avisDoctorId').value = doctorId;
            openModal('avisModal');
        }

        function showNotification(message, isError = false) {
            const n = document.getElementById('notification');
            n.textContent = message;
            n.className = `notification ${isError ? 'error' : 'success'}`;
            n.style.display = 'block';
            setTimeout(() => { n.style.display = 'none'; }, 4000);
        }

        // ── Soumettre RDV ──
        document.getElementById('rdvForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const rdvDate = document.getElementById('rdvDate').value;
            const rdvTime = document.getElementById('rdvTime').value;
            const doctorId = document.getElementById('doctorId').value;
            const typeRdv = document.getElementById('typeSelect').value;
            const motif = document.getElementById('motif').value;

            if (!rdvDate || !rdvTime) {
                showNotification("Veuillez sélectionner une date et un horaire.", true);
                return;
            }
            if (!typeRdv) {
                showNotification("Veuillez choisir un type de consultation.", true);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'prendre_rdv');
            formData.append('pediatre_id', doctorId);
            formData.append('date_rdv', rdvDate);
            formData.append('heure_debut', rdvTime);
            formData.append('type_rdv', typeRdv);
            formData.append('motif', motif);

            try {
                const res = await fetch('recherche_pediatre.php', { method: 'POST', body: formData });
                const data = await res.json();
                showNotification(data.message, !data.success);
                if (data.success) {
                    closeModal('rdvModal');
                    document.getElementById('rdvForm').reset();
                }
            } catch (err) {
                showNotification("Erreur réseau. Réessayez.", true);
            }
        });

        // ── Soumettre Avis ──
        document.getElementById('avisForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const doctorId = document.getElementById('avisDoctorId').value;
            const rating = document.querySelector('input[name="rating"]:checked')?.value;
            const commentaire = document.getElementById('avisCommentaire').value;

            if (!rating) { showNotification("Veuillez sélectionner une note.", true); return; }
            if (!commentaire.trim()) { showNotification("Veuillez écrire un commentaire.", true); return; }

            const formData = new FormData();
            formData.append('action', 'ajouter_avis');
            formData.append('medecin_id', doctorId);
            formData.append('rating', rating);
            formData.append('commentaire', commentaire);

            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();
                showNotification(data.message, !data.success);
                if (data.success) {
                    closeModal('avisModal');
                    document.getElementById('avisForm').reset();
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (error) {
                showNotification("Erreur lors de l'envoi.", true);
            }
        });

        // ── Init ──
        document.addEventListener('DOMContentLoaded', () => {
            if (searchBtn) searchBtn.addEventListener('click', filterDoctors);
            if (sortSelect) sortSelect.addEventListener('change', () => { sortDoctors(); updateDisplay(); });
            filteredDoctors = [...medecinsData];
            sortDoctors();
            updateDisplay();
        });
    </script>
</body>

</html>