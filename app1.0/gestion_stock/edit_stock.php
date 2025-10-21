<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    header('Location: dashboard.php?error=forbidden');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $emplacement = trim($_POST['emplacement'] ?? '');
    $redirectLieu = $_POST['current_lieu'] ?? 'Rabat';
    $lieuxDisponibles = ['Rabat', 'Ouled Saleh'];

    if (!ctype_digit((string) $id) || $emplacement === '') {
        header('Location: dashboard.php?error=invalid&section=consulter');
        exit();
    }

    $stmt = $pdo->prepare('UPDATE stock SET emplacement = ? WHERE id = ?');
    $stmt->execute([$emplacement, $id]);

    if (!in_array($redirectLieu, $lieuxDisponibles, true)) {
        $redirectLieu = $lieuxDisponibles[0];
    }

    header('Location: dashboard.php?updated=1&section=consulter&lieu=' . urlencode($redirectLieu));
    exit();
}
?>
