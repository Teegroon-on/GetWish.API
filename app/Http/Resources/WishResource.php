<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class WishResource extends JsonResource
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
        $response['wishlist'] = $this->wishlist->id;
        $response['images'] = $this->images();
        $reservation = $this->reservation;
        $response['author'] = [
            'id' => $this->wishlist->user->id,
            'username' => $this->wishlist->user->username,
            'avatar' => $this->wishlist->user->avatar(),
        ];
        $response['reservated'] = !is_null($reservation);
        $response['user'] = null;
        if (!is_null($reservation) && (!$response['is_anon'] || Auth::user()->id === $reservation->id)) {
            $response['user'] = new UserResource($reservation);
        }
        unset($response['is_anon']);
        unset($response['media']);
        return $response;
    }
}
