<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Income\IncomePaginateQueryDto;
use App\Http\Requests\GetIncomesRequest;
use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use App\Http\Resources\IncomeCollection;
use App\Http\Resources\IncomeResource;
use App\Models\Income;
use App\Services\IncomeService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Income CRUD endpoints for authenticated users.
 */
class IncomeController extends Controller
{
    #[OA\Get(
        path: '/incomes',
        summary: '查詢收入列表',
        description: '取得收入記錄列表，支援分頁和篩選條件',
        tags: ['Incomes'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: '頁碼', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: '每頁筆數', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'is_active', in: 'query', description: '是否啟用', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'frequency_type', in: 'query', description: '週期類型', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: '薪資'),
                                    new OA\Property(property: 'amount', type: 'string', example: '80000.00'),
                                    new OA\Property(property: 'amount_display', type: 'string', example: '$80,000'),
                                    new OA\Property(property: 'currency', type: 'string', example: 'TWD'),
                                    new OA\Property(property: 'frequency_type', type: 'string', example: 'monthly'),
                                    new OA\Property(property: 'frequency_type_label', type: 'string', example: '每月'),
                                    new OA\Property(property: 'frequency_interval', type: 'integer', example: 1),
                                    new OA\Property(property: 'monthly_amount', type: 'string', example: '80000.00'),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 1),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 3),
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
    public function index(GetIncomesRequest $request, IncomeService $incomeService): IncomeCollection
    {
        $request->validated();
        $query = IncomePaginateQueryDto::forUser((int) auth()->id(), $request->toDto());
        $incomes = $incomeService->paginate($query);

        return new IncomeCollection($incomes);
    }

    #[OA\Post(
        path: '/incomes',
        summary: '新增收入',
        tags: ['Incomes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'amount', 'frequency_type', 'start_date'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: '薪資'),
                    new OA\Property(property: 'amount', type: 'number', example: 80000),
                    new OA\Property(property: 'currency', type: 'string', example: 'TWD'),
                    new OA\Property(property: 'frequency_type', type: 'string', example: 'monthly'),
                    new OA\Property(property: 'frequency_interval', type: 'integer', example: 1),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-02-01'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: null),
                    new OA\Property(property: 'note', type: 'string', example: '本薪'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: '建立成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: '薪資'),
                                new OA\Property(property: 'amount', type: 'string', example: '80000.00'),
                                new OA\Property(property: 'frequency_type', type: 'string', example: 'monthly'),
                                new OA\Property(property: 'frequency_type_label', type: 'string', example: '每月'),
                                new OA\Property(property: 'frequency_interval', type: 'integer', example: 1),
                                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-02-01'),
                                new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: null),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
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
    public function store(StoreIncomeRequest $request, IncomeService $incomeService): JsonResponse
    {
        $income = $incomeService->create($request->toDto());

        return response()->json([
            'success' => true,
            'data' => new IncomeResource($income),
        ], 201);
    }

    #[OA\Get(
        path: '/incomes/{id}',
        summary: '查詢單筆收入',
        tags: ['Incomes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '收入 ID', schema: new OA\Schema(type: 'integer')),
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
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: '薪資'),
                                new OA\Property(property: 'amount', type: 'string', example: '80000.00'),
                                new OA\Property(property: 'frequency_type', type: 'string', example: 'monthly'),
                                new OA\Property(property: 'frequency_type_label', type: 'string', example: '每月'),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '找不到資源'),
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
    public function show(Income $income): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new IncomeResource($income),
        ]);
    }

    #[OA\Put(
        path: '/incomes/{id}',
        summary: '更新收入',
        tags: ['Incomes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '收入 ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: '薪資'),
                    new OA\Property(property: 'amount', type: 'number', example: 85000),
                    new OA\Property(property: 'frequency_type', type: 'string', example: 'monthly'),
                    new OA\Property(property: 'frequency_interval', type: 'integer', example: 1),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'note', type: 'string'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '更新成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: '新名稱'),
                                new OA\Property(property: 'amount', type: 'string', example: '85000.00'),
                                new OA\Property(property: 'frequency_type', type: 'string', example: 'monthly'),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '找不到資源'),
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
    public function update(UpdateIncomeRequest $request, Income $income, IncomeService $incomeService): JsonResponse
    {
        $updatedIncome = $incomeService->update($income, $request->toDto());

        return response()->json([
            'success' => true,
            'data' => new IncomeResource($updatedIncome),
        ]);
    }

    #[OA\Delete(
        path: '/incomes/{id}',
        summary: '刪除收入',
        tags: ['Incomes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '收入 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '刪除成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '收入已刪除'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '找不到資源'),
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
    public function destroy(Income $income, IncomeService $incomeService): JsonResponse
    {
        $incomeService->delete($income);

        return response()->json([
            'success' => true,
            'message' => '收入已刪除',
        ]);
    }
}
