<?php 
$conn = new mysqli("localhost", "root", "","tinysteps");

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Récupérer l'id_bebe depuis l'URL
$id_bebe = isset($_GET['id_bebe']) ? intval($_GET['id_bebe']) : 0;

if($id_bebe > 0){
    $sql = "SELECT * FROM bebe WHERE id_bebe = $id_bebe";
    $result = $conn->query($sql);
    $bebe = $result->fetch_assoc();
} else {
    die("Aucun patient sélectionné !");
}

// Enregistrer la consultation si formulaire soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $motif = $conn->real_escape_string($_POST['motif']);
    $observation = $conn->real_escape_string($_POST['observation']);
    $prescription = $conn->real_escape_string($_POST['prescription']);
    $id_medecin = 1; // TODO : remplacer par id de medecin connecté via session

    // insert consultation
    $sql = "INSERT INTO consultation (id_bebe, id_medecin, motif, observation)
            VALUES ('$id_bebe', '$id_medecin', '$motif', '$observation')";
    $conn->query($sql);

    $id_consultation = $conn->insert_id;

    // insert prescription
    $sql2 = "INSERT INTO prescription (id_consultation, contenu)
             VALUES ('$id_consultation', '$prescription')";
    $conn->query($sql2);

    echo "<p style='color:green'>Consultation enregistrée avec succès</p>";
}
?>