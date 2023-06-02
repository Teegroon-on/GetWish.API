<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\SmsCodeCheckRequest;
use App\Http\Requests\Auth\SmsCodeSendRequest;
use App\Http\Requests\Auth\TokenRefreshRequest;
use App\Models\SmsCode;
use App\Models\User;
use App\Providers\SmsAeroApi\SmsAeroApi;

class AuthController extends Controller
{

    /**
     * Send sms verification code.
     *
     * @param  \App\Http\Requests\Sms\SmsCodeSendRequest  $request
     * @param  \App\Providers\SmsAeroApi\SmsAeroApi  $sms
     *
     */
    public function send(SmsCodeSendRequest $request, SmsAeroApi $sms) {
        $validDate = now()->subMinute();
        $smsCode = SmsCode::where('phone', $request->phone)
            ->where('used', false)
            ->where('created_at', '>=', $validDate)
            ->first();
        if ($smsCode) {
            return response()->json('', 460);
        }
        $generatedCode = $sms->generateSmsCode();
        $smsCode = new SmsCode();
        $smsCode->phone = $request->phone;
        if (config('app.debug')) {
            $smsCode->code = '1234';
        } else {
            $smsCode->code = $generatedCode;
            $aeroLogin = config( 'smsaero.login' );
            $aeroKey = config( 'smsaero.apikey' );
            $sms = 'Ваш код для входа: '.$generatedCode;
            $url = "https://{$aeroLogin}:{$aeroKey}@gate.smsaero.ru/v2/sms/send?number={$request->phone}&text={$sms}&sign=SMS Aero&channel=DIRECT";
            $output = file_get_contents( $url );
            $output = json_decode( $output , 1 );
        }
        $smsCode->save();
        return response()->json('', 200);
    }

    /**
     * Send sms verification code.
     *
     * @param  \App\Http\Requests\Auth\SmsCodeCheckRequest  $request
     *
     */
    public function check(SmsCodeCheckRequest $request) {
        $user = User::where('phone', $request->phone)->first();
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

        if (!$user) {
            $newUser = new User();
            $newUser->phone = $request->phone;
            $refreshToken = $newUser->generateRefreshToken();
            $newUser->save();
            $accessToken = $newUser->createToken('auth');
            $smsCode->used = true;
            $smsCode->save();
            return response()->json([
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $refreshToken,
            ]);
        }
        $refreshToken = $user->generateRefreshToken();
        $user->save();
        $user->tokens()->delete();
        $accessToken = $user->createToken('auth');
        $smsCode->used = true;
        $smsCode->save();
        return response()->json([
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * Send sms verification code.
     *
     * @param  \App\Http\Requests\Auth\TokenRefreshRequest  $request
     *
     */
    public function refresh(TokenRefreshRequest $request) {
        $user = User::where('refresh_token', $request->refresh_token)->first();
        if (!$user) {
            return response()->json('', 401);
        }

        if (!$user->refresh_token_expired_at) {
            return response()->json('', 401);
        }

        if (now()->diffInSeconds($user->refresh_token_expired_at, false) < 0) {
            return response()->json('', 401);
        }

        $refreshToken = $user->generateRefreshToken();
        $user->save();
        $accessToken = $user->createToken('auth');
        return response()->json([
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken,
        ]);
    }
}
