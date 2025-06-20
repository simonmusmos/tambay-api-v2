<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|regex:/^[0-9]{10,15}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $request->mobile, $otp, now()->addMinutes(5));

        return response()->json([
            'message' => 'OTP sent',
            'otp' => $otp
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|regex:/^[0-9]{10,15}$/',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cachedOtp = Cache::get('otp_' . $request->mobile);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $user = User::where('mobile', $request->mobile)->first();
        $isNewUser = !$user;

        $token = null;
        if ($user) {
            $token = $user->createToken('auth_token')->plainTextToken;
        }

        Cache::forget('otp_' . $request->mobile);

        return response()->json([
            'message' => 'OTP verified successfully',
            'is_new_user' => $isNewUser,
            'token' => $token,
            'user' => $user,
        ]);
    }
}
