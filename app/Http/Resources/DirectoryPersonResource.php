<?php

namespace App\Http\Resources;

use App\Models\DirectoryPerson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Proyección pública de una `DirectoryPerson` (sin datos de gestión). Se conserva
 * exactamente la misma forma que cuando el Directorio era una proyección de
 * `users`, para no romper al frontend.
 *
 * @mixin DirectoryPerson
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
            'color' => $this->color ?: User::colorFrom($this->email ?? $this->name),
            'email' => $this->email,
            'phone' => $this->extension ? "Ext. {$this->extension}" : ($this->phone ?? ''),
        ];
    }
}
