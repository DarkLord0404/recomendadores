<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paciente extends Model
{
    protected $fillable = [
        'user_id',
        'fecha_nacimiento',
        'sexo',
        'alergias',
        'enfermedades_cronicas',
        'medicamentos_actuales',
        'grupo_sanguineo',
        'historial_medico',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }
}
