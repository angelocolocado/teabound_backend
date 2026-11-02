<?php

namespace App\Http\Resources\Menu;

use App\Enum\MenuStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => str($this->name)->title(),
            'category' => $this->whenLoaded('category', function () {
                return str($this->category->name)->title();
            }),
            'price' => $this->price,
            'notes' => $this?->notes,
            'status' => $this->status?->name ?? MenuStatusEnum::inactive->name,
            'menu_contents' => MenuContentResource::collection($this->whenLoaded('menuContents')),
            'menu_options' => MenuOptionResource::collection($this->whenLoaded('menuOptions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
