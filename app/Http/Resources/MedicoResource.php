<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'nombre'               => $this->user?->name,
            'especialidad'         => $this->especialidad?->nombre,
            'area_medica'          => $this->especialidad?->area_medica,
            'calificacion_promedio'=> $this->calificacion_promedio,
            'total_consultas'      => $this->total_consultas,
            'bio'                  => $this->bio,
            'foto_url'             => $this->foto_url,
            'disponible'           => $this->disponible,
        ];
    }
}
