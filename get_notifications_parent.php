<?php
// ══════════════════════════════════════
//  get_notifications_parent.php
//  Retourne les notifs non lues du parent
// ══════════════════════════════════════
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "tinySteps");
if ($conn->connect_error) {
    echo json_encode(['success' => false]);
    exit;
}

session_start();
$parent_id = isset($_SESSION['id_parent']) ? (int) $_SESSION['id_parent'] : 1;

$action = $_GET['action'] ?? 'get';

// ── Marquer comme lues
if ($action === 'mark_read' && isset($_GET['notif_id'])) {
    $notif_id = (int) $_GET['notif_id'];
    $conn->query("UPDATE notification SET lu = 1 WHERE id = $notif_id AND destinataire_id = $parent_id");
    echo json_encode(['success' => true]);
    $conn->close();
    exit;
}

// ── Marquer toutes comme lues
if ($action === 'mark_all_read') {
    $conn->query("UPDATE notification SET lu = 1 WHERE destinataire_id = $parent_id AND role = 'parent'");
    echo json_encode(['success' => true]);
    $conn->close();
    exit;
}

// ── Récupérer les notifs
$sql = "
SELECT n.id, n.type, n.message, n.lu,
       DATE_FORMAT(n.date_creation, '%d/%m/%Y à %H:%i') AS date_fr,
       n.rdv_id
FROM notification n
WHERE n.destinataire_id = $parent_id
  AND n.role = 'parent'
ORDER BY n.date_creation DESC
LIMIT 15
";
$res   = $conn->query($sql);
$notifs = [];
while ($n = $res->fetch_assoc()) $notifs[] = $n;

$nb_non_lus = array_reduce($notifs, fn($carry, $n) => $carry + ($n['lu'] == 0 ? 1 : 0), 0);

echo json_encode([
    'success'    => true,
    'nb'         => $nb_non_lus,
    'notifs'     => $notifs,
]);

$conn->close();
?>
