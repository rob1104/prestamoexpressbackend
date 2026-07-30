<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetDatabaseRequest;
use App\Http\Requests\RestoreBackupRequest;
use App\Services\DatabaseAdminService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class DatabaseAdminController extends Controller
{
    protected $databaseAdminService;

    public function __construct(DatabaseAdminService $databaseAdminService)
    {
        $this->databaseAdminService = $databaseAdminService;
    }

    /**
     * List all backups
     */
    public function index(): JsonResponse
    {
        // Require permission: database.backup (or specific listing permission)
        if (!auth()->user()->can('database.backup')) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $backups = $this->databaseAdminService->getBackups();
        return response()->json($backups);
    }

    /**
     * Create a new backup
     */
    public function store(): JsonResponse
    {
        if (!auth()->user()->can('database.backup')) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        try {
            $result = $this->databaseAdminService->createBackup();
            
            activity()
                ->causedBy(auth()->user())
                ->log("Creó un respaldo de la base de datos: {$result['filename']}");

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el respaldo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a backup
     */
    public function destroy(string $filename): JsonResponse
    {
        if (!auth()->user()->can('database.backup')) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($this->databaseAdminService->deleteBackup($filename)) {
            activity()
                ->causedBy(auth()->user())
                ->log("Eliminó el respaldo: {$filename}");

            return response()->json([
                'success' => true,
                'message' => 'Respaldo eliminado correctamente.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se encontró el respaldo.'
        ], 404);
    }

    /**
     * Download a backup
     */
    public function download(string $filename)
    {
        if (!auth()->user()->can('database.backup')) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if (!Storage::disk('local')->exists('backups/' . $filename)) {
            return response()->json(['message' => 'Archivo no encontrado.'], 404);
        }

        activity()
            ->causedBy(auth()->user())
            ->log("Descargó el respaldo: {$filename}");

        return Storage::disk('local')->download('backups/' . $filename);
    }

    /**
     * Restore from a backup
     */
    public function restore(RestoreBackupRequest $request): JsonResponse
    {
        if (!auth()->user()->can('database.restore')) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        try {
            // Put app in maintenance mode
            Artisan::call('down', [
                '--secret' => 'restore-db-secret-key-123'
            ]);

            $result = $this->databaseAdminService->restoreBackup($request->filename);
            
            // Clear caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            // Bring app back up
            Artisan::call('up');

            activity()
                ->causedBy(auth()->user())
                ->log("Restauró la base de datos utilizando el respaldo: {$request->filename}");

            return response()->json($result);
        } catch (Exception $e) {
            Artisan::call('up');
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset the database
     */
    public function reset(ResetDatabaseRequest $request): JsonResponse
    {
        if (!auth()->user()->can('database.reset')) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        try {
            Artisan::call('down', [
                '--secret' => 'reset-db-secret-key-123'
            ]);

            $result = $this->databaseAdminService->resetDatabase();

            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            
            Artisan::call('up');

            activity()
                ->causedBy(auth()->user())
                ->log("Reinicializó completamente el sistema (borrado de datos).");

            return response()->json($result);
        } catch (Exception $e) {
            Artisan::call('up');
            return response()->json([
                'success' => false,
                'message' => 'Error al reinicializar: ' . $e->getMessage()
            ], 500);
        }
    }
}
