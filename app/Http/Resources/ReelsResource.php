<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReelsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->blog_articles_id,
            'title' => $this->blog_articles_title,
            'image' => $this->blog_articles_image,
            'media_file' => $this->media_file,
            'reels_favorite' => $this->reels_favorite,
            'link_url' => $this->link_url,
        ];
    }
}
