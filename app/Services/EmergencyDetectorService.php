<?php

namespace App\Services;

/**
 * EmergencyDetectorService
 *
 * Detecta condiciones de emergencia médica en texto libre de síntomas.
 * Funciona mediante coincidencia de patrones (sin llamar a GPT) para
 * máxima velocidad y mínimo costo. Si detecta una emergencia, el sistema
 * devuelve respuesta inmediata indicando ir a urgencias.
 *
 * Cada condición tiene un arreglo de "reglas". Una regla se cumple cuando
 * TODOS sus términos aparecen en el texto normalizado. Basta con que
 * UNA regla de una condición se cumpla para activar la alerta.
 */
class EmergencyDetectorService
{
    private const CONDITIONS = [

        // ── Síndrome Coronario Agudo / Infarto ────────────────────────
        'sca' => [
            'condicion' => 'Posible Síndrome Coronario Agudo (Infarto)',
            'mensaje'   => 'Los síntomas descritos pueden indicar un infarto agudo de miocardio. Llama al 123 o ve a urgencias AHORA.',
            'rules' => [
                ['dolor de pecho', 'diaforesis'],
                ['dolor de pecho', 'sudoracion fria'],
                ['dolor pecho', 'diaforesis'],
                ['dolor pecho', 'sudoracion fria'],
                ['dolor toracico', 'diaforesis'],
                ['dolor toracico', 'sudoracion fria'],
                ['dolor de pecho', 'brazo izquierdo'],
                ['dolor de pecho', 'mandibula'],
                ['dolor toracico', 'falta de aire'],
                ['dolor toracico', 'nauseas'],
                ['dolor de pecho', 'falta de aire', 'hipertension'],
                ['dolor de pecho', 'falta de aire', 'diabetes'],
                ['dolor precordial'],
                ['infarto'],
                ['sindrome coronario'],
                ['sca'],
            ],
        ],

        // ── ACV / Ictus ───────────────────────────────────────────────
        'acv' => [
            'condicion' => 'Posible ACV / Ictus',
            'mensaje'   => 'Los síntomas pueden indicar un accidente cerebrovascular. Cada minuto cuenta. Llama al 123 o ve a urgencias AHORA.',
            'rules' => [
                ['cara caida'],
                ['asimetria facial'],
                ['no puede hablar'],
                ['no puede levantar el brazo'],
                ['afasia'],
                ['debilidad', 'un lado'],
                ['paralisis', 'un lado'],
                ['debilidad', 'mitad del cuerpo'],
                ['peor dolor de cabeza de mi vida'],
                ['cefalea en trueno'],
                ['cefalea', 'subita', 'nunca habia tenido'],
                ['derrame cerebral'],
                ['ictus'],
                ['acv'],
                ['perdida de vision', 'subita'],
                ['vision doble', 'debilidad'],
            ],
        ],

        // ── Sepsis ────────────────────────────────────────────────────
        'sepsis' => [
            'condicion' => 'Posible Sepsis',
            'mensaje'   => 'Los síntomas pueden indicar una sepsis. Es una emergencia médica. Llama al 123 o ve a urgencias AHORA.',
            'rules' => [
                ['fiebre', 'confusion', 'taquicardia'],
                ['fiebre', 'desorientacion', 'deterioro rapido'],
                ['fiebre', 'hipotension', 'confusion'],
                ['fiebre', 'hipotension', 'taquicardia'],
                ['sepsis'],
                ['choque septico'],
                ['shock septico'],
            ],
        ],

        // ── Dengue con signos de alarma ───────────────────────────────
        'dengue_alarma' => [
            'condicion' => 'Posible Dengue con Signos de Alarma',
            'mensaje'   => 'Los síntomas pueden indicar Dengue grave. Ve a urgencias para evaluación inmediata.',
            'rules' => [
                ['fiebre', 'dolor abdominal intenso', 'vomito'],
                ['fiebre', 'sangrado', 'petequias'],
                ['dengue', 'sangrado'],
                ['dengue', 'dolor abdominal intenso'],
                ['dengue', 'vomito persistente'],
            ],
        ],

        // ── Anafilaxia ────────────────────────────────────────────────
        'anafilaxia' => [
            'condicion' => 'Posible Anafilaxia',
            'mensaje'   => 'Los síntomas pueden indicar una reacción alérgica grave. Llama al 123 o ve a urgencias AHORA.',
            'rules' => [
                ['dificultad para respirar', 'alergia'],
                ['dificultad respirar', 'picadura'],
                ['dificultad respirar', 'medicamento'],
                ['garganta se cierra'],
                ['hinchazón de garganta'],
                ['hinchazón garganta'],
                ['anafilaxia'],
                ['reaccion alergica', 'dificultad respirar'],
                ['urticaria', 'dificultad para respirar'],
            ],
        ],

        // ── Tromboembolismo Pulmonar ──────────────────────────────────
        'tep' => [
            'condicion' => 'Posible Tromboembolismo Pulmonar',
            'mensaje'   => 'Los síntomas pueden indicar un tromboembolismo pulmonar. Llama al 123 o ve a urgencias AHORA.',
            'rules' => [
                ['disnea subita', 'cirugia'],
                ['disnea subita', 'inmovilizacion'],
                ['falta de aire', 'tos con sangre'],
                ['sangre al toser', 'dolor al respirar'],
                ['embolia pulmonar'],
                ['tromboembolismo'],
            ],
        ],

        // ── Crisis Hipertensiva ───────────────────────────────────────
        'crisis_hipertensiva' => [
            'condicion' => 'Posible Crisis Hipertensiva',
            'mensaje'   => 'Una presión arterial muy alta puede ser una emergencia. Ve a urgencias de inmediato.',
            'rules' => [
                ['presion muy alta', 'cefalea intensa'],
                ['hipertension', 'cefalea intensa', 'vision borrosa'],
                ['tension muy alta', 'cefalea'],
                ['crisis hipertensiva'],
                ['presion', '200'],
                ['presion', '220'],
                ['presion', '230'],
                ['presion', '240'],
                ['presion', '250'],
            ],
        ],

        // ── Abdomen Agudo ─────────────────────────────────────────────
        'abdomen_agudo' => [
            'condicion' => 'Posible Abdomen Agudo',
            'mensaje'   => 'El dolor abdominal severo con estas características puede requerir cirugía urgente. Ve a urgencias de inmediato.',
            'rules' => [
                ['dolor abdominal', 'vientre duro'],
                ['abdomen rigido'],
                ['rigidez abdominal'],
                ['peritonitis'],
                ['dolor abdominal', 'no puede moverse del dolor'],
                ['apendicitis', 'perforacion'],
            ],
        ],

        // ── Eclampsia ─────────────────────────────────────────────────
        'eclampsia' => [
            'condicion' => 'Posible Eclampsia',
            'mensaje'   => 'Las convulsiones durante el embarazo son una emergencia. Llama al 123 o ve a urgencias AHORA.',
            'rules' => [
                ['convulsion', 'embarazo'],
                ['convulsion', 'embarazada'],
                ['eclampsia'],
                ['preeclampsia', 'convulsion'],
            ],
        ],

        // ── Dificultad respiratoria severa ────────────────────────────
        'dificultad_respiratoria' => [
            'condicion' => 'Dificultad Respiratoria Severa',
            'mensaje'   => 'La dificultad grave para respirar es una emergencia. Llama al 123 o ve a urgencias AHORA.',
            'rules' => [
                ['no puede respirar'],
                ['no puedo respirar'],
                ['asfixia'],
                ['ahogamiento'],
                ['se esta ahogando'],
            ],
        ],

        // ── Pérdida de consciencia ────────────────────────────────────
        'perdida_conciencia' => [
            'condicion' => 'Pérdida de Consciencia',
            'mensaje'   => 'La pérdida de consciencia es una emergencia médica. Llama al 123 de inmediato.',
            'rules' => [
                ['perdida de conciencia'],
                ['perdio el conocimiento'],
                ['no responde'],
                ['inconsciente'],
                ['desmayo', 'no reacciona'],
                ['en coma'],
            ],
        ],

        // ── Convulsiones ──────────────────────────────────────────────
        'convulsion' => [
            'condicion' => 'Convulsión activa',
            'mensaje'   => 'Una convulsión es una emergencia neurológica. Llama al 123 o ve a urgencias AHORA.',
            'rules' => [
                ['convulsion activa'],
                ['esta convulsionando'],
                ['convulsiona ahora'],
                ['ataque epileptico', 'no para'],
            ],
        ],

    ];

    /**
     * Analiza el texto de síntomas (y antecedentes) y retorna la primera
     * condición de emergencia detectada, o null si no hay ninguna.
     *
     * @return array{condicion: string, mensaje: string}|null
     */
    public function detectar(string $sintomas, ?string $antecedentes = null): ?array
    {
        $text = $this->normalizar($sintomas . ' ' . ($antecedentes ?? ''));

        foreach (self::CONDITIONS as $def) {
            foreach ($def['rules'] as $rule) {
                if ($this->ruleMatches($text, $rule)) {
                    return [
                        'condicion' => $def['condicion'],
                        'mensaje'   => $def['mensaje'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Una regla se cumple si TODOS sus términos aparecen en el texto.
     */
    private function ruleMatches(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            if (strpos($text, $term) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Normaliza el texto: minúsculas + elimina tildes + colapsa espacios.
     */
    private function normalizar(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ñ' => 'n',
        ]);
        return preg_replace('/\s+/', ' ', trim($text));
    }
}
