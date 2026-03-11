<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\CashFlowItem\CashFlowItemPaginateQueryDto;
use App\Http\Requests\GetCashFlowItemsRequest;
use App\Http\Requests\StoreCashFlowItemRequest;
use App\Http\Requests\UpdateCashFlowItemRequest;
use App\Http\Resources\CashFlowItemCollection;
use App\Http\Resources\CashFlowItemResource;
use App\Models\CashFlowItem;
use App\Services\CashFlowItemService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Cash flow item CRUD endpoints for authenticated users.
 *
 * Authorization note:
 * - Route model binding queries are constrained by BelongsToUser -> UserScope.
 * - Scoped-out resources are resolved as 404 by design (instead of explicit 403).
 */
class CashFlowItemController extends Controller
{
    #[OA\Get(
        path: '/cash-flow-items',
        summary: '查詢支出估算項目列表',
        description: '取得支出估算項目列表，支援分頁和篩選條件',
        tags: ['Cash Flow Items'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: '頁碼', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: '每頁筆數', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'category', in: 'query', description: '類別篩選', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', description: '是否啟用', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'frequency_type', in: 'query', description: '週期類型', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
        ]
    )]
    public function index(GetCashFlowItemsRequest $request, CashFlowItemService $cashFlowItemService): CashFlowItemCollection
    {
        $request->validated();
        $query = CashFlowItemPaginateQueryDto::forUser((int) auth()->id(), $request->toDto());
        $items = $cashFlowItemService->paginate($query);

        return new CashFlowItemCollection($items);
    }

    #[OA\Post(
        path: '/cash-flow-items',
        summary: '新增支出估算項目',
        tags: ['Cash Flow Items'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'amount', 'category', 'frequency_type', 'start_date'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: '房租'),
                    new OA\Property(property: 'amount', type: 'number', example: 25000),
                    new OA\Property(property: 'currency', type: 'string', example: 'TWD'),
                    new OA\Property(property: 'category', type: 'string', example: 'living'),
                    new OA\Property(property: 'frequency_type', type: 'string', example: 'monthly'),
                    new OA\Property(property: 'frequency_interval', type: 'integer', example: 1),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-02-01'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: null),
                    new OA\Property(property: 'note', type: 'string', example: '台北市租屋'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '建立成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function store(StoreCashFlowItemRequest $request, CashFlowItemService $cashFlowItemService): JsonResponse
    {
        $item = $cashFlowItemService->create($request->toDto());

        return response()->json([
            'success' => true,
            'data' => new CashFlowItemResource($item),
        ], 201);
    }

    #[OA\Get(
        path: '/cash-flow-items/{id}',
        summary: '查詢單筆支出估算項目',
        tags: ['Cash Flow Items'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '項目 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '找不到資源'),
        ]
    )]
    public function show(CashFlowItem $cashFlowItem, CashFlowItemService $cashFlowItemService): JsonResponse
    {
        // Access control is enforced by model global scope (BelongsToUser/UserScope).
        return response()->json([
            'success' => true,
            'data' => new CashFlowItemResource($cashFlowItemService->show($cashFlowItem)),
        ]);
    }

    #[OA\Put(
        path: '/cash-flow-items/{id}',
        summary: '更新支出估算項目',
        tags: ['Cash Flow Items'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '項目 ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: '房租'),
                    new OA\Property(property: 'amount', type: 'number', example: 26000),
                    new OA\Property(property: 'category', type: 'string', example: 'living'),
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
            new OA\Response(response: 200, description: '更新成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '找不到資源'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function update(UpdateCashFlowItemRequest $request, CashFlowItem $cashFlowItem, CashFlowItemService $cashFlowItemService): JsonResponse
    {
        // Access control is enforced by model global scope (BelongsToUser/UserScope).
        $updatedItem = $cashFlowItemService->update($cashFlowItem, $request->toDto());

        return response()->json([
            'success' => true,
            'data' => new CashFlowItemResource($updatedItem),
        ]);
    }

    #[OA\Delete(
        path: '/cash-flow-items/{id}',
        summary: '刪除支出估算項目',
        tags: ['Cash Flow Items'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '項目 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '刪除成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '找不到資源'),
        ]
    )]
    public function destroy(CashFlowItem $cashFlowItem, CashFlowItemService $cashFlowItemService): JsonResponse
    {
        // Access control is enforced by model global scope (BelongsToUser/UserScope).
        $cashFlowItemService->delete($cashFlowItem);

        return response()->json([
            'success' => true,
            'message' => '支出估算項目已刪除',
        ]);
    }
}
