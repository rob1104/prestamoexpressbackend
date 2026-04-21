<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Boleta;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Cliente::get());
    }

    public function search(Request $request)
    {
        $search = $request->query('search');

        if (!$search) {
            return Cliente::limit(50)->get(['id', 'nombre', 'apellido_paterno', 'apellido_materno', 'identificacion', 'clasificacion']);
        }

        return Cliente::where('nombre', 'LIKE', "%{$search}%")
            ->orWhere('apellido_paterno', 'LIKE', "%{$search}%") // Busca por Apellido Paterno
            ->orWhere('apellido_materno', 'LIKE', "%{$search}%") // Busca por Apellido Materno
            ->orWhere('id', 'LIKE', "{$search}%")
            ->orderBy('nombre', 'asc')
            ->limit(50)
            ->get(['id', 'nombre', 'apellido_paterno', 'apellido_materno', 'identificacion', 'clasificacion']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClienteRequest $request)
    {
        $data = $request->validated();

        // Normalización a mayúsculas antes de guardar
        $data['nombre'] = strtoupper($data['nombre']);
        $data['apellido_paterno'] = strtoupper($data['apellido_paterno'] ?? '');
        $data['apellido_materno'] = strtoupper($data['apellido_materno'] ?? '');
        $data['rfc'] = strtoupper($data['rfc'] ?? '');

        // Procesar INE FRENTE
        if ($request->has('ineFrente') && !empty($request->ineFrente)) {
            $data['ineFrente'] = $this->uploadImage($request->ineFrente, 'clientes/ines');
        }

        // Procesar INE Reverso
        if ($request->has('ineReverso') && !empty($request->ineReverso)) {
            $data['ineReverso'] = $this->uploadImage($request->ineReverso, 'clientes/ines');
        }

        $cliente = Cliente::create($data);
        return response()->json([
            'message' => 'Cliente registrado exitosamente en el sistema.',
            'cliente' => $cliente
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente)
    {
        return response()->json($cliente);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClienteRequest $request, Cliente $cliente)
    {

        $data = $request->validated();

        // Procesar INE FRENTE
        if ($request->has('ineFrente') && !empty($request->ineFrente)) {
            $data['ineFrente'] = $this->uploadImage($request->ineFrente, 'clientes/ines');
        }

        // Procesar INE Reverso
        if ($request->has('ineReverso') && !empty($request->ineReverso)) {
            $data['ineReverso'] = $this->uploadImage($request->ineReverso, 'clientes/ines');
        }

        $cliente->update($data);

        return response()->json([
            'message' => 'Información del cliente actualizada correctamente.',
            'cliente' => $cliente
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente)
    {
        if ($cliente->boletas()->exists()) {
           return response()->json([
               'status' => 'warning',
               'message' => 'No es posible eliminar al cliente. Existen boletas (folios) registradas a su nombre en el historial.'
           ], 422);
        }
        try {
            $cliente->delete();
            return response()->json([
                'message' => 'El cliente ha sido eliminado definitivamente.'
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al intentar eliminar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Función auxiliar para procesar Base64 y guardar como archivo
     */
    private function uploadImage($base64_string, $folder)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
            $data = substr($base64_string, strpos($base64_string, ',') + 1);
            $extension = strtolower($type[1]);
            $data = base64_decode($data);
            $fileName = Str::random(20) . '.' . $extension;
            $path = "{$folder}/{$fileName}";
            Storage::disk('local')->put($path, $data);
            return $path;
        }
        return null;
    }

    public function verIne($path)
    {
        // 1. Verificación de seguridad: solo usuarios autenticados (ya hecho por middleware)
        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        // 2. Entregar el archivo directamente al navegador sin URL pública
        return Storage::disk('local')->response($path);
    }

    public function updateClasificacion(Request $request, $id)
    {
        $request->validate(['clasificacion' => 'required|string']);

        $cliente = Cliente::findOrFail($id);
        $cliente->clasificacion = $request->clasificacion;
        $cliente->save();

        return response()->json([
            'message' => 'Clasificación actualizada correctamente'
        ]);
    }

    public function resumenOperaciones(Cliente $cliente)
    {
        $hoy = Carbon::now();

        // 1. Obtener todas las boletas del cliente (solo las necesarias para el resumen)
        $boletas = Boleta::where('cliente_id', $cliente->id)
            ->where('tipo_prestamo', 'tradicional')
            ->get();

        // 2. Cálculos para la pestaña "TRADICIONAL"

        // Liquidaciones/Terminados (Estado 'liquidada' o 'cerrada')
        $terminados = $boletas->whereIn('estatus', ['LI']);

        // Préstamos en Proceso (Estado 'activa')
        $enProceso = $boletas->where('estatus', 'PE');

        // Desglose de proceso: Vigentes vs Vencidos
        $vigentes = $enProceso->where('fecha_vencimiento', '>=', $hoy->toDateString());
        $vencidos = $enProceso->where('fecha_vencimiento', '<', $hoy->toDateString());

        // 3. Estructura de respuesta
        return response()->json([
            // Datos para la pestaña Tradicional
            'terminados_count' => $terminados->count(),
            'terminados_sum'   => $terminados->sum('prestamo'),

            'liquidaciones_count' => $terminados->count(),
            'liquidaciones_sum'   => $terminados->sum('prestamo'),

            'proceso_count' => $enProceso->count(),
            'proceso_sum'   => $enProceso->sum('prestamo'),

            'vigentes_count' => $vigentes->count(),
            'vigentes_sum'   => $vigentes->sum('prestamo'),

            'vencidos_count' => $vencidos->count(),
            'vencidos_sum'   => $vencidos->sum('prestamo'),

            // Datos para la pestaña Refrendos (Simulado o desde tabla de pagos)
            'refrendos_count' => 0,
            'refrendos_sum'   => 0.00,
        ]);
    }
}
