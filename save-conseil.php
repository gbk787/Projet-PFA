<?php
/**
 * save-conseil.php
 * API AJAX — gère les actions "marquer comme lu" et "sauvegarder un conseil"
 * Appelé en POST depuis conseils-logged.php
 */

session_start();
header('Content-Type: application/json');

// Sécurité : réservé aux utilisateurs connectés
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Sécurité : méthode POST uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "tinysteps");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
    exit;
}

$user_id    = intval($_SESSION['user_id']);
$action     = trim($_POST['action']    ?? '');
$conseil_id = intval($_POST['conseil_id'] ?? 0);

if ($conseil_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID conseil invalide']);
    exit;
}

// ── Vérifier que le conseil existe réellement ─────────────────────────────────
$check = $conn->prepare("SELECT id FROM conseils WHERE id = ?");
$check->bind_param("i", $conseil_id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Conseil introuvable']);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// ── ACTION : toggle_lu ────────────────────────────────────────────────────────
if ($action === 'toggle_lu') {

    // Vérifier si déjà lu
    $stmt = $conn->prepare("SELECT id FROM conseils_lus WHERE user_id = ? AND conseil_id = ?");
    $stmt->bind_param("ii", $user_id, $conseil_id);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();

    if ($existe) {
        // Supprimer → marquer non lu
        $del = $conn->prepare("DELETE FROM conseils_lus WHERE user_id = ? AND conseil_id = ?");
        $del->bind_param("ii", $user_id, $conseil_id);
        $del->execute();
        $del->close();
        $etat = 'non_lu';
    } else {
        // Insérer → marquer lu
        $ins = $conn->prepare("INSERT INTO conseils_lus (user_id, conseil_id, lu_le) VALUES (?, ?, NOW())");
        $ins->bind_param("ii", $user_id, $conseil_id);
        $ins->execute();
        $ins->close();
        $etat = 'lu';
    }

    echo json_encode(['success' => true, 'etat' => $etat]);

// ── ACTION : toggle_sauvegarde ────────────────────────────────────────────────
} elseif ($action === 'toggle_sauvegarde') {

    // Vérifier si déjà sauvegardé
    $stmt = $conn->prepare("SELECT id FROM conseils_sauvegardes WHERE user_id = ? AND conseil_id = ?");
    $stmt->bind_param("ii", $user_id, $conseil_id);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();

    if ($existe) {
        // Supprimer
        $del = $conn->prepare("DELETE FROM conseils_sauvegardes WHERE user_id = ? AND conseil_id = ?");
        $del->bind_param("ii", $user_id, $conseil_id);
        $del->execute();
        $del->close();
        $etat = 'non_sauvegarde';
    } else {
        // Insérer
        $ins = $conn->prepare("INSERT INTO conseils_sauvegardes (user_id, conseil_id, sauvegarde_le) VALUES (?, ?, NOW())");
        $ins->bind_param("ii", $user_id, $conseil_id);
        $ins->execute();
        $ins->close();
        $etat = 'sauvegarde';
    }

    // Compter le total des sauvegardes de cet utilisateur
    $cnt = $conn->prepare("SELECT COUNT(*) as total FROM conseils_sauvegardes WHERE user_id = ?");
    $cnt->bind_param("i", $user_id);
    $cnt->execute();
    $res = $cnt->get_result()->fetch_assoc();
    $cnt->close();

    echo json_encode([
        'success' => true,
        'etat'    => $etat,
        'total'   => intval($res['total']),
    ]);

} else {
    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
}

$conn->close();
