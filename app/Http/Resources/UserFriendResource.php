<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserFriendResource extends JsonResource
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
        $response['is_friend'] = $this->isFriend($request->user()->id);
        $response['has_incoming_friend_request'] = $this->isFriendRequest($request->user()->id);
        $response['has_outgoing_friend_request'] = $request->user()->isFriendRequest($this->id);
        return $response;
    }
}
