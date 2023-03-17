<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wish\WishCreateRequest;
use App\Http\Requests\Wish\WishGetReserved;
use App\Http\Requests\Wish\WishReservationRequest;
use App\Http\Requests\Wish\WishUpdateRequest;
use App\Http\Resources\WishResource;
use App\Http\Resources\WishUserResource;
use App\Models\User;
use App\Models\Wish;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishController extends Controller
{
    /**
     * Get my wishlist.
     *
     * @param  App\Http\Requests\Wishlist\WishlistGetFilterRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function get(Request $request, $id) {
        $user = $request->user();
        $wish = Wish::find(intval($id));
        if (is_null($wish)) {
            return response()->json('Not found', 404);
        }
        if (
            $wish->wishlist->user->id !== $user->id && 
            is_null($wish->wishlist->friends()->where('user_id', $user->id)->first()) &&
            $wish->wishlist->private
        ) {
            return response()->json('Forbidden', 403);
        }
        return new WishResource($wish);
    }

    /**
     * Get wishes reserved by the user
     *
     * @param  App\Http\Requests\Wish\WishReservationRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function getReserved(WishGetReserved $request) {
        $user = $request->user();
        $take = intval($request->input('take', '20'));
        $skip = intval($request->input('skip', '0'));
        $findWishes = Wish::where('user_id', $user->id)
            ->limit($take)
            ->skip($skip)
            ->get();
        return WishUserResource::collection($findWishes);
    }

    /**
     * Reserve wish.
     *
     * @param  App\Http\Requests\Wish\WishReservationRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function reservating(WishReservationRequest $request, $id) {
        $user = $request->user();
        $wish = Wish::find(intval($id));
        if (is_null($wish)) {
            return response()->json('Wish not found', 404);
        }
        if (!is_null($wish->reservation)) {
            return response()->json('Wish already reserved', 461);
        }
        if ($wish->wishlist->private) {
            $findUserFriend = $wish->wishlist->friends()->where('user_id', $user->id)->first();
            if (is_null($findUserFriend)) {
                return response()->json('Forbidden', 403);
            }
        }
        $reservationValues = $this->numberOfReservations($user, $wish->wishlist->user);
        if (intval($reservationValues) >= 3 || is_null($reservationValues)) {
            return response()->json('Maximum numbers of reservations for this friend', 462);
        }
        $wish->reservation()->associate($user);
        $wish->is_anon = $request->anon;
        $wish->save();
        return new WishResource($wish);
    }

    /**
     * Reserve wish.
     *
     * @param  Illuminate\Http\Request  $request
     * @return JsonResponse|JsonResource
     */
    public function deleteReservating(Request $request, $id) {
        $user = $request->user();
        $wish = Wish::find(intval($id));
        if (is_null($wish)) {
            return response()->json('Wish not found', 404);
        }
        if (is_null($wish->reservation)) {
            return response()->json('Wish is not reserved', 461);
        }
        if ($wish->reservation->id !== $user->id) {
            return response()->json('Forbidden', 403);
        }
        $wish->reservation()->dissociate();
        $wish->is_anon = false;
        $wish->save();
        return new WishResource($wish);
    }

    /**
     * Create wish.
     *
     * @param  App\Http\Requests\Wish\WishCreateRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function create(WishCreateRequest $request) {
        $user = $request->user();
        $wishlist = Wishlist::find(intval($request->wishlist));
        if (is_null($wishlist)) {
            return response()->json('Wishlist not found', 461);
        }
        if ($wishlist->user->id !== $user->id) {
            return response()->json('Forbidden', 403);
        }
        $wish = new Wish();
        $wish->name = $request->name;
        $wish->link = $request->link;
        $wish->is_anon = true;
        $wish->description = $request->description;
        $wish->save();
        $wish->wishlist()->associate($wishlist);
        if (is_array($request->images)){
            foreach ($request->images as $image) {
                $createImage = $wish->addImageFromBase64($image);
                if (is_null($createImage)) {
                    return response()->json('Image cannot be added', 462);
                }
            }
        }
        $wish->save();
        return new WishResource($wish);
    }

    /**
     * Update wish.
     *
     * @param  App\Http\Requests\Wish\WishCreateRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function update(WishUpdateRequest $request, $id) {
        $user = $request->user();
        $wish = Wish::find(intval($id));
        if (is_null($wish)) {
            return response()->json('Not found', 404);
        }
        if ($wish->wishlist->user->id !== $user->id) {
            return response()->json('Forbidden', 403);
        }
        if (!is_null($request->name)) {
            $wish->name = $request->name;
        }
        $wish->link = $request->link;
        $wish->description = $request->description;
        if (!is_null($request->wishlist)) {
            $wishlist = Wishlist::find(intval($request->wishlist));
            if (is_null($wishlist)) {
                return response()->json('Wishlist not found', 461);
            }
            $wish->wishlist()->associate($wishlist);
        }
        $currentImages = $wish->imagesFull();
        if (!is_null($request->deleteImages)) {
            if (!is_null($request->addImages)) {
                if (count($currentImages) + count($request->addImages) - count($request->deleteImages) > 5) {
                    return response()->json('Count of images cannot be more than 5', 464);
                }
            } else {
                if (count($currentImages) - count($request->deleteImages) <= 0) {
                    return response()->json('Images cannot be empty', 465);
                }
            }
            foreach ($request->deleteImages as $image) {
                if ($image < count($currentImages)) {
                    try {
                        $currentImages[$image]->delete();
                    } catch (\Throwable $th) {
                        return response()->json('Cannot delete image', 466);
                    }
                }
            }
            if (!is_null($request->addImages)) {
                foreach ($request->addImages as $image) {
                    $result = $wish->addImageFromBase64($image);
                    if (is_null($result)) {
                        return response()->json('Cannot add image', 467);
                    }
                }
            }
        } else {
            if (!is_null($request->addImages)) {
                if (count($currentImages) + count($request->addImages) > 5) {
                    return response()->json('Count of images cannot be more than 5', 466);
                }
                foreach ($request->addImages as $image) {
                    $result = $wish->addImageFromBase64($image);
                    if (is_null($result)) {
                        return response()->json('Cannot add image', 467);
                    }
                }
            }
        }
        $wish->save();
        return new WishResource($wish);
    }

    /**
     * Delete wish.
     *
     * @param  Illuminate\Http\Request  $request
     * @return JsonResponse|JsonResource
     */
    public function delete(Request $request, $id) {
        $user = $request->user();
        $wish = Wish::find(intval($id));
        if (is_null($wish)) {
            return response()->json('Not found', 404);
        }
        if ($wish->wishlist->user->id !== $user->id) {
            return response()->json('Forbidden', 403);
        }
        $wish->delete();
        return new WishResource($wish);
    }

    private function numberOfReservations($user, $findUser) {
        return Wish::where('user_id', $user->id)->whereHas('wishlist', function($q) use($findUser) {
            return $q->where('user_id', $findUser->id)->where('is_archive', false);
        })->count();
    }
}
