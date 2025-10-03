<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$userRole = $_SESSION['role'] ?? 'user';
$isAdmin = $userRole === 'admin';

require __DIR__ . '/bootstrap.php';

use App\Database;
use App\FtpClient;

$db = new Database($config['database']);
$pdo = $db->pdo();
$baseUrl = '.';
$defaultStatus = 'Software';

$equipmentOptions = [
    'Reprograph' => ['DV5700', 'DV5950', 'DV6950'],
    'Capteur' => ['LUX', 'FOCUS', 'DRX'],
    'Numériseur' => ['CR CLASSIC', 'CR VITA', 'CR VITAFLEX'],
];

$designationImages = [
    'DV5700' => 'assets/images/DV5700.jpg',
    'DV5950' => 'assets/images/DV5950.jpg',
    'DV6950' => 'assets/images/DV6950.jpg',
    'LUX' => 'assets/images/LUX 35.jpg',
    'FOCUS' => 'assets/images/FOCUS.jpg',
    'DRX' => 'assets/images/DRX PLUS.jpg',
    'CR CLASSIC' => 'assets/images/CR CLASSIC.jpg',
    'CR VITA' => 'assets/images/CR VITA.jpeg',
    'CR VITAFLEX' => 'assets/images/CR VITAFLEX.jpg',
];

$formData = [
    'equipment' => '',
    'designation' => '',
    'software_name' => '',
    'numero_serie' => '',
    'Knumber' => '',
    'date_backup' => '',
    'status' => $defaultStatus,
];

$errors = [];
$successMessage = $_SESSION['success'] ?? null;
if ($successMessage) {
    unset($_SESSION['success']);
}

