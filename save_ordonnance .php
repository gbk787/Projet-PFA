<?php
// ══════════════════════════════════════════════════════════════
//  save_ordonnance.php — Enregistrement d'une ordonnance
// ══════════════════════════════════════════════════════════════
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']); exit;
}
require_once 'connexion_bd.php';

$id_bebe    = (int) ($_POST['id_bebe']    ?? 0);
$id_medecin = (int) ($_POST['id_medecin'] ?? 0);
$date_ord   = $conn->real_escape_string($_POST['date_ord'] ?? date('Y-m-d'));
$contenu    = $conn->real_escape_string(trim($_POST['contenu'] ?? ''));

if (!$id_bebe || !$contenu) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']); exit;
}

// Récupérer id_consultation le plus récent pour ce bébé/médecin
$res = $conn->query("SELECT id_consultation FROM consultation WHERE id_bebe = $id_bebe AND id_medecin = $id_medecin ORDER BY date_consultation DESC LIMIT 1");
$row = $res ? $res->fetch_assoc() : null;
$id_consultation = $row ? (int) $row['id_consultation'] : 0;

$ok = $conn->query("INSERT INTO ordonnance (id_consultation, id_bebe, id_medecin, date_ord, contenu)
                    VALUES ('$id_consultation', '$id_bebe', '$id_medecin', '$date_ord', '$contenu')");

echo json_encode(['success' => (bool) $ok, 'message' => $ok ? 'OK' : $conn->error]);
$conn->close();
?>