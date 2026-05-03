<?php
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] !== 'parent') {
    header("Location: connexion.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "tinysteps");
if ($conn->connect_error) {
    die("Erreur connexion: " . $conn->connect_error);
}

$isLoggedIn = isset($_SESSION['user_id']);
$parent = null;
$bebeData = null;
$ageMois = 0;

if ($isLoggedIn) {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT * FROM parent WHERE id_parent = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $parent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt2 = $conn->prepare("SELECT * FROM bebe WHERE id_parent = ? LIMIT 1");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $bebeData = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    if ($bebeData && isset($bebeData['date_naissance'])) {
        $dateNaissance = new DateTime($bebeData['date_naissance']);
        $now = new DateTime();
        $diff = $now->diff($dateNaissance);
        $ageMois = ($diff->y * 12) + $diff->m;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conseils Bébé - TinySteps</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/conseil.css">

    <link rel="stylesheet" href="styles/home.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php if ($isLoggedIn): ?>
    <!-- ══ SIDEBAR ══ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="home.php">  
                <div class="logo-img">
                <img src="images/logo1.png" alt="TinySteps Logo">
            </div>
        </a>
          
            <h2>TinySteps</h2>
            <p style="font-size:12px;opacity:.7;margin-top:8px;">Suivi bébé</p>
        </div>
        <nav class="sidebar-nav">
            <a href="accueil_parent.php"><i class="fas fa-home"></i> Accueil</a>
            <a href="profil_parent.php"><i class="fas fa-user"></i> Profil</a>
            <a href="recherche_pediatre.php"><i class="fas fa-stethoscope"></i> Recherche pédiatre</a>
            <a href="croissance_simple.php"><i class="fas fa-chart-line"></i> Courbe de croissance</a>
            <a href="calendrier_vaccination.php"><i class="fas fa-syringe"></i> Calendrier vaccinal</a>
            <a href="conseils.php" class="active"><i class="fas fa-lightbulb"></i> Conseils personnalisés</a>
        </nav>
        <div class="sidebar-footer">
            <div class="parent-info">
                <div class="parent-avatar">
                    <?= strtoupper(substr($parent['nom_prenom'] ?? 'P', 0, 1)) ?>
                </div>
                <div>
                    <div class="parent-name"><?= htmlspecialchars($parent['nom_prenom'] ?? '') ?></div>
                    <div class="parent-email"><?= htmlspecialchars($parent['email'] ?? '') ?></div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>
    <div class="main-content-with-sidebar">

<?php else: ?>
    <?php include('navbar.php'); ?>
    <div class="main-content-no-sidebar">

<?php endif; ?>

        <!-- ========== CONTENU PRINCIPAL ========== -->
        <div class="bienvenu">
            <div class="bienvenu-left">
                <div class="badge">
                    Espace conseils
                    <?php echo isset($_GET['conseil']) ? htmlspecialchars($_GET['conseil']) : 'généraux'; ?>
                </div>
                <h1>Bienvenue,
                    <?php echo $isLoggedIn && $parent ? htmlspecialchars($parent['nom_prenom'] ?? 'Parent') : 'Parent attentionné'; ?>
                </h1>
                <p>Retrouvez des conseils d'experts adaptés à chaque étape du développement de votre enfant —
                    nutrition, sommeil, développement et Hygiène/Santé.</p>
            </div>
        </div>

        <?php include('category-toggle.php'); ?>

        <div class="container">
            <div class="content-left">
                <?php include('age-bloc-conseils.php'); ?>

                <?php
                if (isset($_GET['age']) && isset($_GET['conseil'])) {
                    $age     = intval($_GET['age']);
                    $conseil = $_GET['conseil'];

                    $stmt3 = $conn->prepare("SELECT * FROM conseils WHERE age_id = ? AND categorie = ?");
                    if (!$stmt3) {
                        echo "<p style='color:red'>Erreur prepare conseils : " . $conn->error . "</p>";
                    } else {
                        $stmt3->bind_param("is", $age, $conseil);
                        $stmt3->execute();
                        $result = $stmt3->get_result();

                        if ($result && $result->num_rows > 0) {
                            $isSaved = false;
                            $isRead  = false;
                            
                            ?>
                            <div id="res">
                                <div class="header-box">
                                    <h3>Conseils pour cet âge</h3>
                                </div>

                                <div class="section">
                                    <h4>Que doit faire</h4>
                                    <?php
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()) {
                                        if ($conseil === "alimentation") {
                                            if ($row['type'] === "aliment") {
                                                echo "<p><i class='fa-solid fa-check'></i> " . htmlspecialchars($row['contenu']) . "</p>";
                                            }
                                        } else {
                                            if ($row['type'] === "faire") {
                                                echo "<p>✔ " . htmlspecialchars($row['contenu']) . "</p>";
                                            }
                                        }
                                    }
                                    ?>
                                </div>

                                <?php if ($conseil === "alimentation") { ?>
                                    <div class="section">
                                        <h4>Conseils nutrition</h4>
                                        <?php
                                        $result->data_seek(0);
                                        while ($row = $result->fetch_assoc()) {
                                            if ($row['type'] === "nutrition") {
                                                echo "<p><i class='fa-solid fa-lightbulb'></i> " . htmlspecialchars($row['contenu']) . "</p>";
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php } ?>

                                <div class="section danger">
                                    <h4><i class="fa-solid fa-triangle-exclamation"></i> À éviter</h4>
                                    <?php
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()) {
                                        if ($row['type'] === "eviter") {
                                            echo "<p><i class='fa-solid fa-ban'></i> " . htmlspecialchars($row['contenu']) . "</p>";
                                        }
                                    }
                                    ?>
                                </div>

                                <?php if ($isLoggedIn): ?>
                                    <div class="section" style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                                        <button id="btnSave" class="btn-action btn-save"
                                            onclick="sauvegarderConseils(<?= $age ?>, '<?= htmlspecialchars($conseil) ?>')"
                                            <?= $isSaved ? 'disabled' : '' ?>>
                                            <i class="fa-solid fa-bookmark"></i>
                                            <?= $isSaved ? 'Déjà sauvegardé ✓' : 'Sauvegarder ces conseils' ?>
                                        </button>
                                        <button id="btnRead" class="btn-action btn-read"
                                            onclick="marquerCommeLu(<?= $age ?>, '<?= htmlspecialchars($conseil) ?>')"
                                            <?= $isRead ? 'disabled' : '' ?>>
                                            <i class="fa-solid fa-check-circle"></i>
                                            <?= $isRead ? 'Déjà lu ✓' : 'Marquer comme lu' ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php
                        } else {
                            echo "<p style='text-align:center;padding:50px;'>Aucun conseil trouvé pour cette sélection.</p>";
                        }
                    }
                } else {
                    echo "<p style='text-align:center;padding:50px;'>Sélectionnez une catégorie puis un âge.</p>";
                }
                ?>
            </div><!-- /.content-left -->

            <div class="content-right">
                <h4><i class="fas fa-lightbulb"></i> Astuces</h4>
                <?php
                if (isset($result) && $result && $result->num_rows > 0) {
                    $result->data_seek(0);
                    $colors = ['#9C27B0', '#673AB7', '#3F51B5', '#2196F3'];
                    $i = 0;
                    $hasAstuce = false;
                    while ($row = $result->fetch_assoc()) {
                        if (!empty($row['astuce'])) {
                            $hasAstuce = true;
                            $color = $colors[$i % count($colors)];
                            echo "<div style='background:{$color}20;padding:10px;border-radius:8px;margin-bottom:10px;'>
                                    <i class='fa-solid fa-circle' style='font-size:6px;color:{$color};'></i>
                                    <span style='margin-left:8px;'>" . htmlspecialchars($row['astuce']) . "</span>
                                  </div>";
                            $i++;
                        }
                    }
                    if (!$hasAstuce)
                        echo "<p style='color:#888;'>Aucune astuce pour cette catégorie.</p>";
                } else {
                    echo "<p style='color:#888;'>Sélectionnez une catégorie et un âge pour voir les astuces.</p>";
                }
                ?>
            </div>
        </div><!-- /.container -->

        <!-- ========== SECTION PERSONNALISÉE (connecté + bébé) ========== -->
        <?php if ($isLoggedIn && $bebeData): ?>
            <div class="user-personalized-section">
                <h2><i class="fas fa-robot"></i> Assistant personnalisé pour
                    <?php echo htmlspecialchars($bebeData['nom_prenom'] ?? 'votre bébé'); ?>
                </h2>
                <p>
                    <span class="insight-badge"><i class="fas fa-calendar"></i> Âge: <?php echo $ageMois; ?> mois</span>
                    <span class="insight-badge"><i class="fas fa-baby"></i> Née le:
                        <?php echo date('d/m/Y', strtotime($bebeData['date_naissance'])); ?></span>
                </p>

                <div class="recommendation-card">
                    <h4><i class="fas fa-star"></i> Recommandation du jour</h4>
                    <p><?php
                        if ($ageMois <= 3) {
                            echo "<strong>Période 0-3 mois :</strong> Privilégiez le peau à peau et l'allaitement à la demande. Les premiers sourires apparaîtront bientôt !";
                        } elseif ($ageMois <= 6) {
                            echo " <strong>Période 4-6 mois :</strong> C'est le moment de la diversification ! Commencez par des légumes doux comme la courgette ou la carotte.";
                        } elseif ($ageMois <= 9) {
                            echo " <strong>Période 7-9 mois :</strong> Votre bébé explore ! Sécurisez la maison et encouragez ses premiers déplacements.";
                        } elseif ($ageMois <= 12) {
                            echo " <strong>Période 10-12 mois :</strong> Bientôt 1 an ! Consultez pour le bilan du 12ème mois.";
                        } elseif ($ageMois <= 24) {
                            echo " <strong>Période 1-2 ans :</strong> L'acquisition du langage s'accélère. Parlez-lui souvent et lisez-lui des histoires !";
                        } else {
                            echo " <strong>Plus de 2 ans :</strong> Encouragez l'autonomie et les jeux d'imitation.";
                        }
                    ?></p>
                </div>

                <div class="recommendation-card">
                    <h4><i class="fas fa-syringe"></i> Prochains vaccins recommandés</h4>
                    <p><?php
                        $vaccinSql = "SELECT v.nom, c.age FROM vaccin v
                                      JOIN calendrier c ON v.id = c.vaccin_id
                                      WHERE c.age != 'Naissance'
                                      ORDER BY FIELD(c.age,'2 mois','3 mois','4 mois','6 mois','11 mois','12 mois','18 mois')
                                      LIMIT 3";
                        $vaccinResult = $conn->query($vaccinSql);
                        if ($vaccinResult && $vaccinResult->num_rows > 0) {
                            while ($v = $vaccinResult->fetch_assoc()) {
                                echo "<i class='fa-solid fa-check-circle' style='color:#4E8D9C;'></i> <strong>{$v['age']}</strong> : " . htmlspecialchars($v['nom']) . "<br>";
                            }
                        } else {
                            echo "Aucun vaccin programmé pour le moment.";
                        }
                    ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
            <div class="assistant-ia-container">
                <div class="assistant-ia-btn" onclick="toggleAssistant()" title="Assistant IA">
                    <i class="fas fa-robot"></i>
                </div>

                <div class="assistant-chat" id="assistantChat">
                    <div class="chat-header">
                        <i class="fas fa-brain"></i> Assistant Santé Bébé
                        <span style="float:right;cursor:pointer;" onclick="toggleAssistant()">✕</span>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="message-ia">
                            <strong>Bonjour <?php echo htmlspecialchars($parent['nom_prenom'] ?? 'Parent'); ?> !</strong><br>
                            Je suis votre assistant IA personnalisé pour bébé. Posez-moi des questions sur :
                            <ul style="margin-top:8px;font-size:12px;">
                                <li>Alimentation et diversification</li>
                                <li>Sommeil et rythmes</li>
                                <li>Croissance et développement</li>
                                <li>Symptômes et consultations</li>
                                <li>Vaccins et rappels</li>
                            </ul>
                        </div>
                    </div>
                    <div class="chat-input-area">
                        <input type="text" id="userQuestion" placeholder="Posez votre question..."
                            onkeypress="if(event.key==='Enter') poserQuestion()">
                        <button id="sendBtn" onclick="poserQuestion()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="toast"></div>

            <script>
                const BEBE_AGE_MOIS = <?php echo (int) $ageMois; ?>;
                const BEBE_NOM      = "<?php echo addslashes(htmlspecialchars($bebeData['nom_prenom'] ?? 'Bébé')); ?>";
                const PARENT_NOM    = "<?php echo addslashes(htmlspecialchars($parent['nom_prenom'] ?? 'Parent')); ?>";
                const BEBE_DATE_NAIS = "<?php echo $bebeData ? htmlspecialchars($bebeData['date_naissance']) : ''; ?>";

                let conversationHistory = [];

                function toggleAssistant() {
                    document.getElementById('assistantChat').classList.toggle('active');
                }

                function showToast(message, duration = 3000) {
                    const toast = document.getElementById('toast');
                    toast.textContent = message;
                    toast.classList.add('show');
                    setTimeout(() => toast.classList.remove('show'), duration);
                }

                function sauvegarderConseils(ageId, categorie) {
                    const btn = document.getElementById('btnSave');
                    btn.disabled = true;
                    btn.innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Sauvegarde...";

                    fetch('save_conseil.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'sauvegarder', age_id: ageId, categorie: categorie })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            btn.innerHTML = "<i class='fa-solid fa-bookmark'></i> Déjà sauvegardé ✓";
                            showToast("Conseils sauvegardés dans votre espace personnel !");
                        } else {
                            btn.disabled = false;
                            btn.innerHTML = "<i class='fa-solid fa-bookmark'></i> Sauvegarder ces conseils";
                            showToast("Erreur : " + (data.message || 'Réessayez'));
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        btn.innerHTML = "<i class='fa-solid fa-bookmark'></i> Sauvegarder ces conseils";
                        showToast("Erreur réseau, réessayez.");
                    });
                }

                function marquerCommeLu(ageId, categorie) {
                    const btn = document.getElementById('btnRead');
                    btn.disabled = true;
                    btn.innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Enregistrement...";

                    fetch('save_conseil.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'marquer_lu', age_id: ageId, categorie: categorie })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            btn.innerHTML = "<i class='fa-solid fa-check-circle'></i> Déjà lu ✓";
                            showToast("Marqué comme lu avec succès !");
                        } else {
                            btn.disabled = false;
                            btn.innerHTML = "<i class='fa-solid fa-check-circle'></i> Marquer comme lu";
                            showToast("Erreur : " + (data.message || 'Réessayez'));
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        btn.innerHTML = "<i class='fa-solid fa-check-circle'></i> Marquer comme lu";
                        showToast("Erreur réseau, réessayez.");
                    });
                }

                async function poserQuestion() {
                    const input    = document.getElementById('userQuestion');
                    const sendBtn  = document.getElementById('sendBtn');
                    const question = input.value.trim();
                    if (!question) return;

                    const messagesDiv = document.getElementById('chatMessages');

                    const userDiv = document.createElement('div');
                    userDiv.className = 'message-user';
                    userDiv.textContent = question;
                    messagesDiv.appendChild(userDiv);
                    input.value = '';
                    input.disabled = true;
                    sendBtn.disabled = true;

                    const typingDiv = document.createElement('div');
                    typingDiv.className = 'message-ia';
                    typingDiv.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> L\'assistant réfléchit...';
                    messagesDiv.appendChild(typingDiv);
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;

                    conversationHistory.push({ role: 'user', content: question });

                    try {
                        const response = await fetch('api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                age_mois:   BEBE_AGE_MOIS,
                                bebe_nom:   BEBE_NOM,
                                parent_nom: PARENT_NOM,
                                messages:   conversationHistory
                            })
                        });

                        const data = await response.json();
                        messagesDiv.removeChild(typingDiv);

                        if (data.success) {
                            const reponse = data.reponse;
                            conversationHistory.push({ role: 'assistant', content: reponse });

                            const iaDiv = document.createElement('div');
                            iaDiv.className = 'message-ia';
                            iaDiv.innerHTML = reponse.replace(/\n/g, '<br>')
                                + '<br><small style="font-size:10px;color:#999;margin-top:6px;display:block;">'
                                + '<i class="fas fa-shield-alt"></i> Conseils IA — Consultez toujours un professionnel de santé'
                                + '</small>';
                            messagesDiv.appendChild(iaDiv);
                        } else {
                            const errDiv = document.createElement('div');
                            errDiv.className = 'message-ia';
                            errDiv.innerHTML = '<strong>Erreur :</strong> ' + (data.message || 'Impossible de contacter l\'assistant.');
                            messagesDiv.appendChild(errDiv);
                        }
                    } catch (err) {
                        messagesDiv.removeChild(typingDiv);
                        const errDiv = document.createElement('div');
                        errDiv.className = 'message-ia';
                        errDiv.innerHTML = '<strong>Erreur réseau.</strong> Vérifiez votre connexion.';
                        messagesDiv.appendChild(errDiv);
                    }

                    input.disabled = false;
                    sendBtn.disabled = false;
                    input.focus();
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                }
            </script>
        <?php endif; ?>

    </div><!-- ferme main-content-with-sidebar OU main-content-no-sidebar -->

    <div class="footer">
        <?php include("footer.php"); ?>
    </div>

</body>
</html>