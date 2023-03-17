<?php

namespace App\Http\Resources;

use App\Enums\FriendshipStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PostResource extends JsonResource
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
        $response['attachments'] = $this->attachments();
        $response['user'] = new UserLikeResource($this->user);
        $response['likes'] = [
            'count' => $this->likes()->count(),
            'friends' => PostLikeResource::collection(
                $this->likes()->where('from_friend', true)->limit(3)->get()
            ),
            'liked' => $this->likes()->where('user_id', Auth::user()->id)->count() === 1
        ];
        $response['comments'] = [
            'count' => $this->comments()->count(),
            'friends' => PostCommentResource::collection(
                $this->comments()->where('from_friend', true)->limit(3)->get()
            )
        ];
        unset($response['active']);
        return $response;
    }
}
