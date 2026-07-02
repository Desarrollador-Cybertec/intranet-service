<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdeaResource extends JsonResource
{
    /**
     * Para ideas anónimas, `author` se expone como "Anónimo".
     * Un admin recibe además `realAuthor` (auditoría) — nunca un usuario normal.
     *
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description,
            'anonymous' => $this->anonymous,
            'votes' => $this->votes,
            'date' => $this->date,
            'author' => $this->anonymous ? 'Anónimo' : $this->author,
        ];

        if ($request->user()?->isAdmin()) {
            $data['status'] = $this->status;
            $data['realAuthor'] = $this->author;
            $data['authorId'] = $this->author_id;
        }

        return $data;
    }
}
