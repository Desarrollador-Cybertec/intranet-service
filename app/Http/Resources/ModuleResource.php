<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    /**
     * Shape RhModule/SstModule/SigModule del contrato: `id` es el slug estable.
     *
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'label' => $this->label,
            'icon' => $this->icon,
            'color' => $this->color,
            'bg' => $this->bg,
            'desc' => $this->desc,
        ];
    }
}
