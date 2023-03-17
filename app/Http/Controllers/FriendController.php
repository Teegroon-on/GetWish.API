<?php

namespace App\Http\Controllers;

use App\Enums\FriendshipStatus;
use App\Http\Requests\Friend\FriendRequestCreateRequest;
use App\Http\Requests\Friend\FriendSearchRequest;
use App\Http\Requests\Friend\FriendRequestSearchRequest;
use App\Http\Resources\FriendResource;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    /**
     * Search the friends.
     *
     * @param  App\Http\Requests\Friend\FriendSearchRequest  $request
     * @return JsonResource
     */
    public function search(FriendSearchRequest $request) {
        $user = $request->user();
        if (is_null($request->skip)) {
            $request->skip = 0;
        }
        if (is_null($request->take)) {
            $request->take = 20;
        }
        $findUsers = [];
        if (is_null($request->username)) {
            $findUsers = $user->friendships()
            ->wherePivot('status', '=', FriendshipStatus::ACCEPTED)
            ->skip((int)$request->skip)
            ->limit((int)$request->take)
            ->orderBy('id')
            ->get();
        } else {
            $findUsers = $user->friendships()
            ->wherePivot('status', '=', FriendshipStatus::ACCEPTED)
            ->where('username', 'like', mb_strtolower($request->username).'%')
            ->skip((int)$request->skip)
            ->limit((int)$request->take)
            ->orderBy('id')
            ->get();
        }
        return FriendResource::collection($findUsers);
    }

    /**
     * Delete the friend.
     *
     * @param  Illuminate\Http\Request  $request
     * @return JsonResource
     */
    public function delete(FriendRequestCreateRequest $request) {
        $user = $request->user();
        $friend = $user->friendships()
            ->find((int)$request->user_id);
        if (!$friend) {
            return response()->json('Friend not exists', 461);
        }
        $friend->friendships()->detach($user);
        $user->friendships()->detach($friend);
        return new FriendResource($friend);
    }

    /**
     * Search the incoming friend requests.
     *
     * @param  App\Http\Requests\Friend\FriendRequestSearchRequest  $request
     * @return JsonResource
     */
    public function searchIncomingRequests(FriendRequestSearchRequest $request) {
        $user = $request->user();
        if (is_null($request->skip)) {
            $request->skip = 0;
        }
        if (is_null($request->take)) {
            $request->take = 20;
        }
        $findFriendRequests = null;
        if (is_null($request->username)) {
            $findFriendRequests = $user->reverseFriendships()
                ->wherePivot('status', '=', FriendshipStatus::PENDING)
                ->limit((int)$request->take)
                ->skip((int)$request->skip)
                ->orderBy('id')
                ->get();
        } else {
            $findFriendRequests = $user->reverseFriendships()
                ->wherePivot('status', '=', FriendshipStatus::PENDING)
                ->where('username', 'like', '%'.mb_strtolower($request->username).'%')
                ->limit((int)$request->take)
                ->skip((int)$request->skip)
                ->orderBy('id')
                ->get();
        }
        return FriendResource::collection($findFriendRequests);
    }

    /**
     * Search the outgoing friend requests.
     *
     * @param  App\Http\Requests\Friend\FriendRequestSearchRequest  $request
     * @return JsonResource
     */
    public function searchOutgoingRequests(FriendRequestSearchRequest $request) {
        $user = $request->user();
        if (is_null($request->skip)) {
            $request->skip = 0;
        }
        if (is_null($request->take)) {
            $request->take = 20;
        }
        $findFriendRequests = [];
        if (is_null($request->username)) {
            $findFriendRequests = $user->friendships()
                ->wherePivot('status', '=', FriendshipStatus::PENDING)
                ->limit((int)$request->take)
                ->skip((int)$request->skip)
                ->orderBy('id')
                ->get();
        } else {
            $findFriendRequests = $user->friendships()
                ->wherePivot('status', '=', FriendshipStatus::PENDING)
                ->where('username', 'like', '%'.mb_strtolower($request->username).'%')
                ->limit((int)$request->take)
                ->skip((int)$request->skip)
                ->orderBy('id')
                ->get();
        }
        return FriendResource::collection($findFriendRequests);
    }

    /**
     * Create request to friend.
     *
     * @param  App\Http\Requests\Friend\FriendRequestCreateRequest  $request
     * @return JsonResource
     */
    public function createRequest(FriendRequestCreateRequest $request) {
        $user = $request->user();
        if ((int)$request->user_id === $user->id) {
            return response()->json('Cannot send a friend request to yourself', 462);
        }
        $findUser = User::where('id', (int)$request->user_id)->first();
        if (!$findUser) {
            return response()->json('User not exists', 463);
        }
        $friendship = $user->friendships()
            ->find($findUser->id);
        if ($friendship) {
            return response()->json('FriendRequest already exists', 461);
        }

        $user->friendships()->attach($findUser);
        $user->save();

        return new FriendResource($user->friendships()->find($findUser->id));
    }

    /**
     * Accept request to friend.
     *
     * @param  Illuminate\Http\Request  $request
     * @param string $id
     * @return JsonResource
     */
    public function acceptRequest(FriendRequestCreateRequest $request) {
        $user = $request->user();
        $findUser = User::where('id', (int)$request->user_id)->first();
        if (!$findUser) {
            return response()->json('User not exists', 463);
        }
        $friendship = $user->reverseFriendships()
            ->find($findUser->id);
        if (!$friendship) {
            return response()->json('Friend request not exists', 461);
        }
        if ($friendship->status === FriendshipStatus::BLOCKED) {
            return response()->json('Cannot accepted friend request', 462);
        }
        if ($friendship->status === FriendshipStatus::ACCEPTED) {
            return response()->json('Friend request already accepted', 463);
        }
        $findUser->friendships()->detach($user->id);
        $findUser->friendships()->attach($user->id, [
            'status' => FriendshipStatus::ACCEPTED
        ]);
        $user->friendships()->detach($findUser->id);
        $user->friendships()->attach($findUser->id, [
            'status' => FriendshipStatus::ACCEPTED,
        ]);
        return new FriendResource($friendship);
    }

    /**
     * Cancel request to friend.
     *
     * @param  Illuminate\Http\Request  $request
     * @param string $id
     * @return JsonResource
     */
    public function cancelRequest(FriendRequestCreateRequest $request) {
        $user = $request->user();
        $findUser = User::where('id', (int)$request->user_id)->first();
        if (!$findUser) {
            return response()->json('User not exists', 463);
        }
        $friendship = $user->reverseFriendships()
            ->find($findUser->id);
        if (!$friendship) {
            return response()->json('Friend request not exists', 461);
        }
        if ($friendship->status === FriendshipStatus::BLOCKED) {
            return response()->json('Cannot accepted friend request', 462);
        }
        if ($friendship->status === FriendshipStatus::ACCEPTED) {
            return response()->json('Friend request already accepted', 463);
        }
        $findUser->friendships()->detach($user);
        $user->friendships()->detach($findUser);
        return new FriendResource($friendship);
    }

    /**
     * Cancel outgoing request to friend.
     *
     * @param  Illuminate\Http\Request  $request
     * @param string $id
     * @return JsonResource
     */
    public function cancelOutgoingRequest(FriendRequestCreateRequest $request) {
        $user = $request->user();
        $findUser = User::where('id', (int)$request->user_id)->first();
        if (!$findUser) {
            return response()->json('User not exists', 463);
        }
        $friendship = $user->friendships()
            ->find($findUser->id);
        if (!$friendship) {
            return response()->json('Friend request not exists', 461);
        }
        if ($friendship->status === FriendshipStatus::BLOCKED) {
            return response()->json('Cannot accepted friend request', 462);
        }
        if ($friendship->status === FriendshipStatus::ACCEPTED) {
            return response()->json('Friend request already accepted', 463);
        }
        $user->friendships()->detach($findUser);
        return new FriendResource($friendship);
    }

    public function numberOfReservations(Request $request, $id) {
        $user = $request->user();
        if (intval($id) === 0) {
            return response()->json('User id must be a number');
        }
        $findUser = User::where('id', $id)->first();
        if (is_null($findUser)) {
            return response()->json('User not exists', 461);
        }
        $reservations = Wish::where('user_id', $user->id)->whereHas('wishlist', function($q) use($findUser) {
            return $q->where('user_id', $findUser->id)->where('is_archive', false);
        })->count();
        return [
            'data' => [
                'count' => $reservations,
            ],
        ];
    }
}
