<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Authentification requise.',
    ]);
    exit;
}

require_once __DIR__ . '/../connexion.php';

$allowedLocations = ['Rabat', 'Ouled Saleh'];
$location = $_GET['lieu'] ?? 'Rabat';

if (!in_array($location, $allowedLocations, true)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Lieu de consultation invalide.',
    ]);
    exit;
}

try {
    $statement = $pdo->prepare(
        'SELECT item_key, position_x, position_y, position_z, rotation_y FROM warehouse_layouts WHERE location = :location'
    );
    $statement->execute([':location' => $location]);
    $items = $statement->fetchAll();

    echo json_encode([
        'items' => $items,
    ]);
} catch (\PDOException $exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Impossible de charger la disposition enregistrée.',
    ]);
}
