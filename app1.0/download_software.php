<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/bootstrap.php';

use App\Database;
use App\FtpClient;

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    echo 'Un identifiant de logiciel valide est requis.';
    exit;
}

$db = new Database($config['database']);
$pdo = $db->pdo();

$softwareStatus = 'Software';
$statement = $pdo->prepare('SELECT file_name, file_type FROM backups WHERE id = :id AND status = :status');
$statement->execute([
    ':id' => $id,
    ':status' => $softwareStatus,
]);
$backup = $statement->fetch();

if (!$backup) {
    http_response_code(404);
    echo 'Le logiciel demandé est introuvable.';
    exit;
}

$ftpClient = new FtpClient($config['ftp']);
$tempFile = tempnam(sys_get_temp_dir(), 'software_');
$downloaded = false;

try {
    if ($tempFile === false) {
        throw new \RuntimeException('Impossible de créer un fichier temporaire pour le téléchargement.');
    }

    if (!$ftpClient->download($backup['file_name'], $tempFile)) {
        throw new \RuntimeException('Impossible de récupérer le fichier du logiciel depuis le serveur FTP.');
    }

    $downloadName = basename($backup['file_name']);
    $mimeType = !empty($backup['file_type']) ? $backup['file_type'] : 'application/octet-stream';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    readfile($tempFile);
    $downloaded = true;
} catch (\RuntimeException $exception) {
    http_response_code(500);
    echo 'Échec du téléchargement du logiciel : ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
} finally {
    $ftpClient->disconnect();
    if ($tempFile && file_exists($tempFile)) {
        @unlink($tempFile);
    }
}

if ($downloaded) {
    exit;
}
