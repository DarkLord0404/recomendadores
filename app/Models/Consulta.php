<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consulta extends Model
{
    protected $fillable = [
        'paciente_id',
        'sintomas',
        'enfermedades_previas',
        'descripcion_adicional',
        'edad',
        'sintomas_json',
    ];

    protected $casts = [
        'sintomas_json' => 'array',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function recomendaciones(): HasMany
    {
        return $this->hasMany(Recomendacion::class);
    }
}
