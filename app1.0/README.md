# Backup Management App

This project provides a simple PHP web application to register backups, stock the associated files on an FTP server and track metadata in a MySQL database. It now also centralises the software packages for each device model so that technicians and users can download the latest versions on demand. It is designed to run with XAMPP or any PHP 8+ environment that includes the `pdo_mysql` and `ftp` extensions.

## Features

- Record backup information in the `backups` database table.
- Upload backup files directly to an FTP server for safe storage.
- List previously recorded backups and download the stored file through the FTP connection.
- Publish software packages grouped by device type/model so they can be downloaded whenever needed.
- Responsive interface built with plain PHP, HTML and CSS.

## Project structure

```
Backapp/
├── app/
│   ├── Database.php        # Lightweight PDO wrapper
│   └── FtpClient.php       # FTP helper used for uploads and downloads
├── database/
│   ├── backups.sql             # MySQL schema for the backups table
│   └── device_software.sql     # MySQL schema for the software table
├── assets/
│   └── css/
│       └── styles.css      # Basic styling for the application
├── bootstrap.php           # Loads configuration and autoloads classes
├── config.example.php      # Sample configuration (copy to config.php)
├── download.php                # Streams a backup file from the FTP server
├── download_software.php       # Streams a software file from the FTP server
├── index.php               # Main web interface
├── logiciels.php               # Software management interface
└── README.md
```

## Installation

1. **Copy the project to XAMPP**
   - Place the repository inside your XAMPP `htdocs` directory, e.g. `C:/xampp/htdocs/Backapp`.

2. **Create a configuration file**
   - Duplicate `config.example.php` and rename the copy to `config.php`.
   - Edit the new file with your MySQL and FTP credentials. Example:

     ```php
     return [
         'database' => [
             'host' => '127.0.0.1',
             'database' => 'backups',
             'username' => 'root',
             'password' => '',
             'charset' => 'utf8mb4',
             'ensure_backups_table' => true,
             'ensure_device_software_table' => true,
         ],
         'stock_database' => [
             'host' => '127.0.0.1',
             'database' => 'gestion_stock',
             'username' => 'root',
             'password' => '',
             'charset' => 'utf8mb4',
             'ensure_backups_table' => false,
             'ensure_device_software_table' => false,
         ],
         'ftp' => [
             'host' => '192.168.1.50',
             'username' => 'ftp-user',
             'password' => 'change-me',
             'port' => 21,
             'timeout' => 90,
             'base_path' => '/backups',
             'passive' => true,
         ],
     ];
     ```

     The `stock_database` section is used by the inventory module (users, stock, emplacements). It can point to the same MySQL database as the backups module or to a dedicated schema if you prefer to keep the data separate.

3. **Create the MySQL table**
   - Import `database/backups.sql` with phpMyAdmin or the MySQL console to create the `backups` table automatically. The application will also attempt to create the table on first run if it does not yet exist.
   - (Optional) Import `database/device_software.sql` if you need compatibility with earlier versions that relied on the dedicated software table.

4. **Configure the FTP destination**
   - Ensure the FTP user has permission to upload to the folder configured by `base_path`.
   - If the server requires passive mode, keep `passive` set to `true` (default).

## XAMPP & FileZilla quick start

1. Launch Apache, MySQL and FileZilla Server from the XAMPP control panel.
2. Use phpMyAdmin to create a database named `backups` (or another name that you reference in `config.php`).
3. Import `database/backups.sql` so the `backups` table schema matches the application fields shown in the screenshots.
4. Open the FileZilla Server interface and create an FTP user with write access to the directory where you want to store the files (e.g. `C:\backups`). Both sauvegardes and logiciels use the same location.
5. Update `config.php` with the XAMPP MySQL credentials (usually `root`/empty password) and the FTP account you created on FileZilla Server. Ensure `base_path` points to the remote folder that should contain both the sauvegardes and the logiciels.
6. Naviguez vers `http://localhost/Backapp/index.php` pour commencer à enregistrer des sauvegardes. L'application téléversera les fichiers via FileZilla Server et stockera les métadonnées dans la base MySQL. Utilisez `logiciels.php` pour ajouter ou télécharger les logiciels.

5. **Run the application**
   - Start Apache and MySQL in the XAMPP control panel.
   - Open a browser and navigate to `http://localhost/Backapp/index.php`.

## Usage

1. Fill in the form with equipment, client, backup date and any other information.
2. Select the backup file to upload. The file will be copied to the FTP server.
3. Submit the form to store the record in MySQL.
4. Use the download links in the table to retrieve a stored file from the FTP server whenever needed.
5. Admin users can ouvrir `logiciels.php` pour gérer les logiciels avec la même interface que les sauvegardes : mêmes champs, mêmes filtres et même répertoire FTP.

## Notes

- The application uses PHP sessions to display success messages after saving a record.
- If the FTP extension is not enabled in PHP, uploading and downloading will fail; enable the extension in `php.ini` if required.
- For production use, protect the application with authentication and HTTPS.
