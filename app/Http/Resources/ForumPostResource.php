<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForumPostResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'tag' => $this->tag,
            'tags' => $this->tags,
            'votes' => $this->votes,
            'author' => $this->author,
            'date' => $this->date,
            'replies' => $this->replies,
        ];
    }
}
