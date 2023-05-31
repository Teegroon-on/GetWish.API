<?php

namespace App\Http\Controllers;

use App\Enums\FriendshipStatus;
use App\Http\Requests\Post\PostUpdateRequest;
use App\Http\Requests\Post\PostUploadAttachmentRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostUploadAttachmentResource;
use App\Models\Post;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use SoftCreatR\MimeDetector\MimeDetector;

class PostController extends Controller
{
    /**
     * Get post by id.
     *
     * @param  Illuminate\Http\Request  $request
     * @return JsonResponse|JsonResource
     */
    public function list(Request $request) {
        $user = $request->user();
        $take = intval($request->input('take', '30'));
        $skip = intval($request->input('skip', '0'));
        $posts = Post::whereIn('user_id', function($query) use ($user) {
                $query->select('user_friend_id')
                    ->from('friendships')
                    ->where('user_id', $user->id)
                    ->where('status', FriendshipStatus::ACCEPTED);
            })->where('active', true)
            ->limit($take)
            ->skip($skip)
            ->orderBy('id', 'desc')
            ->get();
        foreach ($posts as $post) {
            $post->views = $post->views + 1;
            $post->save();
        }
        return PostResource::collection($posts);
    }

    /**
     * Get post by id.
     *
     * @param  Illuminate\Http\Request  $request
     * @return JsonResponse|JsonResource
     */
    public function get(Request $request, $id) {
        $user = $request->user();
        $post = Post::find(intval($id));
        if (is_null($post)) {
            return response()->json('Not found', 404);
        } else if(!$post->active) {
            return response()->json('Not found', 404);
        }
        return new PostResource($post);
    }

    /**
     * Get posts by user id.
     *
     * @param  Illuminate\Http\Request  $request
     * @return JsonResponse|JsonResource
     */
    public function getByUser(Request $request, $id) {
        $user = $request->user();
        $findUser = User::find(intval($id));
        $take = intval($request->input('take', '30'));
        $skip = intval($request->input('skip', '0'));
        if (is_null($findUser)) {
            return response()->json('User not found', 404);
        }
        if ($findUser->private) {
            if ($findUser->id !== $user->id && !$findUser->isFriend($user->id)) {
                return response()->json('Forbidden', 403);
            }
        }
        $posts = Post::where('user_id', $findUser->id)
            ->where('active', true)
            ->limit($take)
            ->skip($skip)
            ->orderBy('id', 'desc')
            ->get();
        foreach ($posts as $post) {
            $post->views = $post->views + 1;
            $post->save();
        }
        return PostResource::collection($posts);
    }

    /**
     * Upload attachment of post.
     *
     * @param  App\Http\Requests\Post\PostUploadAttachment  $request
     * @return JsonResponse|JsonResource
     */
    public function uploadAttachment(PostUploadAttachmentRequest $request) {
        return response() -> json('asdasd');
        $user = $request->user();
        $id = $request->input('id');
        if (is_null($id) || empty($id)) {
            $newPost = new Post();
            $newPost->user()->associate($user);
            $newPost->save();
            $ext = $this->getFileExtension($request->file('file'));
            if (is_null($ext)) {
                return response()->json('This file has unreadable extension', 462);
            }
            if (is_null($newPost->uploadAttachment('file', $ext))) {
                $newPost->delete();
                return response()->json('This file cannot be added', 461);
            }
            return new PostUploadAttachmentResource($newPost);
        } else {
            $post = Post::find(intval($id));
            if (is_null($post)) {
                return response()->json('Not found', 404);
            }
            $ext = $this->getFileExtension($request->file('file'));
            if (is_null($ext)) {
                return response()->json('This file has unreadable extension', 462);
            }
            if (is_null($post->uploadAttachment('file', $ext))) {
                return response()->json('This file cannot be added', 461);
            }
            return new PostUploadAttachmentResource($post);
        }
    }

    /**
     * Update post.
     *
     * @param  App\Http\Requests\Post\PostUpdateRequest  $request
     * @return JsonResponse|JsonResource|PostResource
     */
    public function update(PostUpdateRequest $request, $id) {
        $user = $request->user();
        $post = Post::find(intval($id));
        if (is_null($post)) {
            return response()->json('Not found', 404);
        }
        if ($post->user->id !== $user->id) {
            return response()->json('Forbidden', 403);
        }
        $post->active = true;
        $post->text = $request->text;
        $post->save();
        return new PostResource($post);
    }

    /**
     * Delete post.
     *
     * @param  Illuminate\Http\Request  $request
     * @return JsonResponse|JsonResource
     */
    public function delete(Request $request, $id) {
        $user = $request->user();
        $post = Post::find(intval($id));
        if (is_null($post)) {
            return response()->json('Not found', 404);
        }
        if ($post->user->id !== $user->id) {
            return response()->json('Forbidden', 403);
        }
        $post->delete();
        return new PostUploadAttachmentResource($post);
    }

    private function getFileExtension($file) {
        try {
            $mimeDetector = new MimeDetector();
            $mimeDetector->setFile($file->getPathName());
            return $mimeDetector->getFileType()['ext'];
        } catch (Exception $e) {
            return null;
        }
    }
}
