<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: connexion.php"); exit;
}
require_once 'connexion_bd.php';

$id  = $_SESSION['user_id'];
$email     = trim($_POST['email'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');

if (empty($email)) {
    header("Location: profil_parent.php?error=Email+obligatoire"); exit;
}

$st = $conn->prepare("UPDATE parent SET email=?, telephone=? WHERE id_parent=?");
$st->bind_param("ssi", $email, $telephone, $id);
if ($st->execute()) {
    $_SESSION['user_email'] = $email;
    header("Location: profil_parent.php?updated=1");
} else {
    header("Location: profil_parent.php?error=Erreur+lors+de+la+mise+%C3%A0+jour");
}
$st->close(); $conn->close();
