<?php

namespace App\Services;

use App\Http\Resources\MedicoResource;
use App\Models\Consulta;
use App\Models\Medico;
use App\Models\Recomendacion;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * RecomendadorService
 *
 * Flujo completo:
 *  1. El texto libre del usuario se envía a GPT-4o-mini para extraer
 *     los términos médicos reconocidos del JSON de keywords.
 *  2. KeywordMatcherService normaliza los términos (sinónimos → canónico)
 *     y puntúa cada especialidad con sus pesos.
 *  3. Se buscan médicos disponibles en las top 3 especialidades.
 *  4. El resultado se devuelve estructurado (y opcionalmente se persiste en BD).
 */
class RecomendadorService
{
    private const GPT_MODEL = 'gpt-4o-mini';

    public function __construct(
        private readonly KeywordMatcherService $matcher
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINT PÚBLICO: analizar sin guardar en BD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Analiza síntomas en lenguaje natural y devuelve especialidades + médicos.
     * No requiere autenticación ni persiste en BD.
     */
    public function analizar(
        string  $sintomas,
        ?string $enfermedadesPrevias = null,
        ?int    $edad = null,
        ?string $sexo = null,
        ?float  $latitud  = null,
        ?float  $longitud = null,
        ?string $tiempoEvolucion = null
    ): array {
        $gptData           = $this->extraerTerminosViaGPT($sintomas, $enfermedadesPrevias, $edad, $sexo, $tiempoEvolucion);
        $normalizedTerms   = $this->matcher->normalizeTerms($gptData['terminos_identificados'] ?? []);
        $topEspecialidades = $this->matcher->scoreSpecialties($normalizedTerms);

        // Escalar a "critico" si alguna especialidad activó red_flags
        $nivelUrgencia = $gptData['nivel_urgencia'] ?? 'normal';
        foreach ($topEspecialidades as $esp) {
            if (! empty($esp['red_flags'])) {
                $nivelUrgencia = 'critico';
                break;
            }
        }

        $especialidadesResult = $this->buildEspecialidadesResult($topEspecialidades, $latitud, $longitud);

        // Quitar clave interna antes de devolver
        $output = array_map(static function ($e) {
            unset($e['medicos_raw']);
            return $e;
        }, $especialidadesResult);

        return [
            'nivel_urgencia'     => $nivelUrgencia,
            'resumen_ia'         => $gptData['resumen_medico'] ?? '',
            'terminos_extraidos' => $normalizedTerms,
            'especialidades'     => $output,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINT AUTENTICADO: analizar + guardar en BD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Analiza síntomas de una Consulta ya persistida,
     * guarda las Recomendaciones en BD y devuelve el resultado completo.
     */
    public function recomendar(Consulta $consulta): array
    {
        $gptData           = $this->extraerTerminosViaGPT(
            $consulta->sintomas,
            $consulta->enfermedades_previas
        );
        $normalizedTerms   = $this->matcher->normalizeTerms($gptData['terminos_identificados'] ?? []);
        $topEspecialidades = $this->matcher->scoreSpecialties($normalizedTerms);

        $nivelUrgencia = $gptData['nivel_urgencia'] ?? 'normal';
        foreach ($topEspecialidades as $esp) {
            if (! empty($esp['red_flags'])) {
                $nivelUrgencia = 'critico';
                break;
            }
        }

        $especialidadesResult = $this->buildEspecialidadesResult($topEspecialidades);

        // Persistir recomendaciones en BD
        foreach ($especialidadesResult as $espResult) {
            foreach ($espResult['medicos_raw'] as $medico) {
                Recomendacion::create([
                    'consulta_id'      => $consulta->id,
                    'medico_id'        => $medico->id,
                    'posicion'         => $espResult['posicion'],
                    'puntuacion_ia'    => $espResult['puntuacion'],
                    'justificacion_ia' => $espResult['justificacion'],
                    'modelo_ia'        => self::GPT_MODEL,
                ]);
            }
        }

        // Limpiar clave interna
        $output = array_map(static function ($e) {
            unset($e['medicos_raw']);
            return $e;
        }, $especialidadesResult);

        return [
            'nivel_urgencia'     => $nivelUrgencia,
            'resumen_ia'         => $gptData['resumen_medico'] ?? '',
            'terminos_extraidos' => $normalizedTerms,
            'especialidades'     => $output,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GPT-4o-mini: extracción de términos
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía el texto del usuario a GPT-4o-mini junto con la lista completa
     * de términos reconocidos del JSON.
     * GPT devuelve SOLO términos que estén en esa lista.
     */
    private function extraerTerminosViaGPT(string $sintomas, ?string $previas, ?int $edad = null, ?string $sexo = null, ?string $tiempoEvolucion = null): array
    {
        $termsList = $this->matcher->buildTermsList();
        $termsStr  = implode(', ', $termsList);

        $userContent = "Síntomas: {$sintomas}";
        if ($previas) {
            $userContent .= "\nAntecedentes: {$previas}";
        }
        if ($edad !== null) {
            $userContent .= "\nEdad del paciente: {$edad} años";
        }
        if ($sexo !== null) {
            $userContent .= "\nSexo biológico: {$sexo}";
        }
        if ($tiempoEvolucion !== null) {
            $userContent .= "\nTiempo de evolución de los síntomas: {$tiempoEvolucion}";
        }

        $systemPrompt = <<<'SYS'
Eres un extractor de terminología médica especializado. Analiza la descripción del paciente
(incluyendo edad y sexo biológico si se proporcionan, ya que influyen en la prevalencia de
ciertas enfermedades) e identifica qué términos médicos de la lista corresponden a lo que
describe. Responde solo con JSON válido.
SYS;

        $userPrompt = <<<PROMPT
LISTA DE TÉRMINOS RECONOCIDOS (usa SOLO estos, sin inventar otros):
{$termsStr}

DESCRIPCIÓN DEL PACIENTE:
{$userContent}

Responde ÚNICAMENTE con este JSON:
{
  "terminos_identificados": ["término1", "término2"],
  "nivel_urgencia": "normal",
  "resumen_medico": "Frase breve describiendo los síntomas en lenguaje médico"
}

Reglas:
- Incluye un término SOLO si está EXACTAMENTE en la lista anterior.
- nivel_urgencia: "normal" | "urgente" | "critico" (critico = riesgo vital).
- resumen_medico: máximo 2 oraciones.
PROMPT;

        try {
            $response = OpenAI::chat()->create([
                'model'           => self::GPT_MODEL,
                'messages'        => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.1,
                'max_tokens'      => 500,
            ]);

            $data = json_decode($response->choices[0]->message->content, true);

            // Validar que los términos devueltos existen en la lista
            $validSet = array_flip($termsList);
            $data['terminos_identificados'] = array_values(
                array_filter(
                    $data['terminos_identificados'] ?? [],
                    static fn ($t) => isset($validSet[$t])
                )
            );

            return $data;

        } catch (\Throwable $e) {
            Log::error('RecomendadorService@extraerTerminos: ' . $e->getMessage());
            return [
                'terminos_identificados' => [],
                'nivel_urgencia'         => 'normal',
                'resumen_medico'         => '',
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Construcción del resultado
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Para cada especialidad puntuada, busca médicos en la BD y arma la respuesta.
     */
    private function buildEspecialidadesResult(array $topEspecialidades, ?float $latitud = null, ?float $longitud = null): array
    {
        $results = [];
        $hasGeo  = $latitud !== null && $longitud !== null;

        foreach ($topEspecialidades as $idx => $esp) {
            $query = Medico::with('user', 'especialidad')
                ->whereHas('especialidad', fn ($q) =>
                    $q->where('nombre', $esp['specialty_name'])
                )
                ->where('disponible', true)
                ->orderByDesc('calificacion_promedio');

            if ($hasGeo) {
                // Fetch more candidates so we can sort by distance
                $candidates = $query->take(30)->get();

                foreach ($candidates as $medico) {
                    if ($medico->latitud !== null && $medico->longitud !== null) {
                        $medico->distancia_km = $this->haversineKm(
                            $latitud, $longitud,
                            (float) $medico->latitud,
                            (float) $medico->longitud
                        );
                    } else {
                        $medico->distancia_km = PHP_FLOAT_MAX;
                    }
                }

                $medicos = $candidates
                    ->sortBy('distancia_km')
                    ->take(3)
                    ->values();
            } else {
                $medicos = $query->take(3)->get();
            }

            $justificacion = $this->buildJustificacion(
                $esp['specialty_name'],
                $esp['matched_terms'],
                $esp['red_flags'],
            );

            $results[] = [
                'posicion'         => $idx + 1,
                'especialidad'     => $esp['specialty_name'],
                'puntuacion'       => $esp['score'],
                'terminos_matched' => $esp['matched_terms'],
                'red_flags'        => $esp['red_flags'],
                'urgencia'         => $esp['urgency'],
                'justificacion'    => $justificacion,
                'medicos'          => MedicoResource::collection($medicos),
                'medicos_raw'      => $medicos, // solo para persistencia interna
            ];
        }

        return $results;
    }

    /**
     * Genera una justificación legible de por qué se recomienda la especialidad.
     */
    // ─────────────────────────────────────────────────────────────────────────
    // Haversine: distancia en km entre dos coordenadas
    // ─────────────────────────────────────────────────────────────────────────

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function buildJustificacion(
        string $especialidad,
        array  $matched,
        array  $redFlags
    ): string {
        $partes = [];

        if (! empty($matched)) {
            $lista    = implode(', ', $matched);
            $partes[] = "Se detectaron síntomas compatibles con {$especialidad}: {$lista}.";
        }

        if (! empty($redFlags)) {
            $lista    = implode(', ', $redFlags);
            $partes[] = "⚠️ Señales de alerta: {$lista}. Se recomienda atención prioritaria.";
        }

        return implode(' ', $partes) ?: "Perfil sintomático compatible con {$especialidad}.";
    }
}

