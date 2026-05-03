<?php

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']); exit;
}
require_once 'connexion_bd.php';

$id_bebe      = (int) ($_POST['id_bebe']       ?? 0);
$id_medecin   = (int) ($_POST['id_medecin']    ?? 0);
$note_content = $conn->real_escape_string(trim($_POST['note_content'] ?? ''));

if (!$id_bebe || !$note_content) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']); exit;
}

// Insérée comme consultation de type "note"
$ok = $conn->query("INSERT INTO consultation (id_bebe, id_medecin, motif, notes, date_consultation)
                    VALUES ('$id_bebe', '$id_medecin', 'Note interne', '$note_content', NOW())");

echo json_encode(['success' => (bool) $ok, 'message' => $ok ? 'OK' : $conn->error]);
$conn->close();
?>