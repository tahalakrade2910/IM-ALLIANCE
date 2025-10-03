<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Authentification requise.']);
    exit();
}

require __DIR__ . '/bootstrap.php';

use App\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new Database($config['database']);
    $pdo = $db->pdo();
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible d\'accéder à la base de données.']);
    exit();
}

$afterId = filter_input(INPUT_GET, 'after', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$afterId = $afterId !== null && $afterId !== false ? $afterId : 0;

try {
    if ($afterId > 0) {
        $statement = $pdo->prepare('SELECT id, sender_id, sender_name, message, created_at FROM chat_messages WHERE id > :after ORDER BY created_at ASC LIMIT 100');
        $statement->execute([':after' => $afterId]);
    } else {
        $statement = $pdo->query('SELECT id, sender_id, sender_name, message, created_at FROM chat_messages ORDER BY created_at ASC LIMIT 100');
    }

    $messages = [];

    while ($row = $statement->fetch()) {
        $messages[] = [
            'id' => (int) ($row['id'] ?? 0),
            'sender_id' => (int) ($row['sender_id'] ?? 0),
            'sender_name' => (string) ($row['sender_name'] ?? ''),
            'message' => (string) ($row['message'] ?? ''),
            'created_at' => isset($row['created_at']) ? date(DATE_ATOM, strtotime((string) $row['created_at'])) : date(DATE_ATOM),
        ];
    }

    echo json_encode(['messages' => $messages], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de récupérer les messages.']);
}
