<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecomendacionRequest;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Services\RecomendadorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecomendacionController extends Controller
{
    public function __construct(
        private readonly RecomendadorService $recomendadorService
    ) {}

    // ─────────────────────────────────────────────────────────────────────
    // PÚBLICO — sin autenticación
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Endpoint público: analiza síntomas en lenguaje natural,
     * extrae keywords con GPT-4o-mini, puntúa especialidades y
     * devuelve los médicos disponibles más relevantes.
     * No requiere login ni guarda en BD.
     */
    public function analizar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sintomas'             => ['required', 'string', 'min:5', 'max:1000'],
            'enfermedades_previas' => ['nullable', 'string', 'max:500'],
            'edad'                 => ['nullable', 'integer', 'min:0', 'max:120'],
            'sexo'                 => ['nullable', 'string', 'in:masculino,femenino'],
            'latitud'              => ['nullable', 'numeric', 'between:-90,90'],
            'longitud'             => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $resultado = $this->recomendadorService->analizar(
            sintomas:             $validated['sintomas'],
            enfermedadesPrevias:  $validated['enfermedades_previas'] ?? null,
            edad:                 $validated['edad'] ?? null,
            sexo:                 $validated['sexo'] ?? null,
            latitud:              isset($validated['latitud'])  ? (float) $validated['latitud']  : null,
            longitud:             isset($validated['longitud']) ? (float) $validated['longitud'] : null,
        );

        return response()->json($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // AUTENTICADO — requiere Sanctum
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Historial de consultas y recomendaciones del paciente autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $paciente = Paciente::where('user_id', $request->user()->id)->firstOrFail();

        $consultas = Consulta::where('paciente_id', $paciente->id)
            ->with('recomendaciones.medico.especialidad', 'recomendaciones.medico.user')
            ->latest()
            ->paginate(10);

        return response()->json($consultas);
    }

    /**
     * Crea una consulta, la persiste en BD y genera recomendaciones con IA.
     */
    public function store(RecomendacionRequest $request): JsonResponse
    {
        $paciente = Paciente::where('user_id', $request->user()->id)->firstOrFail();

        $consulta = Consulta::create([
            'paciente_id'          => $paciente->id,
            'sintomas'             => $request->validated('sintomas'),
            'enfermedades_previas' => $request->validated('enfermedades_previas'),
            'descripcion_adicional'=> $request->validated('descripcion_adicional'),
            'edad'                 => $request->validated('edad'),
            'sintomas_json'        => $request->validated('sintomas_json'),
        ]);

        $resultado = $this->recomendadorService->recomendar($consulta);

        return response()->json([
            'consulta_id' => $consulta->id,
            ...$resultado,
        ], 201);
    }

    /**
     * Detalle de una consulta con sus recomendaciones guardadas.
     */
    public function show(string $id): JsonResponse
    {
        $consulta = Consulta::with(
            'recomendaciones.medico.especialidad',
            'recomendaciones.medico.user'
        )->findOrFail($id);

        return response()->json($consulta);
    }

    /**
     * Marca una recomendación como seleccionada por el paciente.
     */
    public function seleccionar(
        Request $request,
        string  $consultaId,
        string  $recomendacionId
    ): JsonResponse {
        $consulta = Consulta::findOrFail($consultaId);
        $consulta->recomendaciones()->update(['seleccionado' => false]);
        $consulta->recomendaciones()
            ->where('id', $recomendacionId)
            ->update(['seleccionado' => true]);

        return response()->json(['message' => 'Médico seleccionado correctamente.']);
    }
}
