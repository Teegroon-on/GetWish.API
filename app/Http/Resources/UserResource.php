<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserResource extends JsonResource
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
        $response['avatar'] = $this->avatar();
        $response['friends'] = $this->friends();
        $response['posts'] = $this->countOfPosts();
        $response['wishes'] = $this->countOfWishes(Auth::user());
        return $response;
    }
}
