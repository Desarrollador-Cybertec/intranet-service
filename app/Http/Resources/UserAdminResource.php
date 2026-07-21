<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ficha de usuario para la administración (superset de UserProfileResource):
 * añade el estado de la cuenta y el vínculo con Súmate.
 */
class UserAdminResource extends JsonResource
{
    /**
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
            'active' => (bool) $this->active,
            'profileCompleted' => $this->isProfileComplete(),
            'missingFields' => $this->missingProfileFields(),
            'sumateParticipantId' => $this->whenLoaded(
                'sumateParticipant',
                fn () => $this->sumateParticipant?->id,
            ),
        ];
    }
}
