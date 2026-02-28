<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\PasswordResetCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\VerifyResetCodeRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Helpers\ApiResponse;
use App\Mail\PasswordResetCodeMail;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->registerUser($request->validated());
        $user->load('avatarRelation');

        $expiresIn = 3600;
        $token = $user->createToken('Access Token', expiresAt: now()->addSeconds($expiresIn))->plainTextToken;

        $avatarRelation = $user->avatarRelation;
        $avatarPayload = $avatarRelation ? [
            'id' => (string) $avatarRelation->id,
            'name' => $avatarRelation->name,
            'image' => $avatarRelation->image_url,
        ] : null;

        $userData = [
            'id' => (string) $user->id,
            'username' => $user->username ?? '',
            'email' => $user->email,
            'fullName' => $user->name,
            'phone' => $user->phone,
            'avatar' => $avatarPayload,
            'country' => [
                'label' => $user->country_label,
                'code' => $user->country_code,
            ],
            'badge' => null,
        ];

        return ApiResponse::success([
            'token' => $token,
            'expiresIn' => $expiresIn,
            'user' => $userData,
        ], 'تم إنشاء الحساب بنجاح', 201);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->input('email');
        $user = User::where('email', $email)->first();
        if (!$user) {
            return ApiResponse::error('لا يوجد حساب مرتبط بهذا البريد', 404);
        }

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        PasswordResetCode::where('email', $email)->delete();
        PasswordResetCode::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        try {
            Mail::to($email)->send(new PasswordResetCodeMail($code));
        } catch (\Throwable $e) {
            report($e);
        }

        return ApiResponse::success(null, 'تم إرسال رمز التحقق إلى بريدك الإلكتروني', 200);
    }

    public function verifyResetCode(VerifyResetCodeRequest $request): JsonResponse
    {
        $record = PasswordResetCode::where('email', $request->input('email'))
            ->where('code', $request->input('code'))
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return ApiResponse::error('رمز التحقق غير صحيح أو منتهي', 400);
        }

        $resetToken = Str::random(64);
        cache()->put('password_reset:' . $resetToken, $record->email, now()->addMinutes(10));

        return ApiResponse::success([
            'resetToken' => $resetToken,
            'expiresIn' => 600,
        ], null, 200);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $email = $request->input('email');
        $code = $request->input('code');
        $newPassword = $request->input('newPassword');

        $record = PasswordResetCode::where('email', $email)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return ApiResponse::error('رمز التحقق غير صحيح أو منتهي', 400);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return ApiResponse::error('لا يوجد حساب مرتبط بهذا البريد', 404);
        }

        $user->update(['password' => Hash::make($newPassword)]);
        PasswordResetCode::where('email', $email)->delete();

        return ApiResponse::success(null, 'تم تغيير كلمة المرور بنجاح', 200);
    }
}
