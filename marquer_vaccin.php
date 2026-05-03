<?php
header('Content-Type: application/json; charset=utf-8');

$conn = new mysqli("localhost", "root", "", "tinysteps");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erreur connexion BD']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// ── Récupération des données
$bebe_id = isset($_POST['bebe_id']) ? (int) $_POST['bebe_id'] : 0;
$vaccin_id = isset($_POST['vaccin_id']) ? (int) $_POST['vaccin_id'] : 0;
$pediatre_id = isset($_POST['pediatre_id']) ? (int) $_POST['pediatre_id'] : 0;
$date_effectuee = trim($_POST['date_effectuee'] ?? '');
$note_medicale = trim($_POST['note_medicale'] ?? '');

// ── Validations
if (!$bebe_id || !$vaccin_id || !$date_effectuee) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_effectuee)) {
    echo json_encode(['success' => false, 'message' => 'Format de date invalide']);
    exit;
}
if ($date_effectuee > date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'La date ne peut pas être dans le futur']);
    exit;
}

try {
    // ── Vérifier si une ligne vaccination existe déjà
    $check = $conn->query(
        "SELECT id FROM vaccination 
         WHERE bebe_id = $bebe_id AND vaccin_id = $vaccin_id LIMIT 1"
    );

    if ($check && $check->num_rows > 0) {
        // ── UPDATE
        $row_id = $check->fetch_assoc()['id'];

        $stmt = $conn->prepare(
            "UPDATE vaccination SET
                date_effectuee = ?,
                statut         = 'effectue',
                note_medicale  = ?,
                pediatre_id    = ?
             WHERE id = ?"
        );
        $stmt->bind_param("ssii", $date_effectuee, $note_medicale, $pediatre_id, $row_id);

        if (!$stmt->execute())
            throw new Exception($stmt->error);

        echo json_encode([
            'success' => true,
            'action' => 'updated',
            'vaccination_id' => $row_id,
            'message' => 'Vaccin mis à jour avec succès'
        ]);

    } else {
        // ── INSERT
        $stmt = $conn->prepare(
            "INSERT INTO vaccination
                (bebe_id, vaccin_id, date_effectuee, statut, note_medicale, pediatre_id)
             VALUES (?, ?, ?, 'effectue', ?, ?)"
        );
        $stmt->bind_param(
            "iissi",
            $bebe_id,
            $vaccin_id,
            $date_effectuee,
            $note_medicale,
            $pediatre_id
        );

        if (!$stmt->execute())
            throw new Exception($stmt->error);

        echo json_encode([
            'success' => true,
            'action' => 'inserted',
            'vaccination_id' => $conn->insert_id,
            'message' => 'Vaccination enregistrée avec succès'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur base de données : ' . $e->getMessage()
    ]);
}

$conn->close();
?>