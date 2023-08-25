<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryBoardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'category_id' => $this->category_board_id,
            'name' => $this->category_board_name,
            'image_url' => $this->category_board_image,
            'category_board_id_parent' => $this->category_board_id_parent,
            'subcategories' => '',
        ];
    }
}
