<?php
// ══════════════════════════════════════
//  gerer_rdv.php — Accepter / Refuser un RDV
//  Appelé en AJAX par le pédiatre
// ══════════════════════════════════════
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "tinySteps");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erreur connexion BD']);
    exit;
}

session_start();
$pediatre_id = isset($_SESSION['pediatre_id']) ? (int) $_SESSION['pediatre_id'] : 1;

$rdv_id = isset($_POST['rdv_id']) ? (int) $_POST['rdv_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$rdv_id || !in_array($action, ['accepter', 'refuser'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Vérifier que le RDV appartient bien à ce pédiatre
$check = $conn->query("SELECT id FROM rendez_vous WHERE id = $rdv_id AND pediatre_id = $pediatre_id");
if (!$check || $check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$nouveau_statut = $action === 'accepter' ? 'confirmé' : 'annulé';

// ── Mettre à jour le statut du RDV
$conn->query("
    UPDATE rendez_vous
    SET statut = '$nouveau_statut'
    WHERE id = $rdv_id
");

if ($conn->affected_rows >= 0) {
    // ── Récupérer les infos du RDV pour la notif et la mise à jour bébé
    $res = $conn->query("
        SELECT rv.date_rdv, rv.heure_debut, rv.bebe_id, rv.pediatre_id,
               b.nom_prenom AS bebe_nom, b.id_parent
        FROM rendez_vous rv
        JOIN bebe b ON rv.bebe_id = b.id_bebe
        WHERE rv.id = $rdv_id
    ");
    $rdv = $res->fetch_assoc();

    if ($rdv) {
        $parent_id = (int) $rdv['id_parent'];
        $bebe_nom  = $conn->real_escape_string($rdv['bebe_nom']);
        $date_rdv  = $rdv['date_rdv'];
        $heure_rdv = substr($rdv['heure_debut'], 0, 5);

        // ── Si accepté : mettre à jour pediatre_id dans la table bebe
        if ($action === 'accepter') {
            $bebe_id_upd    = (int) $rdv['bebe_id'];
            $pediatre_id_upd = (int) $rdv['pediatre_id'];
            $conn->query("
                UPDATE bebe
                SET pediatre_id = $pediatre_id_upd
                WHERE id_bebe = $bebe_id_upd
            ");
        }

        $notif_msg = $action === 'accepter'
            ? "Votre rendez-vous pour {$bebe_nom} le {$date_rdv} à {$heure_rdv} a été confirmé."
            : "Votre rendez-vous pour {$bebe_nom} le {$date_rdv} à {$heure_rdv} a été annulé." .
            ($message ? " Motif : " . $conn->real_escape_string($message) : '');

        $notif_msg_esc = $conn->real_escape_string($notif_msg);
        $type = $action === 'accepter' ? 'rdv_confirme' : 'rdv_annule';

        $conn->query("
            INSERT INTO notification (destinataire_id, role, type, message, rdv_id, lu, date_creation)
            VALUES ($parent_id, 'parent', '$type', '$notif_msg_esc', $rdv_id, 0, NOW())
        ");
    }

    echo json_encode(['success' => true, 'statut' => $nouveau_statut]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur mise à jour']);
}

$conn->close();
?>