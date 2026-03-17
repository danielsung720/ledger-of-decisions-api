<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

/**
 * Authentication endpoints for register/login/verification/password flows.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    #[OA\Post(
        path: '/register',
        summary: '註冊帳號',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 201, description: '註冊成功'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->toDto());

        return response()->json($result->toArray(), $result->statusCode);
    }

    #[OA\Post(
        path: '/verify-email',
        summary: '驗證 Email',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: '驗證成功'),
            new OA\Response(response: 404, description: '找不到使用者'),
            new OA\Response(response: 422, description: '驗證失敗'),
            new OA\Response(response: 429, description: '嘗試次數過多'),
        ]
    )]
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $result = $this->authService->verifyEmail($request->toDto());

        return response()->json($result->toArray(), $result->statusCode);
    }

    #[OA\Post(
        path: '/resend-verification',
        summary: '重送驗證碼',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: '已受理'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $result = $this->authService->resendVerification($request->toDto());

        return response()->json($result->toArray(), $result->statusCode);
    }

    #[OA\Post(
        path: '/login',
        summary: '登入',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: '登入成功'),
            new OA\Response(response: 401, description: '帳密錯誤'),
            new OA\Response(response: 403, description: 'Email 未驗證'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->toDto());

        if ($result->success && isset($result->data['user']['id'])) {
            Auth::guard('web')->loginUsingId((int) $result->data['user']['id']);
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
        }

        return response()->json($result->toArray(), $result->statusCode);
    }

    #[OA\Post(
        path: '/logout',
        summary: '登出',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: '登出成功'),
            new OA\Response(response: 401, description: '未授權'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $result = $this->authService->logout();

        return response()->json($result->toArray(), $result->statusCode);
    }

    #[OA\Get(
        path: '/user',
        summary: '取得目前使用者',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 401, description: '未授權'),
        ]
    )]
    public function user(Request $request): JsonResponse
    {
        $result = $this->authService->user($request->user());

        return response()->json($result->toArray(), $result->statusCode);
    }

    #[OA\Post(
        path: '/forgot-password',
        summary: '送出重設密碼驗證碼',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: '已受理'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->forgotPassword($request->toDto());

        return response()->json($result->toArray(), $result->statusCode);
    }

    #[OA\Post(
        path: '/reset-password',
        summary: '重設密碼',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: '重設成功'),
            new OA\Response(response: 404, description: '找不到使用者'),
            new OA\Response(response: 422, description: '驗證失敗'),
            new OA\Response(response: 429, description: '嘗試次數過多'),
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->resetPassword($request->toDto());

        return response()->json($result->toArray(), $result->statusCode);
    }

    #[OA\Put(
        path: '/user/password',
        summary: '更新目前使用者密碼',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: '更新成功'),
            new OA\Response(response: 401, description: '未授權'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $result = $this->authService->updatePassword($request->user(), $request->toDto());

        return response()->json($result->toArray(), $result->statusCode);
    }
}
