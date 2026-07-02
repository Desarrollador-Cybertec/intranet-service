<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'image' => $this->image,
            'initials' => $this->initials,
            'color' => $this->color,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
