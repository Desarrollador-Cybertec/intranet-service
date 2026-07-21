<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Shape `UserProfile` del contrato (User + color, joinedAt, extension?).
     *
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'roleType' => $this->role_type,
            'initials' => $this->initials,
            'email' => $this->email,
            'area' => $this->area,
            'phone' => $this->phone,
            'color' => $this->color,
            'joinedAt' => $this->joined_at?->format('Y-m-d'),
            'birthday' => $this->birthday?->format('Y-m-d'),
            'extension' => $this->extension,
            'photo' => $this->photo,
            'profileCompleted' => $this->isProfileComplete(),
            'missingFields' => $this->missingProfileFields(),
            'canManageRoles' => $this->canManageRoles(),
        ];
    }
}