$isEditing = false;
$formDataFromPost = false;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$currentFileName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     $formDataFromPost = true;
     $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        if (!$isAdmin) {
            $errors[] = 'Vous n\'êtes pas autorisé à supprimer des logiciels.';
        } else {
            $deleteId = filter_input(INPUT_POST, 'backup_id', FILTER_VALIDATE_INT);
            if (!$deleteId) {
                $errors[] = 'Le logiciel à supprimer est invalide.';
            } else {
                $statement = $pdo->prepare('SELECT id, file_name FROM backups WHERE id = :id');
                $statement->execute([':id' => $deleteId]);
                $backupToDelete = $statement->fetch();

                if (!$backupToDelete) {
                    $errors[] = 'Le logiciel demandé est introuvable.';
                } else {
                    $fileDeletionIssue = false;

                    if (!empty($backupToDelete['file_name'])) {
                        $ftpClient = new FtpClient($config['ftp']);

                        try {
                            $deleted = $ftpClient->delete($backupToDelete['file_name']);
                            if (!$deleted) {
                                $fileDeletionIssue = true;
                            }
                        } catch (\RuntimeException $exception) {
                            $fileDeletionIssue = true;
                        } finally {
                            if (isset($ftpClient)) {
                                $ftpClient->disconnect();
                            }
                        }
                    }

                    try {
                        $statement = $pdo->prepare('DELETE FROM backups WHERE id = :id');
                        $statement->execute([':id' => $deleteId]);

                        $_SESSION['success'] = $fileDeletionIssue
                            ? 'Le logiciel a été supprimé, mais le fichier n\'a pas pu être retiré du serveur FTP.'
                            : 'Le logiciel a été supprimé avec succès.';

                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    } catch (\PDOException $exception) {
                        $errors[] = 'Une erreur de base de données est survenue lors de la suppression du logiciel.';
                    }
                }
            }
        }
    } else {
        $isEditAction = $action === 'update';
        $backupId = $isEditAction ? filter_input(INPUT_POST, 'backup_id', FILTER_VALIDATE_INT) : null;
        $existingBackup = null;

        $skipEditProcessing = $isEditAction && !$isAdmin;
        if ($skipEditProcessing) {
            $errors[] = 'Vous n\'êtes pas autorisé à modifier des logiciels.';
        } else {
            if ($isEditAction) {
                if (!$backupId) {
                    $errors[] = 'Le logiciel à modifier est invalide.';
                } else {
                    $statement = $pdo->prepare('SELECT * FROM backups WHERE id = :id');
                    $statement->execute([':id' => $backupId]);
                    $existingBackup = $statement->fetch();

                    if (!$existingBackup || ($existingBackup['status'] ?? null) !== $defaultStatus) {
                        $errors[] = 'Le logiciel demandé est introuvable.';
                    } else {
                        $isEditing = true;
                        $editId = $backupId;
                        $currentFileName = $existingBackup['file_name'] ?? '';
                    }
                }
            }

            $formData = [
                'equipment' => trim($_POST['equipment'] ?? ''),
                'designation' => trim($_POST['designation'] ?? ''),
                'software_name' => trim($_POST['software_name'] ?? ''),
                'numero_serie' => trim($_POST['numero_serie'] ?? ''),
                'Knumber' => trim($_POST['Knumber'] ?? ''),
                'date_backup' => $_POST['date_backup'] ?? '',
                'status' => $existingBackup['status'] ?? $defaultStatus,
            ];

            if ($formData['equipment'] === '') {
                $errors[] = 'Le champ « Équipement » est obligatoire.';
            }

            if ($formData['designation'] === '') {
                $errors[] = 'Le champ « Désignation du DM » est obligatoire.';
            }

            if ($formData['software_name'] === '') {
                $errors[] = 'Le champ « Nom du logiciel » est obligatoire.';
            }

            if ($formData['numero_serie'] === '') {
                $errors[] = 'Le champ « Version » est obligatoire.';
            }

            if ($formData['date_backup'] === '') {
                $errors[] = 'La date de sortie est obligatoire.';
            }

            $fileInfo = $_FILES['backup_file'] ?? null;
            $remoteFilename = $existingBackup['file_name'] ?? '';
            $remoteFileType = $existingBackup['file_type'] ?? 'application/octet-stream';
            $newFileUploaded = false;
            $oldRemoteFilename = $existingBackup['file_name'] ?? '';

            if ($fileInfo && $fileInfo['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Une erreur est survenue lors du téléversement du fichier.';
                } else {
                    $originalName = $fileInfo['name'] ?? 'backup';
                    $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', strtolower($originalName));
                    if ($safeName === '' || $safeName === false) {
                        $safeName = 'backup';
                    }
                    $remoteFilename = date('Ymd_His') . '_' . $safeName;
                    $remoteFileType = $fileInfo['type'] ?? 'application/octet-stream';

                    $ftpClient = new FtpClient($config['ftp']);
                    $uploadSuccess = false;

                    try {
                        $uploadSuccess = $ftpClient->upload($fileInfo['tmp_name'], $remoteFilename);
                    } catch (\RuntimeException $exception) {
                        $errors[] = 'Erreur FTP : ' . $exception->getMessage();
                    } finally {
                        if (isset($ftpClient)) {
                            $ftpClient->disconnect();
                        }
                    }

                    if (!$uploadSuccess) {
                        $errors[] = 'Impossible de téléverser le fichier logiciel sur le serveur FTP.';
                    } else {
                        $newFileUploaded = true;
                    }
                }
            } elseif (!$isEditAction) {
                $errors[] = 'Un fichier logiciel doit être sélectionné.';
            }

            if (empty($errors)) {
                if ($isEditAction) {
                    try {
                        $statement = $pdo->prepare('UPDATE backups SET equipment = :equipment, designation = :designation, software_name = :software_name, numero_serie = :numero_serie, Knumber = :Knumber, client = :client, fournisseur = :fournisseur, date_backup = :date_backup, commentaire = :commentaire, status = :status, file_name = :file_name, file_type = :file_type, file_date = :file_date WHERE id = :id');

                        $statement->execute([
                            ':equipment' => $formData['equipment'],
                            ':designation' => $formData['designation'],
                            ':software_name' => $formData['software_name'],
                            ':numero_serie' => $formData['numero_serie'] !== '' ? $formData['numero_serie'] : null,
                            ':Knumber' => $formData['Knumber'] !== '' ? $formData['Knumber'] : null,
                            ':client' => 'Logiciels',
                            ':fournisseur' => null,
                            ':date_backup' => $formData['date_backup'],
                            ':commentaire' => null,
                            ':status' => $formData['status'],
                            ':file_name' => $remoteFilename,
                            ':file_type' => $remoteFileType,
                            ':file_date' => null,
                            ':id' => $backupId,
                        ]);

                        $message = 'Le logiciel a été mis à jour avec succès.';

                        if ($newFileUploaded && $oldRemoteFilename !== '' && $oldRemoteFilename !== $remoteFilename) {
                            $cleanupClient = new FtpClient($config['ftp']);
                            $cleanupIssue = false;

                            try {
                                if (!$cleanupClient->delete($oldRemoteFilename)) {
                                    $cleanupIssue = true;
                                }
                            } catch (\RuntimeException $exception) {
                                $cleanupIssue = true;
                            } finally {
                                $cleanupClient->disconnect();
                            }

                            if ($cleanupIssue) {
                                $message .= ' Cependant, l\'ancien fichier n\'a pas pu être retiré du serveur FTP.';
                            }
                        }

                        $_SESSION['success'] = $message;
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    } catch (\PDOException $exception) {
                        $errors[] = 'Une erreur de base de données est survenue lors de la mise à jour du logiciel.';

                        if ($newFileUploaded && $remoteFilename !== '') {
                            $cleanupClient = new FtpClient($config['ftp']);
                            try {
                                $cleanupClient->delete($remoteFilename);
                            } catch (\RuntimeException $cleanupException) {
                                // Ignorer les erreurs de nettoyage
                            } finally {
                                $cleanupClient->disconnect();
                            }
                        }
                    }
                } else {
                    try {
                        $statement = $pdo->prepare('INSERT INTO backups (equipment, designation, software_name, numero_serie, Knumber, client, fournisseur, date_backup, commentaire, status, file_name, file_type, file_date, uploaded_at) VALUES (:equipment, :designation, :software_name, :numero_serie, :Knumber, :client, :fournisseur, :date_backup, :commentaire, :status, :file_name, :file_type, :file_date, NOW())');

                        $statement->execute([
                            ':equipment' => $formData['equipment'],
                            ':designation' => $formData['designation'],
                            ':software_name' => $formData['software_name'],
                            ':numero_serie' => $formData['numero_serie'] !== '' ? $formData['numero_serie'] : null,
                            ':Knumber' => $formData['Knumber'] !== '' ? $formData['Knumber'] : null,
                            ':client' => 'Logiciels',
                            ':fournisseur' => null,
                            ':date_backup' => $formData['date_backup'],
                            ':commentaire' => null,
                            ':status' => $formData['status'],
                            ':file_name' => $remoteFilename,
                            ':file_type' => $remoteFileType,
                            ':file_date' => null,
                        ]);

                        $_SESSION['success'] = 'Le logiciel a été enregistré avec succès.';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    } catch (\PDOException $exception) {
                        $errors[] = 'Une erreur de base de données est survenue lors de l\'enregistrement du logiciel.';

                        if ($newFileUploaded && $remoteFilename !== '') {
                            $cleanupClient = new FtpClient($config['ftp']);
                            try {
                                $cleanupClient->delete($remoteFilename);
                            } catch (\RuntimeException $cleanupException) {
                                // Ignorer les erreurs de nettoyage
                            } finally {
                                $cleanupClient->disconnect();
                            }
                        }
                    }
                }
            } else {
                if ($isEditAction && $existingBackup) {
                    $isEditing = true;
                    $currentFileName = $existingBackup['file_name'] ?? '';
                }
            }
        }
    }
}

