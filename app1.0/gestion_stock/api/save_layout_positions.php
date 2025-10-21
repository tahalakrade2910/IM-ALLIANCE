<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'error' => "Authentification requise pour enregistrer la disposition.",
    ]);
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'error' => "Seuls les administrateurs peuvent modifier la disposition.",
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode([
        'error' => 'Méthode non autorisée.',
    ]);
    exit;
}

require_once __DIR__ . '/../connexion.php';

$allowedLocations = ['Rabat', 'Ouled Saleh'];

try {
    $payload = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException $exception) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Requête JSON invalide.',
    ]);
    exit;
}

$location = (string) ($payload['location'] ?? '');
$items = $payload['items'] ?? null;

if ($location === '' || !in_array($location, $allowedLocations, true)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Lieu de sauvegarde invalide.',
    ]);
    exit;
}

if (!is_array($items)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Format des éléments invalide.',
    ]);
    exit;
}

$updatedBy = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

try {
    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'INSERT INTO warehouse_layouts (location, item_key, position_x, position_y, position_z, rotation_y, updated_by) '
        . 'VALUES (:location, :item_key, :position_x, :position_y, :position_z, :rotation_y, :updated_by) '
        . 'ON DUPLICATE KEY UPDATE position_x = VALUES(position_x), position_y = VALUES(position_y), '
        . 'position_z = VALUES(position_z), rotation_y = VALUES(rotation_y), updated_by = VALUES(updated_by), '
        . 'updated_at = CURRENT_TIMESTAMP'
    );

    foreach ($items as $item) {
        $itemKey = (string) ($item['itemKey'] ?? $item['item_key'] ?? '');
        $position = $item['position'] ?? [];
        $rotationY = $item['rotationY'] ?? $item['rotation_y'] ?? 0;

        if ($itemKey === '' || !is_array($position)) {
            throw new InvalidArgumentException('Données de disposition incomplètes.');
        }

        $x = $position['x'] ?? null;
        $y = $position['y'] ?? null;
        $z = $position['z'] ?? null;

        if (!is_numeric($x) || !is_numeric($y) || !is_numeric($z) || !is_numeric($rotationY)) {
            throw new InvalidArgumentException('Positions ou rotation invalides.');
        }

        $statement->execute([
            ':location' => $location,
            ':item_key' => $itemKey,
            ':position_x' => (float) $x,
            ':position_y' => (float) $y,
            ':position_z' => (float) $z,
            ':rotation_y' => (float) $rotationY,
            ':updated_by' => $updatedBy,
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
    ]);
} catch (\InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'error' => $exception->getMessage(),
    ]);
} catch (\PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => "Impossible d'enregistrer la disposition.",
    ]);
}
