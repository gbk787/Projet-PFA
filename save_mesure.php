<?php
// ══════════════════════════════════════════════════════════════
//  save_mesure.php — Enregistrement d'une mesure
// ══════════════════════════════════════════════════════════════
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']); exit;
}
require_once 'connexion_bd.php';

$id_bebe    = (int) ($_POST['id_bebe']    ?? 0);
$id_medecin = (int) ($_POST['id_medecin'] ?? 0);
$date_mesure = $conn->real_escape_string($_POST['date_mesure'] ?? date('Y-m-d'));

if (!$id_bebe) { echo json_encode(['success' => false, 'message' => 'Bébé manquant']); exit; }

$poids  = !empty($_POST['poids'])  ? (float) $_POST['poids']  : null;
$taille = !empty($_POST['taille']) ? (float) $_POST['taille'] : null;
$pc     = !empty($_POST['pc'])     ? (float) $_POST['pc']     : null;

$imc = ($poids && $taille) ? round($poids / pow($taille / 100, 2), 2) : null;

$p_sql = $poids  !== null ? "'$poids'"  : 'NULL';
$t_sql = $taille !== null ? "'$taille'" : 'NULL';
$c_sql = $pc     !== null ? "'$pc'"     : 'NULL';
$i_sql = $imc    !== null ? "'$imc'"    : 'NULL';

$ok = $conn->query("INSERT INTO mesure (id_bebe, id_medecin, date_mesure, poids, taille, pc, imc)
                    VALUES ('$id_bebe', '$id_medecin', '$date_mesure', $p_sql, $t_sql, $c_sql, $i_sql)");

echo json_encode(['success' => (bool) $ok, 'message' => $ok ? 'OK' : $conn->error]);
$conn->close();
?>