if (!$formDataFromPost && $editId) {
    if (!$isAdmin) {
        $errors[] = 'Vous n\'êtes pas autorisé à modifier des logiciels.';
        $editId = null;
    } else {
        $statement = $pdo->prepare('SELECT * FROM backups WHERE id = :id');
        $statement->execute([':id' => $editId]);
        $backupToEdit = $statement->fetch();

        if ($backupToEdit && ($backupToEdit['status'] ?? null) === $defaultStatus) {
            $isEditing = true;
            $currentFileName = $backupToEdit['file_name'] ?? '';
            $formData = [
                'equipment' => $backupToEdit['equipment'] ?? '',
                'designation' => $backupToEdit['designation'] ?? '',
                'software_name' => $backupToEdit['software_name'] ?? '',
                'numero_serie' => $backupToEdit['numero_serie'] ?? '',
                'Knumber' => $backupToEdit['Knumber'] ?? '',
                'date_backup' => $backupToEdit['date_backup'] ?? '',
                'status' => $backupToEdit['status'] ?? $defaultStatus,
            ];
        } else {
            $errors[] = 'Le logiciel demandé est introuvable.';
            $editId = null;
        }
    }
}

$backupsStatement = $pdo->prepare('SELECT id, equipment, designation, software_name, numero_serie, Knumber, date_backup, file_name, file_type, uploaded_at FROM backups WHERE status = :status ORDER BY date_backup DESC, id DESC');
$backupsStatement->execute([':status' => $defaultStatus]);
$backups = $backupsStatement->fetchAll();
$defaultSection = ($isEditing || $formDataFromPost) ? 'create' : 'list';

