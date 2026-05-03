<?php
// ══════════════════════════════════════════════════════════════
//  save_vaccin.php — Enregistrement d'une vaccination
// ══════════════════════════════════════════════════════════════
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']); exit;
}
require_once 'connexion_bd.php';

$id_bebe    = (int) ($_POST['id_bebe']    ?? 0);
$id_medecin = (int) ($_POST['id_medecin'] ?? 0);
$vaccin_nom = $conn->real_escape_string(trim($_POST['vaccin_nom'] ?? ''));

if (!$id_bebe || !$vaccin_nom) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']); exit;
}

$date_prevue    = !empty($_POST['date_prevue'])    ? "'" . $conn->real_escape_string($_POST['date_prevue'])    . "'" : 'NULL';
$date_effectuee = !empty($_POST['date_effectuee']) ? "'" . $conn->real_escape_string($_POST['date_effectuee']) . "'" : 'NULL';
$lot  = $conn->real_escape_string(trim($_POST['lot']  ?? ''));
$note = $conn->real_escape_string(trim($_POST['note'] ?? ''));

$ok = $conn->query("INSERT INTO vaccination (id_bebe, vaccin_nom, date_prevue, date_effectuee, lot, note, id_medecin)
                    VALUES ('$id_bebe', '$vaccin_nom', $date_prevue, $date_effectuee, '$lot', '$note', '$id_medecin')");

echo json_encode(['success' => (bool) $ok, 'message' => $ok ? 'OK' : $conn->error]);
$conn->close();
?>