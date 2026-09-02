<?php

namespace App\Http\Resources;

use App\Models\DirectoryPerson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ficha de gestión de una persona del Directorio (GET /api/directory/entries).
 *
 * @mixin DirectoryPerson
 */
class DirectoryPersonAdminResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'area' => $this->area,
            'phone' => $this->phone,
            'extension' => $this->extension,
            'email' => $this->email,
            'image' => $this->photo,
            'initials' => $this->initials,
            'color' => $this->color,
            'userId' => $this->user_id,
            'active' => $this->active,
            'position' => $this->position,
        ];
    }
}
