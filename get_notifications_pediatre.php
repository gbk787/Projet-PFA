<?php
// ══════════════════════════════════════
//  get_notifications_pediatre.php
//  Retourne les RDV en attente + nb non lus
// ══════════════════════════════════════
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "tinysteps");
if ($conn->connect_error) {
    echo json_encode(['success' => false]);
    exit;
}

session_start();
$pediatre_id = isset($_SESSION['pediatre_id']) ? (int) $_SESSION['pediatre_id'] : 1;

// ── RDV en attente (demandes non traitées)
$sql = "
SELECT rv.id, rv.date_rdv, rv.heure_debut, rv.motif, rv.statut,
       b.nom_prenom AS bebe_nom, b.id_bebe,
       b.date_naissance,
       pa.nom_prenom AS parent_nom
FROM rendez_vous rv
JOIN bebe b   ON rv.bebe_id  = b.id_bebe
JOIN parent pa ON b.id_parent = pa.id_parent
WHERE rv.pediatre_id = $pediatre_id
  AND rv.statut = 'attente'
ORDER BY rv.date_rdv ASC, rv.heure_debut ASC
LIMIT 10
";
$res = $conn->query($sql);
$rdvs = [];
while ($r = $res->fetch_assoc()) {
    $dob = new DateTime($r['date_naissance']);
    $diff = $dob->diff(new DateTime());
    $r['age_mois'] = $diff->y * 12 + $diff->m;
    $rdvs[] = $r;
}

$nb = count($rdvs);

echo json_encode([
    'success' => true,
    'nb' => $nb,
    'rdvs' => $rdvs,
]);

$conn->close();
?>