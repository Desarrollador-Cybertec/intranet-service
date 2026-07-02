<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * `completed` es relativo al usuario autenticado. Se resuelve en el controlador
     * y se inyecta como atributo `completed` en el modelo antes de serializar.
     *
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'icon' => $this->icon,
            'tag' => $this->tag,
            'tagColor' => $this->tag_color,
            'tagBg' => $this->tag_bg,
            'desc' => $this->desc,
            'duration' => $this->duration,
            'modality' => $this->modality,
            'completed' => (bool) ($this->completed ?? false),
        ];
    }
}
