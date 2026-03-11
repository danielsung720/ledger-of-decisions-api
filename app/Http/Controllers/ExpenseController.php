<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Expense\ExpenseBatchDeleteQueryDto;
use App\DTO\Expense\ExpensePaginateQueryDto;
use App\Http\Requests\BatchDeleteExpenseRequest;
use App\Http\Requests\GetExpensesRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseCollection;
use App\Http\Resources\ExpenseWithDecisionResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Expense CRUD and batch delete endpoints for authenticated users.
 */
class ExpenseController extends Controller
{
    #[OA\Get(
        path: '/expenses',
        summary: '查詢消費列表',
        description: '取得消費記錄列表，支援分頁和多種篩選條件',
        tags: ['Expenses'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: '頁碼', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: '每頁筆數', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'start_date', in: 'query', description: '起始日期 (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', description: '結束日期 (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'preset', in: 'query', description: '預設時間範圍', schema: new OA\Schema(type: 'string', enum: ['today', 'this_week', 'this_month'])),
            new OA\Parameter(name: 'category', in: 'query', description: '類別篩選 (可用逗號分隔多選)', schema: new OA\Schema(type: 'string', example: 'food,transport')),
            new OA\Parameter(name: 'intent', in: 'query', description: '意圖篩選 (可用逗號分隔多選)', schema: new OA\Schema(type: 'string', example: 'necessity,impulse')),
            new OA\Parameter(name: 'confidence_level', in: 'query', description: '信心程度篩選', schema: new OA\Schema(type: 'string', example: 'high,medium')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
        ]
    )]
    public function index(GetExpensesRequest $request, ExpenseService $expenseService): ExpenseCollection
    {
        $request->validated();
        $query = ExpensePaginateQueryDto::forUser((int) auth()->id(), $request->toDto());
        $expenses = $expenseService->paginate($query);

        return new ExpenseCollection($expenses);
    }

    #[OA\Post(
        path: '/expenses',
        summary: '新增消費記錄',
        tags: ['Expenses'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'category', 'occurred_at'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', example: 150),
                    new OA\Property(property: 'currency', type: 'string', example: 'TWD'),
                    new OA\Property(property: 'category', ref: '#/components/schemas/Category'),
                    new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time', example: '2026-02-06 12:00:00'),
                    new OA\Property(property: 'note', type: 'string', example: '午餐便當'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '建立成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function store(StoreExpenseRequest $request, ExpenseService $expenseService): JsonResponse
    {
        $expense = $expenseService->create($request->toDto());

        return response()->json([
            'success' => true,
            'data' => new ExpenseWithDecisionResource($expense),
        ], 201);
    }

    #[OA\Get(
        path: '/expenses/{id}',
        summary: '查詢單筆消費',
        tags: ['Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '消費記錄 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '找不到資源'),
        ]
    )]
    public function show(Expense $expense, ExpenseService $expenseService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ExpenseWithDecisionResource($expenseService->show($expense)),
        ]);
    }

    #[OA\Put(
        path: '/expenses/{id}',
        summary: '更新消費記錄',
        tags: ['Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '消費記錄 ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'amount', type: 'number', example: 200),
                    new OA\Property(property: 'currency', type: 'string', example: 'TWD'),
                    new OA\Property(property: 'category', ref: '#/components/schemas/Category'),
                    new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'note', type: 'string', example: '午餐便當 + 飲料'),
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
    public function update(UpdateExpenseRequest $request, Expense $expense, ExpenseService $expenseService): JsonResponse
    {
        $updatedExpense = $expenseService->update($expense, $request->toDto());

        return response()->json([
            'success' => true,
            'data' => new ExpenseWithDecisionResource($updatedExpense),
        ]);
    }

    #[OA\Delete(
        path: '/expenses/{id}',
        summary: '刪除消費記錄',
        tags: ['Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '消費記錄 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '刪除成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '找不到資源'),
        ]
    )]
    public function destroy(Expense $expense, ExpenseService $expenseService): JsonResponse
    {
        $expenseService->delete($expense);

        return response()->json([
            'success' => true,
            'message' => '消費記錄已刪除',
        ]);
    }

    #[OA\Delete(
        path: '/expenses/batch',
        summary: '批次刪除消費記錄',
        description: '一次刪除多筆消費記錄，最多 100 筆',
        tags: ['Expenses'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(
                        property: 'ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        example: [1, 2, 3],
                        description: '要刪除的消費記錄 ID 陣列，最多 100 筆'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '刪除成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '成功刪除 3 筆消費記錄'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'deleted_count', type: 'integer', example: 3),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function batchDestroy(BatchDeleteExpenseRequest $request, ExpenseService $expenseService): JsonResponse
    {
        $query = ExpenseBatchDeleteQueryDto::forUser((int) auth()->id(), $request->toDto());
        $deletedCount = $expenseService->batchDelete($query);

        return response()->json([
            'success' => true,
            'message' => "成功刪除 {$deletedCount} 筆消費記錄",
            'data' => [
                'deleted_count' => $deletedCount,
            ],
        ]);
    }
}
