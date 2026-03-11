<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPreferencesRequest;
use App\Services\UserPreferencesService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UserPreferencesController extends Controller
{
    public function __construct(
        private readonly UserPreferencesService $preferencesService
    ) {
    }

    /**
     * 取得當前使用者的偏好設定
     */
    #[OA\Get(
        path: '/api/user/preferences',
        summary: '取得使用者偏好設定',
        description: '取得當前登入使用者的偏好設定，包含 UI 主題等',
        tags: ['User Preferences'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得偏好設定',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'ui_theme', type: 'string', example: 'default'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: '未授權',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'Unauthenticated'),
                    ]
                )
            ),
        ]
    )]
    public function show(): JsonResponse
    {
        $userId = auth()->id();
        $preferences = $this->preferencesService->get($userId);

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * 更新當前使用者的偏好設定
     */
    #[OA\Put(
        path: '/api/user/preferences',
        summary: '更新使用者偏好設定',
        description: '更新當前登入使用者的偏好設定',
        tags: ['User Preferences'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ui_theme'],
                properties: [
                    new OA\Property(
                        property: 'ui_theme',
                        type: 'string',
                        enum: ['default', 'code', 'ocean'],
                        example: 'code'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '成功更新偏好設定',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'ui_theme', type: 'string', example: 'code'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: '未授權',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'Unauthenticated'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: '驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '選擇的主題無效'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'ui_theme',
                                    type: 'array',
                                    items: new OA\Items(type: 'string')
                                ),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function update(UpdateUserPreferencesRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $preferences = $this->preferencesService->update(
            $userId,
            $request->toPreferences()
        );

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }
}
