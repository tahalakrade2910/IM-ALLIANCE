<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
        $host = $config['host'] ?? '127.0.0.1';
        $database = $config['database'] ?? '';
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $host,
            $database,
            $charset
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $exception) {
            if ((int) $exception->getCode() === 1049 && $database !== '') {
                $this->createDatabase($host, $database, $charset, $collation, $username, $password, $options);
                $this->pdo = new PDO($dsn, $username, $password, $options);
            } else {
                throw new PDOException('Unable to connect to the database: ' . $exception->getMessage(), (int) $exception->getCode(), $exception);
            }
        }

        $shouldEnsureBackupsTable = $config['ensure_backups_table'] ?? true;
        if ($shouldEnsureBackupsTable) {
            $this->ensureBackupsTable();
        }

        $shouldEnsureSoftwareTable = $config['ensure_device_software_table'] ?? true;
        if ($shouldEnsureSoftwareTable) {
            $this->ensureDeviceSoftwareTable();
        }

        $shouldEnsureChatTable = $config['ensure_chat_messages_table'] ?? true;
        if ($shouldEnsureChatTable) {
            $this->ensureChatMessagesTable();
        }

        $shouldEnsureWarehouseLayoutTable = $config['ensure_warehouse_layout_table'] ?? true;
        if ($shouldEnsureWarehouseLayoutTable) {
            $this->ensureWarehouseLayoutTable();
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function ensureBackupsTable(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `backups` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `equipment` VARCHAR(255) NOT NULL,
    `designation` VARCHAR(255) NOT NULL,
    `software_name` VARCHAR(255) NULL,
    `numero_serie` VARCHAR(255) NULL,
    `Knumber` VARCHAR(255) NULL,
    `client` VARCHAR(255) NOT NULL,
    `fournisseur` VARCHAR(255) NULL,
    `date_backup` DATE NOT NULL,
    `commentaire` TEXT NULL,
    `status` VARCHAR(50) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(255) NULL,
    `file_date` DATE NULL,
    `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        try {
            $this->pdo->exec($sql);
            $this->ensureBackupsColumns();
        } catch (PDOException $exception) {
            throw new PDOException('Unable to ensure the backups table exists: ' . $exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    public function ensureDeviceSoftwareTable(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `device_software` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `dm_type` VARCHAR(50) NOT NULL,
    `dm_model` VARCHAR(100) NULL,
    `version` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(255) NULL,
    `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        try {
            $this->pdo->exec($sql);
            $this->ensureDeviceSoftwareColumns();
        } catch (PDOException $exception) {
            throw new PDOException('Unable to ensure the device_software table exists: ' . $exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    public function ensureChatMessagesTable(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT UNSIGNED NOT NULL,
    `sender_name` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `chat_messages_created_at_idx` (`created_at`),
    INDEX `chat_messages_sender_idx` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        try {
            $this->pdo->exec($sql);
            $this->ensureChatMessageColumns();
        } catch (PDOException $exception) {
            throw new PDOException('Unable to ensure the chat_messages table exists: ' . $exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    public function ensureWarehouseLayoutTable(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `warehouse_layouts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `location` VARCHAR(100) NOT NULL,
    `item_key` VARCHAR(100) NOT NULL,
    `position_x` DOUBLE NOT NULL DEFAULT 0,
    `position_y` DOUBLE NOT NULL DEFAULT 0,
    `position_z` DOUBLE NOT NULL DEFAULT 0,
    `rotation_y` DOUBLE NOT NULL DEFAULT 0,
    `updated_by` INT UNSIGNED NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `warehouse_layouts_location_item_key_unique` (`location`, `item_key`),
    INDEX `warehouse_layouts_location_idx` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        try {
            $this->pdo->exec($sql);

            $this->ensureColumns('warehouse_layouts', [
                'rotation_y' => "ALTER TABLE `warehouse_layouts` ADD COLUMN `rotation_y` DOUBLE NOT NULL DEFAULT 0 AFTER `position_z`",
                'updated_by' => "ALTER TABLE `warehouse_layouts` ADD COLUMN `updated_by` INT UNSIGNED NULL AFTER `rotation_y`",
                'updated_at' => "ALTER TABLE `warehouse_layouts` ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `updated_by`",
            ]);
        } catch (PDOException $exception) {
            throw new PDOException('Unable to ensure the warehouse_layouts table exists: ' . $exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    private function createDatabase(string $host, string $database, string $charset, string $collation, string $username, string $password, array $options): void
    {
        $baseDsn = sprintf('mysql:host=%s;charset=%s', $host, $charset);

        try {
            $pdo = new PDO($baseDsn, $username, $password, $options);
            $quotedDatabase = str_replace('`', '``', $database);
            $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s', $quotedDatabase, $charset, $collation));
        } catch (PDOException $exception) {
            throw new PDOException('Unable to create the database: ' . $exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    private function ensureBackupsColumns(): void
    {
        $columns = [
            'software_name' => "ALTER TABLE `backups` ADD COLUMN `software_name` VARCHAR(255) NULL AFTER `designation`",
            'numero_serie' => "ALTER TABLE `backups` ADD COLUMN `numero_serie` VARCHAR(255) NULL AFTER `software_name`",
            'Knumber' => "ALTER TABLE `backups` ADD COLUMN `Knumber` VARCHAR(255) NULL AFTER `numero_serie`",
            'client' => "ALTER TABLE `backups` ADD COLUMN `client` VARCHAR(255) NOT NULL DEFAULT '' AFTER `Knumber`",
            'fournisseur' => "ALTER TABLE `backups` ADD COLUMN `fournisseur` VARCHAR(255) NULL AFTER `client`",
            'commentaire' => "ALTER TABLE `backups` ADD COLUMN `commentaire` TEXT NULL AFTER `fournisseur`",
            'status' => "ALTER TABLE `backups` ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'Stored' AFTER `commentaire`",
            'file_name' => "ALTER TABLE `backups` ADD COLUMN `file_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `status`",
            'file_type' => "ALTER TABLE `backups` ADD COLUMN `file_type` VARCHAR(255) NULL AFTER `file_name`",
            'file_date' => "ALTER TABLE `backups` ADD COLUMN `file_date` DATE NULL AFTER `file_type`",
            'uploaded_at' => "ALTER TABLE `backups` ADD COLUMN `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `file_date`",
        ];

        $this->ensureColumns('backups', $columns);
    }

    private function ensureDeviceSoftwareColumns(): void
    {
        $columns = [
            'dm_type' => "ALTER TABLE `device_software` ADD COLUMN `dm_type` VARCHAR(50) NOT NULL DEFAULT '' AFTER `id`",
            'dm_model' => "ALTER TABLE `device_software` ADD COLUMN `dm_model` VARCHAR(100) NULL AFTER `dm_type`",
            'version' => "ALTER TABLE `device_software` ADD COLUMN `version` VARCHAR(100) NOT NULL DEFAULT '' AFTER `dm_model`",
            'description' => "ALTER TABLE `device_software` ADD COLUMN `description` TEXT NULL AFTER `version`",
            'file_name' => "ALTER TABLE `device_software` ADD COLUMN `file_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `description`",
            'file_type' => "ALTER TABLE `device_software` ADD COLUMN `file_type` VARCHAR(255) NULL AFTER `file_name`",
            'added_at' => "ALTER TABLE `device_software` ADD COLUMN `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `file_type`",
        ];

        $this->ensureColumns('device_software', $columns);
    }

    private function ensureChatMessageColumns(): void
    {
        $columns = [
            'sender_id' => "ALTER TABLE `chat_messages` ADD COLUMN `sender_id` INT UNSIGNED NOT NULL AFTER `id`",
            'sender_name' => "ALTER TABLE `chat_messages` ADD COLUMN `sender_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `sender_id`",
            'message' => "ALTER TABLE `chat_messages` ADD COLUMN `message` TEXT NOT NULL AFTER `sender_name`",
            'created_at' => "ALTER TABLE `chat_messages` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `message`",
        ];

        $this->ensureColumns('chat_messages', $columns);
    }

    private function ensureColumns(string $table, array $columns): void
    {
        foreach ($columns as $column => $sql) {
            if ($this->columnExists($table, $column)) {
                continue;
            }

            try {
                $this->pdo->exec($sql);
            } catch (PDOException $exception) {
                throw new PDOException(sprintf('Unable to add the %s column to the %s table: %s', $column, $table, $exception->getMessage()), (int) $exception->getCode(), $exception);
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $sql = <<<'SQL'
SELECT COUNT(*)
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            ':table' => $table,
            ':column' => $column,
        ]);

        return (bool) $statement->fetchColumn();
    }
}
