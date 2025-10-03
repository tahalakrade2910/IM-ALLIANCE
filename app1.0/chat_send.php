<?php
declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo 'Méthode non autorisée';
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Authentification requise.']);
    exit();
}

require __DIR__ . '/bootstrap.php';

use App\Database;

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput ?: '[]', true);

$message = '';
if (is_array($data) && array_key_exists('message', $data)) {
    $message = trim((string) $data['message']);
}

if ($message === '') {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Le message ne peut pas être vide.']);
    exit();
}

if (mb_strlen($message) > 1000) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Le message est trop long (1000 caractères maximum).']);
    exit();
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$username = (string) ($_SESSION['username'] ?? 'Utilisateur');

try {
    $db = new Database($config['database']);
    $pdo = $db->pdo();

    $statement = $pdo->prepare('INSERT INTO chat_messages (sender_id, sender_name, message) VALUES (:sender_id, :sender_name, :message)');
    $statement->execute([
        ':sender_id' => $userId,
        ':sender_name' => $username,
        ':message' => $message,
    ]);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true]);
} catch (PDOException $exception) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Impossible d\'enregistrer le message.']);
}
