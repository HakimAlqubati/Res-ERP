<?php

namespace App\Services\Tenancy;

use App\Models\CustomTenantModel;
use InvalidArgumentException;
use RuntimeException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class TenantBackupService
{
    /**
     * Dump the tenant database, compress it into a ZIP archive, and upload it to Google Drive.
     *
     * @throws Throwable
     */
    public function backupToGoogleDrive(CustomTenantModel $tenant): string
    {
        $dbName = (string) $tenant->database;

        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]{1,62}$/', $dbName)) {
            throw new InvalidArgumentException("Invalid tenant database name: {$dbName}");
        }

        $timestamp = now()->format('Y_m_d_His');
        $sqlFileName = "{$dbName}_{$timestamp}.sql";
        $zipFileName = "{$dbName}_{$timestamp}.zip";

        $sqlPath = storage_path("app/backups/{$sqlFileName}");
        $zipPath = storage_path("app/backups/{$zipFileName}");

        $connectionConfig = config('database.connections.landlord')
            ?? config('database.connections.mysql', []);

        $host = (string) ($connectionConfig['host'] ?? '127.0.0.1');
        $port = (string) ($connectionConfig['port'] ?? '3306');
        $username = (string) ($connectionConfig['username'] ?? 'root');
        $password = (string) ($connectionConfig['password'] ?? '');

        try {
            Storage::disk('local')->makeDirectory('backups');

            $process = new Process(
                command: [
                    'mysqldump',
                    "--host={$host}",
                    "--port={$port}",
                    "--user={$username}",
                    "--ignore-table={$dbName}.audits",
                    "--ignore-table={$dbName}.app_logs",
                    "--result-file={$sqlPath}",
                    $dbName,
                ],
                env: $password !== '' ? ['MYSQL_PWD' => $password] : null,
                timeout: 3600,
            );

            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Failed to create backup ZIP archive.');
            }

            $zip->addFile($sqlPath, $sqlFileName);
            $zip->close();

            $googlePath = 'backups/' . $zipFileName;
            $stream = fopen($zipPath, 'r');

            if ($stream === false) {
                throw new RuntimeException('Failed to open backup ZIP archive for upload.');
            }

            try {
                Storage::disk('google')->put($googlePath, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return $googlePath;
        } finally {
            if (file_exists($sqlPath)) {
                @unlink($sqlPath);
            }

            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }
        }
    }
}
