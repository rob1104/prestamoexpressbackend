<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClienteRequest $request)
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
        $cliente->delete();
        return response()->json([
            'message' => 'El cliente ha sido eliminado definitivamente.'
        ]);
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
}
