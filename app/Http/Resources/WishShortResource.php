<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WishShortResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $response = [];
        $response['id'] = $this->id;
        $response['name'] = $this->name;
        $response['description'] = $this->description;
        $response['link'] = $this->link;
        $response['image'] = $this->mainImage();
        $response['reservated'] = !is_null($this->reservation);
        return $response;
    }
}
