<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'tag' => $this->tag,
            'tagBg' => $this->tag_bg,
            'tagColor' => $this->tag_color,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'date' => $this->date,
            'eventDate' => $this->event_date?->format('Y-m-d'),
            'author' => $this->author,
            'imgs' => $this->imgs,
            'body' => $this->body,
        ];
    }
}
