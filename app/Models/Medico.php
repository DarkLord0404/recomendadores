<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medico extends Model
{
    protected $fillable = [
        'user_id',
        'especialidad_id',
        'numero_colegiado',
        'telefono',
        'bio',
        'calificacion_promedio',
        'total_consultas',
        'disponible',
        'foto_url',
    ];

    protected $casts = [
        'disponible'           => 'boolean',
        'calificacion_promedio'=> 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function recomendaciones(): HasMany
    {
        return $this->hasMany(Recomendacion::class);
    }
}
