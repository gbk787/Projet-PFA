<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: connexion.php");
    exit;
}
?>
