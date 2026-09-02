<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'protected' => (bool) $this->protected,
            'isDefault' => (bool) $this->is_default,
            'isSuperadmin' => $this->slug === Role::SUPERADMIN,
            'position' => $this->position,
            'usersCount' => $this->whenCounted('users'),
            'permissions' => $this->when(
                $this->relationLoaded('permissions'),
                fn () => $this->matrix(),
            ),
        ];
    }
}
