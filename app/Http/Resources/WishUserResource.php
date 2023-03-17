<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WishUserResource extends JsonResource
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
        $response['user'] = [
            'id' => $this->wishlist->user->id,
            'avatar' => $this->wishlist->user->avatar(),
        ];
        return $response;
    }
}
