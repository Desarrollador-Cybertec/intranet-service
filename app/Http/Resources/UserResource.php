<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Shape `User` del contrato (id como string).
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
        ];
    }
}
