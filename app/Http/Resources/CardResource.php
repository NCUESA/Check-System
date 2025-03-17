<?php

namespace App\Http\Resources;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id, 
            "inner_code" => $this->inner_code, 
            "person_id" => $this->person_id, 
            "status" => $this->status, 
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at, 
            "person" => Person::find($this->person_id),
        ];
    }
}
