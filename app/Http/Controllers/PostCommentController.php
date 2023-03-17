<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostComment\PostCommentRequest;
use App\Http\Resources\PostCommentResource;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;

class PostCommentController extends Controller
{
    /**
     * List of comments a post.
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
        $postLike = PostComment::where('post_id', intval($post->id))
            ->limit($take)
            ->skip($skip)
            ->get();
        return PostCommentResource::collection($postLike);
    }

    /**
     * Create post comment.
     *
     * @param  App\Http\Requests\PostComment\PostCommentRequest  $request
     * @param  string  $id
     * @return JsonResponse|JsonResource
     */
    public function create(PostCommentRequest $request, $id) {
        $user = $request->user();
        $post = Post::find(intval($id));
        $postUser = $post->user;
        if (is_null($post)) {
            return response()->json('Post not found', 404);
        }
        if ($postUser->id !== $user->id && !$postUser->isFriend($user->id)) {
            return response()->json('Forbidden', 403);
        }
        $newPostComment = new PostComment();
        $newPostComment->from_friend = $post->user->isFriend($user->id);
        $newPostComment->user()->associate($user);
        $newPostComment->post()->associate($post);
        $newPostComment->text = $request->text;
        $newPostComment->save();
        return new PostCommentResource($newPostComment);
    }

    /**
     * Update post comment.
     *
     * @param  App\Http\Requests\PostComment\PostCommentRequest  $request
     * @param  string  $id
     * @return JsonResponse|JsonResource
     */
    public function update(PostCommentRequest $request, $id) {
        $user = $request->user();
        $postComment = PostComment::find(intval($id));
        if (is_null($postComment)) {
            return response()->json('PostComment not found', 404);
        }
        if ($postComment->user->id !== $user->id) {
            return response()->json('Forbidden', 403);
        }
        $postComment->text = $request->text;
        $postComment->save();
        return new PostCommentResource($postComment);
    }

    /**
     * Delete post comment.
     *
     * @param  Illuminate\Http\Request  $request
     * @param  string  $id
     * @return JsonResponse|JsonResource
     */
    public function delete(Request $request, $id) {
        $user = $request->user();
        $postComment = PostComment::find(intval($id));
        if (is_null($postComment)) {
            return response()->json('PostComment not found', 404);
        }
        if ($postComment->user->id !== $user->id && $postComment->post->user->id !== $user->id) {
            return response()->json('Forbidden', 403);
        }
        $postComment->delete();
        return new PostCommentResource($postComment);
    }
}
