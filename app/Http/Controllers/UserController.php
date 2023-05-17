<?php

namespace App\Http\Controllers;

use App\Enums\FriendshipStatus;
use App\Http\Requests\User\UserAvailabilityRequest;
use App\Http\Requests\User\UserBlockRequest;
use App\Http\Requests\User\UserEditPhoneRequest;
use App\Http\Requests\User\UserSearchRequest;
use App\Http\Requests\User\UserSendCodeRequest;
use App\Http\Requests\User\UserUpdateAvatarRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\UserFriendResource;
use App\Http\Resources\UserResource;
use App\Models\SmsCode;
use App\Models\User;
use App\Providers\SmsAeroApi\SmsAeroApi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserController extends Controller
{
    /**
     * Get auth user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResource
     */
    public function getMe(Request $request) {
        return new UserResource($request->user());
    }

    /**
     * Get auth user by id.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResource
     */
    public function get(Request $request, $id) {
        $user = User::where('id', '=', (int)$id)->first();
        if (is_null($user)) {
            return response()->json('User not found', 404);
        }
        return new UserFriendResource($user);
    }

/**
     * Get auth user by username.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResource
     */
    public function getByUsername(Request $request, $id) {
        $user = User::where('username', '=', $id)->first();
        if (is_null($user)) {
            return response()->json('User not found', 404);
        }
        return new UserResource($user);
    }

    /**
     * Update auth user.
     *
     * @param  App\Http\Requests\User\UserUpdateRequest  $request
     * @param string $id
     * @return JsonResource
     */
    public function update(UserUpdateRequest $request, string $id) {
        $user = $request->user();
        $id = intval($id);
        if (!$id || $user->id !== $id) {
            return response()->json('', 403);
        }
        if (!is_null($request->username)) {
            $findUsers = User::where('username', mb_strtolower($request->username))->first();
            if ($findUsers && $findUsers->id !== $user->id) {
                return response()->json('User with this username already exists', 461);
            }
            $user->username = mb_strtolower($request->username);
        }
        if (!is_null($request->firstname)) {
            $user->firstname = $request->firstname;
        } else {
            $user->firstname = null;
        }
        if (!is_null($request->secondname)) {
            $user->secondname = $request->secondname;
        } else {
            $user->secondname = null;
        }
        if (!is_null($request->birthdate)) {
            $user->birthdate = $request->birthdate;
        }
        if (!is_null($request->private)) {
            $user->private = $request->private;
        }
        $user->save();
        return new UserResource($user);
    }

    /**
     * Search the user.
     *
     * @param  App\Http\Requests\User\UserSearchRequest  $request
     * @param string $id
     * @return JsonResource
     */
    public function search(UserSearchRequest $request) {
        $user = $request->user();
        if (is_null($request->skip)) {
            $request->skip = 0;
        }
        if (is_null($request->take)) {
            $request->take = 20;
        }
        $findUsers = User::whereDoesntHave('friendships', function (Builder $query) use ($user) {
                $query->where('user_friend_id', '=', $user->id);
            })
            ->whereDoesntHave('reverseFriendships', function (Builder $query) use ($user) {
                $query->where('user_id', '=', $user->id);
            })
            ->where('username', 'like', '%'.mb_strtolower($request->username).'%')
            ->where('id', '!=', $request->user()->id)
            ->skip((int)$request->skip)
            ->limit((int)$request->take)
            ->orderBy('id')
            ->get();
        return UserResource::collection($findUsers);
    }

    /**
     * Update user avatar.
     *
     * @param  App\Http\Requests\User\UserUpdateAvatarRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function updateAvatar(UserUpdateAvatarRequest $request, string $id) {
        $user = $request->user();
        $id = intval($id);
        if (!$id || $user->id !== $id) {
            return response()->json('', 403);
        }
        if (is_null($request->avatar)) {
            $user->deleteAvatar();
        } else {
            $avatar = $user->setAvatarFromBase64($request->avatar);
            return $avatar;
            if (is_null($avatar)) {
                return response()->json('Invalid image format', 462);
            }
        }
        return new UserResource($user);
    }

    /**
     * Check availability of username.
     *
     * @param  App\Http\Requests\User\UserAvailabilityRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function availability(UserAvailabilityRequest $request) {
        $findUsers = User::where('username', mb_strtolower($request->username))->first();
        if ($findUsers) {
            return response()->json('User with this username already exists', 461);
        }
        return response()->json('', 200);
    }

    /**
     * Edit phone of auth user.
     *
     * @param  App\Http\Requests\User\UserUpdateRequest  $request
     * @param string $id
     * @return JsonResource
     */
    public function updatePhone(UserEditPhoneRequest $request) {
        $user = $request->user();
        $smsCode = SmsCode::where('phone', $request->phone)
            ->where('code', $request->code)
            ->where('used', false)
            ->orderBy('created_at', 'desc')
            ->first();
        if (!$smsCode) {
            return response()->json('SmsCode not exists', 462);
        }

        if (now()->diffInSeconds($smsCode->created_at) > 60) {
            return response()->json('SmsCode expired', 461);
        }

        $user->phone = $request->phone;
        $user->save();
        return new UserResource($user);
    }

    /**
     * Send code for update phone auth user.
     *
     * @param  App\Http\Requests\User\UserSendCodeRequest  $request
     * @return JsonResource
     */
    public function sendCode(UserSendCodeRequest $request, SmsAeroApi $sms) {
        $findUser = User::where('phone', $request->phone)->first();
        if ($findUser) {
            return response()->json('This phone number is already in use by another user', 461);
        }
        $validDate = now()->subMinute();
        $smsCode = SmsCode::where('phone', $request->phone)
            ->where('used', false)
            ->where('created_at', '>=', $validDate)
            ->first();
        if ($smsCode) {
            return response()->json('Sms code sent to this phone number still active', 460);
        }
        $generatedCode = $sms->generateSmsCode();
        $smsCode = new SmsCode();
        $smsCode->phone = $request->phone;
        if (config('app.debug')) {
            $smsCode->code = '1234';
        } else {
            $smsCode->code = $generatedCode;
            $sms->send($request->phone, 'Ваш код для входа: '.$generatedCode);
        }
        $smsCode->save();
        return response()->json('', 200);
    }

    /**
     * Delete auth user.
     *
     * @param  App\Http\Requests\User\UserUpdateRequest  $request
     * @param string $id
     * @return JsonResource
     */
    public function delete(UserUpdateRequest $request, string $id) {
        $user = $request->user();
        $id = intval($id);
        if (!$id || $user->id !== $id) {
            return response()->json('', 403);
        }
        $user->tokens()->delete();
        $user->delete();
        return new UserResource($user);
    }

    /**
     * Block user.
     *
     * @param  App\Http\Requests\User\UserUpdateRequest  $request
     * @param string $id
     * @return JsonResource
     */
    public function block(UserBlockRequest $request) {
        $user = $request->user();
        $findUser = User::where('id', (int)$request->user_id)->first();
        if (!$findUser) {
            return response()->json('User not exists', 461);
        }
        $user->friendships()->detach($findUser);
        $user->friendships()->attach($findUser, [
            'status' => FriendshipStatus::BLOCKED,
        ]);
        return new UserResource($findUser);
    }

    /**
     * Unblock user.
     *
     * @param  App\Http\Requests\User\UserUpdateRequest  $request
     * @param string $id
     * @return JsonResource
     */
    public function unblock(UserBlockRequest $request) {
        $user = $request->user();
        $findUser = User::where('id', (int)$request->user_id)->first();
        if (!$findUser) {
            return response()->json('User not exists', 461);
        }
        $user->friendships()->detach($findUser);
        return new UserResource($findUser);
    }

    /**
     * Logout user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Illuminate\Http\JsonResponse
     */
    public function logout(Request $request) {
        $user = $request->user();
        $user->deleteRefreshToken();
        $user->tokens()->delete();
        $user->save();
        return response()->json('');
    }
}
