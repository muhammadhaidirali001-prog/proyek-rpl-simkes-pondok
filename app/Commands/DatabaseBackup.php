<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class DatabaseBackup extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Database';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'db:backup';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Cadangkan (backup) skema dan data database SIMKES Santri secara aman.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'db:backup [filename]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'filename' => 'Nama file backup kustom (opsional).',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        CLI::write('=== PROSES CADANGAN (BACKUP) DATABASE SIMKES SANTRI ===', 'green');

        $db = Database::connect();
        $dbName = $db->getDatabase();

        CLI::write("Menghubungkan ke database: {$dbName} ...", 'yellow');

        // Pastikan direktori backup terproteksi
        $backupDir = WRITEPATH . 'backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Amankan direktori dari akses web peramban
        $htaccessPath = $backupDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n");
        }
        $indexHtmlPath = $backupDir . 'index.html';
        if (!file_exists($indexHtmlPath)) {
            file_put_contents($indexHtmlPath, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
        }

        $tables = $db->listTables();
        if (empty($tables)) {
            CLI::error('Database tidak memiliki tabel untuk dicadangkan.');
            return;
        }

        $timestamp = date('Ymd_His');
        $customName = $params[0] ?? null;
        $fileName = $customName ? ($customName . '.sql') : "simkes_backup_{$dbName}_{$timestamp}.sql";
        $filePath = $backupDir . $fileName;

        $handle = fopen($filePath, 'w');
        if (!$handle) {
            CLI::error("Gagal membuat berkas backup di {$filePath}");
            return;
        }

        // Tulis Header SQL
        fwrite($handle, "-- ==========================================================\n");
        fwrite($handle, "-- CADANGAN DATABASE SIMKES SANTRI\n");
        fwrite($handle, "-- Database: {$dbName}\n");
        fwrite($handle, "-- Tanggal Ekspor: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Host: " . $db->hostname . "\n");
        fwrite($handle, "-- ==========================================================\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        fwrite($handle, "SET time_zone = '+00:00';\n\n");

        foreach ($tables as $table) {
            CLI::write("Memproses tabel: {$table} ...", 'white');

            // 1. Dapatkan DDL CREATE TABLE
            $createTableQuery = $db->query("SHOW CREATE TABLE `{$table}`")->getRowArray();
            $createSql = $createTableQuery['Create Table'] ?? null;

            if ($createSql) {
                fwrite($handle, "-- ----------------------------------------------------------\n");
                fwrite($handle, "-- Struktur Tabel `{$table}`\n");
                fwrite($handle, "-- ----------------------------------------------------------\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createSql . ";\n\n");
            }

            // 2. Dapatkan Baris Data
            $rows = $db->table($table)->get()->getResultArray();
            if (!empty($rows)) {
                fwrite($handle, "-- Data untuk Tabel `{$table}` (" . count($rows) . " baris)\n");

                $fields = array_keys($rows[0]);
                $fieldsList = implode('`, `', $fields);

                foreach ($rows as $row) {
                    $escapedValues = array_map(function ($val) use ($db) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        return $db->escape($val);
                    }, array_values($row));

                    $valuesList = implode(', ', $escapedValues);
                    fwrite($handle, "INSERT INTO `{$table}` (`{$fieldsList}`) VALUES ({$valuesList});\n");
                }
                fwrite($handle, "\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fwrite($handle, "-- SELESAI CADANGAN DATABASE\n");
        fclose($handle);

        $fileSizeKb = round(filesize($filePath) / 1024, 2);

        CLI::newLine();
        CLI::write("BERHASIL! Cadangan database tersimpan dengan aman.", 'green');
        CLI::write("Lokasi Berkas : {$filePath}", 'cyan');
        CLI::write("Ukuran Berkas : {$fileSizeKb} KB", 'cyan');
        CLI::write("Total Tabel   : " . count($tables), 'cyan');
        CLI::newLine();

        CLI::write("--- PROSEDUR PEMULIHAN (RESTORE PROCEDURE) ---", 'yellow');
        CLI::write("Untuk memulihkan data jika terjadi kegagalan bencana (disaster recovery), jalankan:", 'white');
        CLI::write("mysql -h {$db->hostname} -u {$db->username} -p {$dbName} < \"{$filePath}\"", 'yellow');
        CLI::newLine();
    }
}
