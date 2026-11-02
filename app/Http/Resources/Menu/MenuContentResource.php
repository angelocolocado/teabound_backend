<?php

namespace App\Http\Resources\Menu;

use App\Http\Resources\Inventory\InventoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuContentResource extends JsonResource
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
            'name' => str($this?->name)->title() ?? "No Title",
            'stock_to_deduct_in_pcs' => $this->stock_to_deduct_in_pcs,
            'inventory' => new InventoryResource($this->whenLoaded('inventory')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
