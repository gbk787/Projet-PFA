<?php
// ══════════════════════════════════════════════════════════════
//  save_consultation.php — Enregistrement d'une consultation
//  Appelé en AJAX depuis dossier_patient.php
// ══════════════════════════════════════════════════════════════
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once 'connexion_bd.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$id_bebe    = isset($_POST['id_bebe'])    ? (int) $_POST['id_bebe']    : 0;
$id_medecin = isset($_POST['id_medecin']) ? (int) $_POST['id_medecin'] : 0;

if (!$id_bebe || !$id_medecin) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$motif        = $conn->real_escape_string(trim($_POST['motif']        ?? ''));
$observation  = $conn->real_escape_string(trim($_POST['observation']  ?? ''));
$diagnostic   = $conn->real_escape_string(trim($_POST['diagnostic']   ?? ''));
$prescription = $conn->real_escape_string(trim($_POST['prescription'] ?? ''));
$notes        = $conn->real_escape_string(trim($_POST['notes']        ?? ''));
$compte_rendu = $conn->real_escape_string(trim($_POST['compte_rendu'] ?? ''));

$poids       = !empty($_POST['poids'])       ? (float) $_POST['poids']       : null;
$taille      = !empty($_POST['taille'])      ? (float) $_POST['taille']      : null;
$temperature = !empty($_POST['temperature']) ? (float) $_POST['temperature'] : null;
$pc          = !empty($_POST['pc'])          ? (float) $_POST['pc']          : null;

if (empty($motif)) {
    echo json_encode(['success' => false, 'message' => 'Le motif est obligatoire']);
    exit;
}

// Valeurs SQL nullable
$poids_sql  = $poids       !== null ? "'$poids'"       : 0;
$taille_sql = $taille      !== null ? "'$taille'"      : 0;
$temp_sql   = $temperature !== null ? "'$temperature'" : 0;

try {
    // ── Insérer consultation
    // ⚠️ La colonne du médecin s'appelle id_pediatre dans la table consultation
    $sql = "INSERT INTO consultation
                (id_bebe, id_pediatre, motif, observation, diagnostic,
                 poids, taille, temperature, notes, compte_rendu, date_consultation)
            VALUES
                ('$id_bebe', '$id_medecin', '$motif', '$observation', '$diagnostic',
                 $poids_sql, $taille_sql, $temp_sql,
                 '$notes', '$compte_rendu', NOW())";

    if (!$conn->query($sql)) {
        throw new Exception($conn->error);
    }
    $id_consultation = $conn->insert_id;

    // ── Insérer prescription si renseignée
    if (!empty($prescription)) {
        $conn->query("INSERT INTO prescription (id_consultation, contenu)
                      VALUES ('$id_consultation', '$prescription')");
    }

    // ── Insérer mesure si des données sont présentes
    // ⚠️ La colonne du médecin s'appelle id_medecin dans la table mesure
    if ($poids !== null || $taille !== null || $pc !== null) {
        $p_sql  = $poids  !== null ? "'$poids'"  : 'NULL';
        $t_sql  = $taille !== null ? "'$taille'" : 'NULL';
        $pc_sql = $pc     !== null ? "'$pc'"     : 'NULL';
        $imc    = ($poids && $taille) ? round($poids / pow($taille / 100, 2), 2) : null;
        $i_sql  = $imc !== null ? "'$imc'" : 'NULL';

        $conn->query("INSERT INTO mesure (id_bebe, id_medecin, date_mesure, poids, taille, pc, imc)
                      VALUES ('$id_bebe', '$id_medecin', CURDATE(),
                              $p_sql, $t_sql, $pc_sql, $i_sql)");
    }

    echo json_encode(['success' => true, 'id_consultation' => $id_consultation]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BD : ' . $e->getMessage()]);
}

$conn->close();
?>