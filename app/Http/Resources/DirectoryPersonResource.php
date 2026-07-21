<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Proyección pública de un `User` para el Directorio (sin datos sensibles).
 * `image` es la foto de perfil; si es null el front pinta `initials` + `color`.
 *
 * @mixin User
 */
class DirectoryPersonResource extends JsonResource
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
            'image' => $this->photo,
            'initials' => $this->initials ?: User::initialsFrom($this->name),
            'color' => $this->color ?: User::colorFrom($this->email),
            'email' => $this->email,
            'phone' => $this->extension ? "Ext. {$this->extension}" : ($this->phone ?? ''),
        ];
    }
}
