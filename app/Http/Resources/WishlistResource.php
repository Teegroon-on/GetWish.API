<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $response = parent::toArray($request);
        $response['theme'] = new WishlistThemeResource($this->theme);
        $response['friends'] = UserResource::collection($this->friends);
        $response['wishes'] = WishShortResource::collection($this->wishes);
        return $response;
    }
}
