<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recomendacion extends Model
{
    protected $table = 'recomendaciones';

    protected $fillable = [
        'consulta_id',
        'medico_id',
        'posicion',
        'puntuacion_ia',
        'justificacion_ia',
        'prompt_usado',
        'modelo_ia',
        'seleccionado',
    ];

    protected $casts = [
        'puntuacion_ia' => 'decimal:2',
        'seleccionado'  => 'boolean',
    ];

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }
}
