<?php

namespace App\Services;

/**
 * KeywordMatcherService
 *
 * Carga el JSON de keywords por especialidad y expone dos responsabilidades:
 *  1. buildTermsList()      → lista plana de todos los términos (para enviar a GPT)
 *  2. normalizeTerms()      → convierte sinónimos al término canónico
 *  3. scoreSpecialties()    → puntúa cada especialidad según los términos identificados
 */
class KeywordMatcherService
{
    /** Mapeo de clave JSON → nombre oficial en la BD */
    private const SPECIALTY_MAP = [
        'medicina_general'     => 'Medicina General',
        'cardiologia'          => 'Cardiología',
        'neumologia'           => 'Neumología',
        'gastroenterologia'    => 'Gastroenterología',
        'neurologia'           => 'Neurología',
        'dermatologia'         => 'Dermatología',
        'urologia'             => 'Urología',
        'ginecologia'          => 'Ginecología',
        'ortopedia'            => 'Traumatología',
        'psiquiatria'          => 'Psiquiatría',
        'otorrinolaringologia' => 'Otorrinolaringología',
    ];

    private array $specialties;
    /** Mapa inverso: sinónimo → término canónico */
    private array $synonymMap = [];

    public function __construct()
    {
        $path = resource_path('data/especialidades_keywords.json');
        $this->specialties = json_decode(file_get_contents($path), true);
        $this->buildSynonymMap();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pública: construir lista de términos para GPT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Devuelve una lista plana y única de todos los términos reconocidos
     * (keywords + red_flags + negative_keywords + sinónimos).
     * Esta lista se envía a GPT para restringir su vocabulario.
     */
    public function buildTermsList(): array
    {
        $terms = [];

        foreach ($this->specialties as $data) {
            foreach ($data['keywords']          as $kw) { $terms[] = $kw['term']; }
            foreach ($data['red_flags']         as $rf) { $terms[] = $rf['term']; }
            foreach ($data['negative_keywords'] as $nk) { $terms[] = $nk['term']; }
            foreach ($data['synonyms'] as $canonical => $synList) {
                $terms[] = $canonical;
                foreach ($synList as $syn) {
                    $terms[] = $syn;
                }
            }
        }

        return array_values(array_unique($terms));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pública: normalizar sinónimos → término canónico
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Recibe los términos que devolvió GPT y los normaliza al término canónico
     * usando el mapa de sinónimos construido del JSON.
     */
    public function normalizeTerms(array $rawTerms): array
    {
        $normalized = [];
        foreach ($rawTerms as $term) {
            $normalized[] = $this->synonymMap[$term] ?? $term;
        }
        return array_values(array_unique($normalized));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pública: puntuar especialidades
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Recorre todas las especialidades, suma/resta pesos según los términos
     * normalizados encontrados y devuelve las top 3 con su detalle.
     *
     * @param  array $normalizedTerms  Términos ya normalizados (canónicos)
     * @return array  Top 3 especialidades ordenadas por puntuación desc
     */
    public function scoreSpecialties(array $normalizedTerms): array
    {
        $scores = [];

        foreach ($this->specialties as $key => $data) {
            $score             = 0;
            $matchedTerms      = [];
            $redFlagsTriggered = [];

            // Sumar keywords
            foreach ($data['keywords'] as $kw) {
                if (in_array($kw['term'], $normalizedTerms, true)) {
                    $score         += $kw['weight'];
                    $matchedTerms[] = $kw['term'];
                }
            }

            // Sumar red_flags (pesos altos)
            foreach ($data['red_flags'] as $rf) {
                if (in_array($rf['term'], $normalizedTerms, true)) {
                    $score               += $rf['weight'];
                    $redFlagsTriggered[]  = $rf['term'];
                }
            }

            // Restar negative_keywords
            foreach ($data['negative_keywords'] as $nk) {
                if (in_array($nk['term'], $normalizedTerms, true)) {
                    $score += $nk['weight']; // ya son negativos en el JSON
                }
            }

            if ($score > 0) {
                $scores[] = [
                    'specialty_key'  => $key,
                    'specialty_name' => self::SPECIALTY_MAP[$key]
                        ?? ucwords(str_replace('_', ' ', $key)),
                    'score'          => $score,
                    'matched_terms'  => $matchedTerms,
                    'red_flags'      => $redFlagsTriggered,
                    'urgency'        => ! empty($redFlagsTriggered) ? 'urgente' : 'normal',
                ];
            }
        }

        usort($scores, static fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scores, 0, 3);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Privado
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Construye el mapa inverso sinónimo → canónico para normalizar rápidamente.
     */
    private function buildSynonymMap(): void
    {
        foreach ($this->specialties as $data) {
            foreach ($data['synonyms'] as $canonical => $synList) {
                foreach ($synList as $syn) {
                    // El sinónimo apunta al canónico
                    $this->synonymMap[$syn] = $canonical;
                }
            }
        }
    }
}
