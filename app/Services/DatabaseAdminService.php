<?php

namespace App\Services;

use Ifsnop\Mysqldump as IMysqldump;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Exception;
use PDO;

class DatabaseAdminService
{
    protected $backupDisk = 'local';
    protected $backupPath = 'backups';
    protected $protectedTables = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'migrations',
        'password_reset_tokens',
        'sessions',
        'personal_access_tokens',
        'cajas',
        'categorias',
        'categorias_joyeria',
        'clasificaciones_joyeria',
        'comision_configs',
        'cotizacion_oros',
        'promocions',
        'recargo_configs',
        'sucursal_configs',
        'flujo_conceptos'
    ];

    /**
     * Create a backup of the database using pure PHP (no external binaries)
     *
     * @return array
     * @throws Exception
     */
    public function createBackup(): array
    {
        $filename = date('Y-m-d_H-i-s') . '.sql';
        $zipFilename = $filename . '.zip';
        
        $tempDir = Storage::disk($this->backupDisk)->path('temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $sqlFilePath = $tempDir . DIRECTORY_SEPARATOR . $filename;
        $zipFilePath = Storage::disk($this->backupDisk)->path($this->backupPath . '/' . $zipFilename);

        try {
            // Create the backup directory if it doesn't exist
            $backupDir = Storage::disk($this->backupDisk)->path($this->backupPath);
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            // Connection parameters
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port');
            $db = config('database.connections.mysql.database');
            $user = config('database.connections.mysql.username');
            $pass = config('database.connections.mysql.password');

            $dsn = "mysql:host={$host};port={$port};dbname={$db}";
            
            // Create the dump using pure PHP
            $dumpSettings = [
                'compress' => IMysqldump\Mysqldump::NONE,
                'no-data' => false,
                'add-drop-table' => true,
                'single-transaction' => true,
                'lock-tables' => true,
                'routines' => true,
                'events' => true,
                'skip-definer' => true,
            ];

            // Use the pure PHP dumper
            $dumper = new IMysqldump\Mysqldump($dsn, $user, $pass, $dumpSettings);
            $dumper->start($sqlFilePath);

            // Zip the file
            $zip = new ZipArchive();
            $zipResult = $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($zipResult === true) {
                if (File::exists($sqlFilePath)) {
                    $zip->addFile($sqlFilePath, $filename);
                    $zip->close();
                } else {
                    throw new Exception("El archivo SQL temporal no se generó correctamente.");
                }
            } else {
                throw new Exception("No se pudo crear el archivo ZIP. Código de error: " . $zipResult . ". Ruta: " . $zipFilePath);
            }

            // Log
            Log::info("Backup creado exitosamente (Pure PHP): " . $zipFilename);

            return [
                'success' => true,
                'message' => 'Respaldo creado exitosamente.',
                'filename' => $zipFilename
            ];
        } catch (Exception $e) {
            Log::error("Error al crear backup: " . $e->getMessage());
            throw $e;
        } finally {
            // Clean up temp SQL file
            if (File::exists($sqlFilePath)) {
                File::delete($sqlFilePath);
            }
        }
    }

    /**
     * Get all available backups
     *
     * @return array
     */
    public function getBackups(): array
    {
        $files = Storage::disk($this->backupDisk)->files($this->backupPath);
        $backups = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $backups[] = [
                    'name' => basename($file),
                    'size' => Storage::disk($this->backupDisk)->size($file),
                    'date' => date('Y-m-d H:i:s', Storage::disk($this->backupDisk)->lastModified($file))
                ];
            }
        }

        // Sort by date descending
        usort($backups, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $backups;
    }

    /**
     * Delete a backup
     *
     * @param string $filename
     * @return bool
     */
    public function deleteBackup(string $filename): bool
    {
        $path = $this->backupPath . '/' . $filename;
        if (Storage::disk($this->backupDisk)->exists($path)) {
            Log::info("Backup eliminado: " . $filename);
            return Storage::disk($this->backupDisk)->delete($path);
        }
        return false;
    }

    /**
     * Restore a backup using PDO (Pure PHP)
     *
     * @param string $filename
     * @return array
     * @throws Exception
     */
    public function restoreBackup(string $filename): array
    {
        $zipFilePath = Storage::disk($this->backupDisk)->path($this->backupPath . '/' . $filename);
        if (!File::exists($zipFilePath)) {
            throw new Exception("El archivo de respaldo no existe.");
        }

        $tempDir = Storage::disk($this->backupDisk)->path('temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) === true) {
            $sqlFilename = str_replace('.zip', '', $filename);
            $zip->extractTo($tempDir, $sqlFilename);
            $zip->close();

            $sqlFilePath = $tempDir . DIRECTORY_SEPARATOR . $sqlFilename;
            
            try {
                // Restore purely via PHP/PDO, avoiding mysql binary winsock errors
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::unprepared(file_get_contents($sqlFilePath));
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                Log::info("Backup restaurado exitosamente (Pure PHP): " . $filename);
                return [
                    'success' => true,
                    'message' => 'Respaldo restaurado exitosamente.'
                ];
            } catch (Exception $e) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                Log::error("Error al restaurar backup (PDO): " . $e->getMessage());
                throw $e;
            } finally {
                // Clean up extracted SQL file
                if (File::exists($sqlFilePath)) {
                    File::delete($sqlFilePath);
                }
            }
        } else {
            throw new Exception('No se pudo abrir el archivo ZIP.');
        }
    }

    /**
     * Reset the database, keeping only protected tables.
     *
     * @return array
     * @throws Exception
     */
    public function resetDatabase(): array
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $dbName = 'Tables_in_' . config('database.connections.mysql.database');
            
            if (empty($tables) || !isset($tables[0]->{$dbName})) {
                // Fallback field name if database name is empty or different
                $dbName = array_keys(get_object_vars($tables[0]))[0];
            }

            foreach ($tables as $tableInfo) {
                $table = $tableInfo->{$dbName};
                if (!in_array($table, $this->protectedTables)) {
                    DB::table($table)->truncate();
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            Log::info("Base de datos reinicializada. Tablas truncadas exitosamente.");

            return [
                'success' => true,
                'message' => 'Sistema reinicializado correctamente. Solo se conservaron los datos de acceso y configuración.'
            ];
        } catch (Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Ensure FK checks are re-enabled on error
            Log::error("Error al reinicializar sistema: " . $e->getMessage());
            throw $e;
        }
    }
}
