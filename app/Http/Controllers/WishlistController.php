<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wishlist\WishlistCreateRequest;
use App\Http\Requests\Wishlist\WishlistGetByUser;
use App\Http\Requests\Wishlist\WishlistGetFilterRequest;
use App\Http\Requests\Wishlist\WishlistUpdateRequest;
use App\Http\Resources\WishlistResource;
use App\Http\Resources\WishlistShortResource;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistTheme;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * Get wishlist.
     *
     * @param  Illuminate\Http\Request  $request
     * @return JsonResponse|JsonResource
     */
    public function get(Request $request, $id) {
        $user = $request->user();
        $wishlist = Wishlist::find(intval($id));
        if (is_null($wishlist)) {
            return response()->json('Wishlist not found', 404);
        }
        if ($wishlist->private) {
            $userInFriendList = $wishlist->friends()->where('user_id', $user->id)->first();
            if (is_null($userInFriendList) && $wishlist->user->id !== $user->id) {
                return response()->json('Forbidden', 403);
            }
        }
        return new WishlistResource($wishlist);
    }

    /**
     * Get my wishlist.
     *
     * @param  Illuminate\Http\Request  $request
     * @return JsonResponse|JsonResource
     */
    public function getMy(Request $request) {
        $user = $request->user();
        return WishlistShortResource::collection($user->wishlists()
            ->where('is_archive', '=', false)
            ->orderBy('id')
            ->get());
    }

    /**
     * Get by user id.
     *
     * @param  App\Http\Requests\Wishlist\WishlistGetByUser  $request
     * @return JsonResponse|JsonResource
     */
    public function getByUser(WishlistGetByUser $request, $id) {
        $user = $request->user();
        $take = intval($request->input('take', '20'));
        $skip = intval($request->input('skip', '0'));
        $findUser = User::find(intval($id));
        if (is_null($findUser)) {
            return response()->json('Not found', 404);
        }
        if ($findUser->private) {
            if (is_null($findUser->friendships()->wherePivot('user_friend_id', $user->id)->first())) {
                return response()->json('Forbidden', 403);
            }
        }
        return WishlistResource::collection(
            $findUser->wishlists()
                ->where('is_archive', false)
                ->where('private', false)
                ->orWhere(function($query) use($user, $findUser) {
                    $query->where('private', true)
                        ->whereHas('friends', function ($q) use($user, $findUser) {
                            $q->where('id', $user->id)
                            ->where('wishlists.user_id', $findUser->id);
                        });
                })
                ->limit($take)
                ->skip($skip)
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * Get filtered my wishlist.
     *
     * @param  App\Http\Requests\Wishlist\WishlistGetFilterRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function filter(WishlistGetFilterRequest $request) {
        $user = $request->user();
        if (is_null($request->take)) {
            $request->take = 5;
        }
        if (is_null($request->skip)) {
            $request->skip = 0;
        }
        if (intval($request->type) === 1) {
            return WishlistResource::collection($user->wishlists()
                ->where('private', '=', false)
                ->where('is_archive', '=', false)
                ->limit((int)$request->take)
                ->skip((int)$request->skip)
                ->orderBy('id')
                ->get());
        } else if (intval($request->type) === 2) {
            return WishlistResource::collection($user->wishlists()
                ->where('private', '=', true)
                ->where('is_archive', '=', false)
                ->limit((int)$request->take)
                ->skip((int)$request->skip)
                ->orderBy('id')
                ->get());
        } else {
            return WishlistResource::collection($user->wishlists()
            ->where('is_archive', '=', true)
            ->limit((int)$request->take)
            ->skip((int)$request->skip)
            ->orderBy('id')
            ->get());
        }
    }

    /**
     * Create wishlist.
     *
     * @param  App\Http\Requests\Wishlist\WishlistCreateRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function create(WishlistCreateRequest $request) {
        $user = $request->user();
        $theme = WishlistTheme::find(intval($request->theme));
        if (is_null($theme)) {
            return response()->json('Wishlist theme not found', 462);
        }
        $wishlist = new Wishlist();
        $wishlist->name = $request->name;
        $wishlist->private = $request->private;
        $wishlist->save();
        $wishlist->user()->associate($user);
        $wishlist->theme()->associate($theme);
        if ($request->private) {
            $friends = [];
            if (count($request->friends) > 0) {
                $findFriends = $user->friendships()->wherePivotIn('user_friend_id', $request->friends)->get();
                if ($findFriends) {
                    foreach ($findFriends as $friendId) {
                        $friends[] = $friendId->id;
                    }
                }
            }
            $wishlist->friends()->sync($friends);
        }
        $wishlist->save();
        return new WishlistResource($wishlist);
    }

    /**
     * Update wishlist.
     *
     * @param  App\Http\Requests\Wishlist\WishlistUpdateRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function update(WishlistUpdateRequest $request, $id) {
        $user = $request->user();
        $wishlist = Wishlist::find(intval($id));
        if (is_null($wishlist)) {
            return response()->json('Wishlist not found', 463);
        }
        if ($request->private) {
            if (is_null($request->friends) || !is_array($request->friends)) {
                return response()->json('Friends list cannot be empty', 461);
            }
            if (count($request->friends) == 0) {
                return response()->json('Friends list cannot be empty', 461);
            }
        }
        $theme = WishlistTheme::find(intval($request->theme));
        if (is_null($theme)) {
            return response()->json('Wishlist theme not found', 462);
        }
        $wishlist->name = $request->name;
        $wishlist->private = $request->private;
        $wishlist->is_archive = $request->is_archive;
        $wishlist->save();
        $wishlist->user()->associate($user);
        $wishlist->theme()->associate($theme);
        if ($request->private) {
            $friends = [];
            $findFriends = $user->friendships()->wherePivotIn('user_friend_id', $request->friends)->get();
            if ($findFriends) {
                foreach ($findFriends as $friendId) {
                    $friends[] = $friendId->id;
                }
            }
            $wishlist->friends()->sync($friends);
        } else {
            $wishlist->friends()->detach();
        }
        $wishlist->save();
        return new WishlistResource($wishlist);
    }

    /**
     * Delete wishlist.
     *
     * @param  Illuminate\Http\Request  $request
     * @return JsonResponse|JsonResource
     */
    public function delete(Request $request, $id) {
        $user = $request->user();
        $wishlist = Wishlist::where('id', intval($id))->first();
        if (is_null($wishlist)) {
            return response()->json('Not found', 404);
        }
        if ($wishlist->user->id !== $user->id) {
            return response()->json('Forbidden', 403);
        }
        $wishlist->delete();
        return $wishlist;
    }
}
