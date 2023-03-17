<?php

namespace App\Http\Controllers;

use App\Http\Requests\WishlistTheme\WishlistThemeCreateRequest;
use App\Http\Requests\WishlistTheme\WishlistThemeUpdateRequest;
use App\Http\Resources\WishlistThemeResource;
use App\Models\WishlistTheme;
use Illuminate\Http\Request;

class WishlistThemeController extends Controller
{
    /**
     * Get all wishlist themes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResource
     */
    public function getAll(Request $request) {
        $themes = WishlistTheme::orderBy('id', 'asc')->get();
        return WishlistThemeResource::collection($themes);
    }

    /**
     * Create wishlist theme.
     *
     * @param  App\Http\Requests\WishlistTheme\WishlistThemeCreateRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function create(WishlistThemeCreateRequest $request) {
        $theme = new WishlistTheme();
        $theme->symbol = $request->symbol;
        $theme->color = $request->color;
        $theme->save();
        if (!is_null($request->image)) {
            $image = $theme->setImageFromBase64($request->image);
            if (is_null($image)) {
                return response()->json('Invalid image format', 462);
            }
        }
        if (!is_null($request->icon)) {
            $icon = $theme->setIconFromBase64($request->icon);
            if (is_null($icon)) {
                return response()->json('Invalid icon format', 463);
            }
        }
        if (!is_null($request->card)) {
            $card = $theme->setCardFromBase64($request->icon);
            if (is_null($card)) {
                return response()->json('Invalid card format', 464);
            }
        }
        if (!is_null($request->preview)) {
            $preview = $theme->setPreviewFromBase64($request->icon);
            if (is_null($preview)) {
                return response()->json('Invalid preview format', 465);
            }
        }
        return new WishlistThemeResource($theme);
    }

    /**
     * Update wishlist theme.
     *
     * @param  App\Http\Requests\WishlistTheme\WishlistThemeUpdateRequest  $request
     * @return JsonResponse|JsonResource
     */
    public function update(WishlistThemeUpdateRequest $request, string $id) {
        $theme = WishlistTheme::find(intval($id));
        if (is_null($theme)) {
            return response()->json('Theme not found', 461);
        }
        if (!is_null($request->symbol)) {
            $theme->symbol = $request->symbol;
        }
        if (!is_null($request->color)) {
            $theme->color = $request->color;
        }
        $theme->save();
        if (!is_null($request->image)) {
            $image = $theme->setImageFromBase64($request->image);
            if (is_null($image)) {
                return response()->json('Invalid image format', 462);
            }
        }
        if (!is_null($request->icon)) {
            $icon = $theme->setIconFromBase64($request->icon);
            if (is_null($icon)) {
                return response()->json('Invalid icon format', 463);
            }
        }
        if (!is_null($request->card)) {
            $card = $theme->setCardFromBase64($request->card);
            if (is_null($card)) {
                return response()->json('Invalid card format', 464);
            }
        }
        if (!is_null($request->preview)) {
            $preview = $theme->setPreviewFromBase64($request->preview);
            if (is_null($preview)) {
                return response()->json('Invalid preview format', 465);
            }
        }
        return new WishlistThemeResource($theme);
    }
}
