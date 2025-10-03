<?php
return [
    'database' => [
        'host' => '127.0.0.1',
        'database' => 'backups',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'ensure_backups_table' => true,
        'ensure_device_software_table' => true,
        'ensure_chat_messages_table' => true,
    ],
    'stock_database' => [
        'host' => '127.0.0.1',
        'database' => 'gestion_stock',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'ensure_backups_table' => false,
        'ensure_device_software_table' => false,
        'ensure_chat_messages_table' => false,
    ],
    'ftp' => [
        'host' => '127.0.0.1',
        'username' => 'ftp-user',
        'password' => 'secret',
        'port' => 21,
        'timeout' => 90,
        'base_path' => '/backups',
        'passive' => true,
    ],
];
