<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostLikeResource;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    /**
     * List of likes a post.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return JsonResource
     */
    public function list(Request $request, $id) {
        $user = $request->user();
        $post = Post::find(intval($id));
        $take = intval($request->input('take', '20'));
        $skip = intval($request->input('skip', '0'));
        if (is_null($post)) {
            return response()->json('Post not found', 404);
        }
        $postLike = PostLike::where('post_id', intval($post->id))
            ->limit($take)
            ->skip($skip)
            ->get();
        return PostLikeResource::collection($postLike);
    }

    /**
     * Like the post.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return JsonResource
     */
    public function like(Request $request, $id) {
        $user = $request->user();
        $post = Post::find(intval($id));
        if (is_null($post)) {
            return response()->json('Post not found', 404);
        }
        $postLike = PostLike::where('user_id', intval($user->id))
            ->where('post_id', intval($post->id))
            ->first();
        if (is_null($postLike)) {
            $newPostLike = new PostLike();
            $newPostLike->from_friend = $post->user->isFriend($user->id);
            $newPostLike->user()->associate($user);
            $newPostLike->post()->associate($post);
            $newPostLike->save();
        }
        return response()->json('', 200);
    }

    /**
     * Like the post.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return JsonResource
     */
    public function unlike(Request $request, $id) {
        $user = $request->user();
        $post = Post::find(intval($id));
        if (is_null($post)) {
            return response()->json('Post not found', 404);
        }
        $postLike = PostLike::where('user_id', intval($user->id))
            ->where('post_id', intval($post->id))
            ->first();
        if (!is_null($postLike)) {
            $postLike->delete();
        }
        return response()->json('', 200);
    }
}
