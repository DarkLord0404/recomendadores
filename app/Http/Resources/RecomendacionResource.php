<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecomendacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'posicion'        => $this->posicion,
            'puntuacion_ia'   => $this->puntuacion_ia,
            'justificacion'   => $this->justificacion_ia,
            'modelo_ia'       => $this->modelo_ia,
            'seleccionado'    => $this->seleccionado,
            'medico'          => new MedicoResource($this->whenLoaded('medico')),
        ];
    }
}
