<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Statistics\StatisticsQueryDto;
use App\DTO\Statistics\TrendsStatisticsQueryDto;
use App\Http\Requests\GetIntentsRequest;
use App\Http\Requests\GetSummaryRequest;
use App\Http\Requests\GetTrendsRequest;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use OpenApi\Attributes as OA;

/**
 * Statistics endpoints for intent distribution, summary metrics and trends.
 */
class StatisticsController extends Controller
{
    #[OA\Get(
        path: '/statistics/intents',
        summary: 'Intent 統計',
        description: '查看各決策意圖的出現次數與平均信心程度',
        tags: ['Statistics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', description: '起始日期', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', description: '結束日期', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'preset', in: 'query', description: '預設時間範圍', schema: new OA\Schema(type: 'string', enum: ['today', 'this_week', 'this_month'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'intent', type: 'string', example: 'necessity'),
                                    new OA\Property(property: 'intent_label', type: 'string', example: '必要性'),
                                    new OA\Property(property: 'count', type: 'integer', example: 12),
                                    new OA\Property(property: 'avg_confidence_score', type: 'number', format: 'float', example: 2.17),
                                    new OA\Property(property: 'avg_confidence_level', type: 'string', example: 'medium'),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(
                response: 422,
                description: '驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'validation_error'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: '伺服器錯誤',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'server_error'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function intents(GetIntentsRequest $request, StatisticsService $statisticsService): JsonResponse
    {
        $query = StatisticsQueryDto::forUser(
            (int) auth()->id(),
            $request->toDto()
        );
        $stats = $statisticsService->getIntentsStatistics($query);

        return $this->respondWithStatistics($stats);
    }

    #[OA\Get(
        path: '/statistics/summary',
        summary: '消費總覽',
        description: '取得消費統計總覽，包含分類統計、意圖統計、衝動消費比例',
        tags: ['Statistics'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', description: '起始日期', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', description: '結束日期', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'preset', in: 'query', description: '預設時間範圍', schema: new OA\Schema(type: 'string', enum: ['today', 'this_week', 'this_month'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 5432.5),
                                new OA\Property(property: 'total_count', type: 'integer', example: 25),
                                new OA\Property(
                                    property: 'by_category',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'category', type: 'string', example: 'food'),
                                            new OA\Property(property: 'category_label', type: 'string', example: '飲食'),
                                            new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 1200),
                                            new OA\Property(property: 'count', type: 'integer', example: 8),
                                        ]
                                    )
                                ),
                                new OA\Property(
                                    property: 'by_intent',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'intent', type: 'string', example: 'impulse'),
                                            new OA\Property(property: 'intent_label', type: 'string', example: '衝動'),
                                            new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 980),
                                            new OA\Property(property: 'count', type: 'integer', example: 6),
                                        ]
                                    )
                                ),
                                new OA\Property(property: 'impulse_spending_ratio', type: 'number', format: 'float', example: 24.5),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(
                response: 422,
                description: '驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'validation_error'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: '伺服器錯誤',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'server_error'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function summary(GetSummaryRequest $request, StatisticsService $statisticsService): JsonResponse
    {
        $query = StatisticsQueryDto::forUser(
            (int) auth()->id(),
            $request->toDto()
        );
        $stats = $statisticsService->getSummaryStatistics($query);

        return $this->respondWithStatistics($stats);
    }

    #[OA\Get(
        path: '/statistics/trends',
        summary: '趨勢指標',
        description: '取得衝動消費週對週比較、高信心決策統計',
        tags: ['Statistics'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'impulse_spending',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'this_week', type: 'number', format: 'float', example: 300),
                                        new OA\Property(property: 'last_week', type: 'number', format: 'float', example: 250),
                                        new OA\Property(property: 'change_percentage', type: 'number', format: 'float', example: 20),
                                        new OA\Property(property: 'trend', type: 'string', example: 'up'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'high_confidence_intents',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'intent', type: 'string', example: 'necessity'),
                                            new OA\Property(property: 'intent_label', type: 'string', example: '必要性'),
                                            new OA\Property(property: 'count', type: 'integer', example: 5),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(
                response: 422,
                description: '驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'validation_error'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: '伺服器錯誤',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'server_error'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function trends(GetTrendsRequest $request, StatisticsService $statisticsService): JsonResponse
    {
        $request->validated();
        $query = TrendsStatisticsQueryDto::forUser((int) auth()->id(), now());
        $stats = $statisticsService->getTrendsStatistics($query);

        return $this->respondWithStatistics($stats);
    }

    /**
     * Standardize statistics response shape for collection and DTO results.
     */
    private function respondWithStatistics(mixed $data): JsonResponse
    {
        $payload = match (true) {
            $data instanceof Collection => $data->map(
                static fn (mixed $item): array => is_object($item) && method_exists($item, 'toArray')
                    ? $item->toArray()
                    : (array) $item
            )->values()->all(),
            is_object($data) && method_exists($data, 'toArray') => $data->toArray(),
            default => (array) $data,
        };

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }
}
