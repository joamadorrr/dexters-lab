<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'experiment_id' => $this->experiment_id,
            'data' => $this->data,
            'created_at' => $this->created_at->format('d M Y'),
            'updated_at' => $this->updated_at->diffForHumans()
        ];
    }
}
