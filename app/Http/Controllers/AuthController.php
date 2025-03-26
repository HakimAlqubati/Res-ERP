<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Mail\MailableOtp;
use App\Models\EmailOtp;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login_old(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = $request->user();
            $token = $user->createToken('MyApp')->accessToken;

            return response()->json([
                'token' => $token,
                'user' => UserResource::make($user)
            ]);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function login(Request $request)
    {
        $loginMethod = Setting::where('key', 'login_method')->value('value') ?? 'email';
        $authType = Setting::where('key', 'login_auth_type')->value('value') ?? 'password';

        if ($authType === 'otp' && $loginMethod == 'email') {
            return $this->sendOtp($request);
        }
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            $loginMethod => $request->input('username'),
            'password' => $request->input('password'),
        ];

        if (Auth::attempt($credentials)) {
            $user = $request->user();
            $token = $user->createToken('MyApp')->accessToken;

            return response()->json([
                'token' => $token,
                'user' => UserResource::make($user),
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
    public function getCurrnetUser(Request $request)
    {
        return UserResource::make($request->user());
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['username' => 'required|email']);

        $email = $request->input('username');
        $user = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $otp = rand(100000, 999999);

        EmailOtp::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
        ]);

        Mail::to($email)->send(new MailableOtp($otp, $email));


        return response()->json(['message' => 'OTP sent to your email']);
    }

    public function loginWithOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        $otpRecord = EmailOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->latest()
            ->first();
        if (!$otpRecord || $otpRecord->isExpired()) {
            return response()->json(['error' => 'Invalid or expired OTP'], 401);
        }

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // حذف الـ OTP بعد الاستخدام
        $otpRecord->delete();

        // إصدار التوكن وتسجيل الدخول
        $token = $user->createToken('MyApp')->accessToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }
}