function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function toSearchIndex(array $values): string
{
    $filtered = array_filter($values, static fn($value): bool => $value !== null && $value !== '');
    if (empty($filtered)) {
        return '';
    }

    $string = implode(' ', array_map(static fn($value): string => (string) $value, $filtered));

    return function_exists('mb_strtolower')
        ? mb_strtolower($string, 'UTF-8')
        : strtolower($string);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des logiciels</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php require __DIR__ . '/partials/top_nav.php'; ?>
    <header class="page-header">
        <img src="assets/images/logo.png" alt="Logo IMAlliance" class="page-logo">
        <div class="page-header-content">
            <h1>Gestion des logiciels</h1>
            <p>Gérez vos logiciels en toute simplicité et retrouvez-les facilement, partout et à tout instant.</p>
        </div>
    </header>

    <main data-default-section="<?= e($defaultSection); ?>">
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <?= e($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <h2>Veuillez corriger les éléments suivants :</h2>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="section-toggle" role="group" aria-label="Navigation des logiciels">
            <button type="button" class="toggle-button<?= $defaultSection === 'create' ? ' is-active' : ''; ?>" data-section-toggle="create" aria-pressed="<?= $defaultSection === 'create' ? 'true' : 'false'; ?>">➕ Ajouter un logiciel</button>
            <button type="button" class="toggle-button<?= $defaultSection === 'list' ? ' is-active' : ''; ?>" data-section-toggle="list" aria-pressed="<?= $defaultSection === 'list' ? 'true' : 'false'; ?>">📂 Consulter les logiciels</button>
        </div>

        <section class="card<?= $defaultSection === 'create' ? '' : ' hidden'; ?>" id="create-section" aria-hidden="<?= $defaultSection === 'create' ? 'false' : 'true'; ?>">
            <h2><?= $isEditing ? 'Modifier un logiciel' : 'Enregistrer un nouveau logiciel'; ?></h2>
            <form action="<?= e($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data" class="backup-form">
                <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'create'; ?>">
                <?php if ($isEditing && $editId): ?>
                    <input type="hidden" name="backup_id" value="<?= e((string) $editId); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="equipment">Nom du DM <span class="required">*</span></label>
                    <select name="equipment" id="equipment" required>
                        <option value="">Sélectionnez un équipement</option>
                        <?php foreach ($equipmentOptions as $equipment => $designations): ?>
                            <option value="<?= e($equipment); ?>"<?= $formData['equipment'] === $equipment ? ' selected' : ''; ?>><?= e($equipment); ?></option>
                        <?php endforeach; ?>
                        <?php if ($formData['equipment'] !== '' && !array_key_exists($formData['equipment'], $equipmentOptions)): ?>
                            <option value="<?= e($formData['equipment']); ?>" selected><?= e($formData['equipment']); ?> (valeur existante)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="designation">Désignation du DM <span class="required">*</span></label>
                    <?php
                        $selectedEquipment = $formData['equipment'];
                        $designationChoices = $equipmentOptions[$selectedEquipment] ?? [];
                        $selectedDesignation = $formData['designation'];
                        $isCustomDesignation = $selectedDesignation !== '' && !in_array($selectedDesignation, $designationChoices, true);
                    ?>
                    <select name="designation" id="designation" data-initial-designation="<?= e($selectedDesignation); ?>" required>
                        <option value="">Sélectionnez une désignation</option>
                        <?php foreach ($designationChoices as $designation): ?>
                            <option value="<?= e($designation); ?>"<?= $selectedDesignation === $designation ? ' selected' : ''; ?>><?= e($designation); ?></option>
                        <?php endforeach; ?>
                        <?php if ($isCustomDesignation): ?>
                            <option value="<?= e($selectedDesignation); ?>" selected><?= e($selectedDesignation); ?> (valeur existante)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="software_name">Nom du logiciel <span class="required">*</span></label>
                    <input type="text" name="software_name" id="software_name" value="<?= e($formData['software_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="numero_serie">Version <span class="required">*</span></label>
                    <input type="text" name="numero_serie" id="numero_serie" value="<?= e($formData['numero_serie']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="Knumber">Knumber</label>
                    <input type="text" name="Knumber" id="Knumber" value="<?= e($formData['Knumber']); ?>">
                </div>

                <div class="form-group">
                    <label for="date_backup">Date de sortie <span class="required">*</span></label>
                    <input type="date" name="date_backup" id="date_backup" value="<?= e($formData['date_backup']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="backup_file">
                        <?= $isEditing ? 'Nouveau fichier logiciel' : 'Fichier logiciel'; ?>
                        <?php if (!$isEditing): ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                    <input type="file" name="backup_file" id="backup_file" <?= $isEditing ? '' : 'required'; ?>>
                    <?php if ($isEditing && $currentFileName !== ''): ?>
                        <small>Fichier actuel : <?= e($currentFileName); ?>. Laissez ce champ vide pour conserver le fichier existant.</small>
                    <?php else: ?>
                        <small>Les fichiers sont téléversés sur le serveur FTP configuré.</small>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit"><?= $isEditing ? 'Mettre à jour le logiciel' : 'Enregistrer le logiciel'; ?></button>
                    <?php if ($isEditing): ?>
                        <a href="<?= e($_SERVER['PHP_SELF']); ?>" class="button-link secondary">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="card<?= $defaultSection === 'list' ? '' : ' hidden'; ?>" id="list-section" aria-hidden="<?= $defaultSection === 'list' ? 'false' : 'true'; ?>">
            <h2>Logiciels enregistrés</h2>
            <?php if (empty($backups)): ?>
                <p class="muted">Aucun logiciel n'a encore été enregistré.</p>
            <?php else: ?>
                <div class="software-browser">
                    <div class="browser-step">
                        <h3>1. Choisissez une catégorie</h3>
                        <div class="equipment-choice" role="group" aria-label="Choix de la catégorie du DM">
                            <?php foreach ($equipmentOptions as $equipment => $designations): ?>
                                <button type="button" class="equipment-button" data-equipment-choice="<?= e($equipment); ?>">
                                    <?= e($equipment); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="browser-step">
                        <h3>2. Sélectionnez un DM</h3>
                        <p class="muted" id="equipment-instructions">Choisissez une catégorie pour afficher les DM associés.</p>
                        <div class="designation-grid" id="designation-grid">
                            <?php foreach ($equipmentOptions as $equipment => $designations): ?>
                                <?php foreach ($designations as $designation): ?>
                                    <?php $imagePath = $designationImages[$designation] ?? 'assets/images/logo.png'; ?>
                                    <button type="button" class="designation-card hidden" data-equipment="<?= e($equipment); ?>" data-designation="<?= e($designation); ?>">
                                        <img src="<?= e($imagePath); ?>" alt="<?= e($designation); ?>">
                                        <span><?= e($designation); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="browser-step">
                        <h3>3. Logiciels disponibles</h3>
                        <p class="muted" id="designation-instructions">Sélectionnez un DM pour afficher les logiciels correspondants.</p>
                        <div class="backup-filters hidden" id="software-filters">
                            <div class="filter-field">
                                <label for="backup-search">Recherche</label>
                                <input type="search" id="backup-search" placeholder="Rechercher par nom, version ou date…">
                            </div>
                            <div class="filter-actions">
                                <button type="button" class="button-link secondary" id="clear-filters">Réinitialiser</button>
                            </div>
                        </div>
                        <div class="table-wrapper hidden" id="software-results">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="column-equipment">Nom du DM</th>
                                        <th class="column-designation">Désignation du DM</th>
                                        <th>Nom du logiciel</th>
                                        <th>Version</th>
                                        <th>Date de sortie</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <?php
                                            $backupId = (string) ($backup['id'] ?? '');
                                            $equipment = (string) ($backup['equipment'] ?? '');
                                            $designation = (string) ($backup['designation'] ?? '');
                                            $searchIndex = toSearchIndex([
                                                $equipment,
                                                $designation,
                                                $backup['software_name'] ?? '',
                                                $backup['numero_serie'] ?? '',
                                                $backup['Knumber'] ?? '',
                                                $backup['date_backup'] ?? '',
                                            ]);
                                        ?>
                                        <tr class="backup-row hidden" data-backup-id="<?= e($backupId); ?>" data-equipment="<?= e($equipment); ?>" data-designation="<?= e($designation); ?>" data-search="<?= e($searchIndex); ?>">
                                            <td class="column-equipment"><?= e($equipment); ?></td>
                                            <td class="column-designation"><?= e($designation); ?></td>
                                            <td><?= e($backup['software_name']); ?></td>
                                            <td><?= e($backup['numero_serie']); ?></td>
                                            <td><?= e($backup['date_backup']); ?></td>
                                            <td class="table-actions">
                                                <a href="download_software.php?id=<?= e($backupId); ?>" class="button-link">Télécharger</a>
                                                <?php if ($isAdmin): ?>
                                                    <a href="logiciels.php?edit=<?= e($backupId); ?>" class="button-link secondary">Modifier</a>
                                                    <form action="<?= e($_SERVER['PHP_SELF']); ?>" method="post" class="inline-form" onsubmit="return confirm('Confirmez-vous la suppression de ce logiciel ?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="backup_id" value="<?= e($backupId); ?>">
                                                        <button type="submit" class="button-link danger">Supprimer</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr class="details-row hidden" data-backup-id="<?= e($backupId); ?>" data-equipment="<?= e($equipment); ?>" data-designation="<?= e($designation); ?>">
                                            <td colspan="6">
                                                <span class="detail-item"><strong>Ajouté le :</strong> <?= e($backup['uploaded_at']); ?></span>
                                                <?php
                                                    $kNumber = trim((string) ($backup['Knumber'] ?? ''));
                                                    $fileName = trim((string) ($backup['file_name'] ?? ''));
                                                    $fileType = trim((string) ($backup['file_type'] ?? ''));
                                                ?>
                                                <?php if ($kNumber !== ''): ?>
                                                    <span class="detail-item"><strong>Knumber :</strong> <?= e($backup['Knumber']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($fileName !== ''): ?>
                                                    <span class="detail-item"><strong>Nom du fichier :</strong> <?= e($backup['file_name']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($fileType !== ''): ?>
                                                    <span class="detail-item"><strong>Type de fichier :</strong> <?= e($backup['file_type']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr id="no-results-row" class="hidden">
                                        <td colspan="6" class="no-results">Aucun logiciel ne correspond aux critères de recherche.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mainElement = document.querySelector('main');
            const defaultSection = mainElement && mainElement.dataset.defaultSection ? mainElement.dataset.defaultSection : 'list';
            const sections = {
                create: document.getElementById('create-section'),
                list: document.getElementById('list-section')
            };
            const toggleButtons = document.querySelectorAll('[data-section-toggle]');
            const equipmentDesignationMap = <?= json_encode($equipmentOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const equipmentSelect = document.getElementById('equipment');
            const designationSelect = document.getElementById('designation');

            function activateSection(sectionName) {
                toggleButtons.forEach(function (button) {
                    const isActive = button.dataset.sectionToggle === sectionName;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                Object.keys(sections).forEach(function (name) {
                    const element = sections[name];
                    if (!element) {
                        return;
                    }
                    const isActive = name === sectionName;
                    element.classList.toggle('hidden', !isActive);
                    element.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                });
            }

            toggleButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    activateSection(button.dataset.sectionToggle);
                });
            });

            activateSection(defaultSection);

            const searchInput = document.getElementById('backup-search');
            const clearFiltersButton = document.getElementById('clear-filters');
            const backupRows = Array.from(document.querySelectorAll('.backup-row'));
            const detailRows = new Map();
            const equipmentButtons = Array.from(document.querySelectorAll('[data-equipment-choice]'));
            const designationCards = Array.from(document.querySelectorAll('.designation-card'));
            const equipmentInstructions = document.getElementById('equipment-instructions');
            const designationInstructions = document.getElementById('designation-instructions');
            const softwareResults = document.getElementById('software-results');
            const softwareFilters = document.getElementById('software-filters');

            backupRows.forEach(function (row) {
                const backupId = row.dataset.backupId || '';
                if (!backupId) {
                    return;
                }
                const detailRow = document.querySelector('.details-row[data-backup-id="' + backupId + '"]');
                if (detailRow) {
                    detailRows.set(backupId, detailRow);
                }
            });

            const noResultsRow = document.getElementById('no-results-row');
            let activeEquipment = '';
            let activeDesignation = '';

            function updateEquipmentButtons() {
                equipmentButtons.forEach(function (button) {
                    const isActive = button.dataset.equipmentChoice === activeEquipment;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            }

            function updateDesignationCards() {
                designationCards.forEach(function (card) {
                    const matchesEquipment = !!activeEquipment && card.dataset.equipment === activeEquipment;
                    const isActive = card.dataset.designation === activeDesignation;

                    card.classList.toggle('hidden', !matchesEquipment);
                    card.classList.toggle('is-active', isActive);
                });

                if (equipmentInstructions) {
                    equipmentInstructions.classList.toggle('hidden', !!activeEquipment);
                }

            }

            function updateSoftwareVisibility() {
                const hasDesignation = !!activeDesignation;

                if (designationInstructions) {
                    designationInstructions.classList.toggle('hidden', hasDesignation);
                }

                if (softwareResults) {
                    softwareResults.classList.toggle('hidden', !hasDesignation);
                    softwareResults.classList.toggle('hide-selection-columns', hasDesignation);
                }

                if (softwareFilters) {
                    softwareFilters.classList.toggle('hidden', !hasDesignation);
                }

                if (!hasDesignation) {
                    backupRows.forEach(function (row) {
                        row.classList.add('hidden');
                        const detailRow = detailRows.get(row.dataset.backupId || '');
                        if (detailRow) {
                            detailRow.classList.add('hidden');
                        }
                    });

                    if (noResultsRow) {
                        noResultsRow.classList.add('hidden');
                    }
                }
            }

            if (equipmentSelect && designationSelect) {
                function populateDesignations(selectedEquipment, desiredDesignation) {
                    const allowedDesignations = Array.isArray(equipmentDesignationMap[selectedEquipment])
                        ? equipmentDesignationMap[selectedEquipment]
                        : [];
                    let previousSelection;

                    if (desiredDesignation === null) {
                        previousSelection = designationSelect.dataset.initialDesignation || '';
                    } else if (typeof desiredDesignation === 'string') {
                        previousSelection = desiredDesignation;
                    } else {
                        previousSelection = designationSelect.value || designationSelect.dataset.initialDesignation || '';
                    }

                    designationSelect.innerHTML = '';

                    const placeholderOption = document.createElement('option');
                    placeholderOption.value = '';
                    placeholderOption.textContent = 'Sélectionnez une désignation';
                    designationSelect.appendChild(placeholderOption);

                    allowedDesignations.forEach(function (designation) {
                        const option = document.createElement('option');
                        option.value = designation;
                        option.textContent = designation;
                        designationSelect.appendChild(option);
                    });

                    if (previousSelection && !allowedDesignations.includes(previousSelection)) {
                        const customOption = document.createElement('option');
                        customOption.value = previousSelection;
                        customOption.textContent = previousSelection + ' (valeur existante)';
                        designationSelect.appendChild(customOption);
                    }

                    if (previousSelection && (allowedDesignations.includes(previousSelection) || !allowedDesignations.length)) {
                        designationSelect.value = previousSelection;
                    } else {
                        designationSelect.value = '';
                    }

                    designationSelect.dataset.initialDesignation = designationSelect.value;
                    designationSelect.disabled = selectedEquipment === '';
                }

                populateDesignations(equipmentSelect.value, null);

                equipmentSelect.addEventListener('change', function () {
                    populateDesignations(equipmentSelect.value, '');
                });

                designationSelect.addEventListener('change', function () {
                    designationSelect.dataset.initialDesignation = designationSelect.value;
                });
            }

            function applyFilters() {
                const query = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
                let visibleCount = 0;

                if (!activeDesignation) {
                    return;
                }

                backupRows.forEach(function (row) {
                    const searchValue = row.dataset.search || '';
                    const matchesEquipment = !activeEquipment || row.dataset.equipment === activeEquipment;
                    const matchesDesignation = !activeDesignation || row.dataset.designation === activeDesignation;
                    const matchesSearch = !query || searchValue.indexOf(query) !== -1;
                    const isVisible = matchesEquipment && matchesDesignation && matchesSearch;

                    row.classList.toggle('hidden', !isVisible);
                    const detailRow = detailRows.get(row.dataset.backupId || '');
                    if (detailRow) {
                        detailRow.classList.toggle('hidden', !isVisible);
                    }

                    if (isVisible) {
                        visibleCount++;
                    }
                });

                if (noResultsRow) {
                    noResultsRow.classList.toggle('hidden', visibleCount !== 0);
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', applyFilters);
            }
            if (clearFiltersButton) {
                clearFiltersButton.addEventListener('click', function () {
                    activeEquipment = '';
                    activeDesignation = '';
                    if (searchInput) {
                        searchInput.value = '';
                    }
                    updateEquipmentButtons();
                    updateDesignationCards();
                    updateSoftwareVisibility();
                    applyFilters();
                    if (searchInput) {
                        searchInput.focus();
                    }
                });
            }

            equipmentButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const selectedEquipment = button.dataset.equipmentChoice || '';
                    if (activeEquipment === selectedEquipment) {
                        return;
                    }

                    activeEquipment = selectedEquipment;
                    activeDesignation = '';
                    updateEquipmentButtons();
                    updateDesignationCards();
                    updateSoftwareVisibility();
                    applyFilters();
                });
            });

            designationCards.forEach(function (card) {
                card.addEventListener('click', function () {
                    const selectedDesignation = card.dataset.designation || '';
                    const selectedEquipment = card.dataset.equipment || '';

                    if (!selectedDesignation) {
                        return;
                    }

                    activeEquipment = selectedEquipment;
                    activeDesignation = selectedDesignation;
                    updateEquipmentButtons();
                    updateDesignationCards();
                    updateSoftwareVisibility();
                    applyFilters();
                });
            });

            updateEquipmentButtons();
            updateDesignationCards();
            updateSoftwareVisibility();
            applyFilters();
        });
    </script>

    <footer class="page-footer">
        <p>Configurez le fichier <code>config.php</code> avec vos identifiants MySQL et FTP pour utiliser l'application.</p>
    </footer>
</body>
</html>
