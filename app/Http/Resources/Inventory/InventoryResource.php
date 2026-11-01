<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
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
            'name' => $this->name,
            'type' => $this->type->name,
            'type_code' => $this->type->value,
            'stock_in_pcs' => $this->stock_in_pcs,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
