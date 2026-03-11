<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\RecurringExpense\RecurringExpensePaginateQueryDto;
use App\Http\Requests\GenerateExpenseRequest;
use App\Http\Requests\GetRecurringExpenseHistoryRequest;
use App\Http\Requests\GetRecurringExpensesRequest;
use App\Http\Requests\GetUpcomingRecurringExpensesRequest;
use App\Http\Requests\StoreRecurringExpenseRequest;
use App\Http\Requests\UpdateRecurringExpenseRequest;
use App\Http\Resources\ExpenseWithDecisionResource;
use App\Http\Resources\RecurringExpenseCollection;
use App\Http\Resources\RecurringExpenseResource;
use App\Models\RecurringExpense;
use App\Services\RecurringExpenseService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Recurring expense CRUD and helper endpoints for authenticated users.
 */
class RecurringExpenseController extends Controller
{
    #[OA\Get(
        path: '/recurring-expenses',
        summary: '查詢固定支出列表',
        description: '取得固定支出記錄列表，支援分頁和篩選條件',
        tags: ['Recurring Expenses'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: '頁碼', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: '每頁筆數', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'category', in: 'query', description: '類別篩選', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', description: '是否啟用', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'frequency_type', in: 'query', description: '週期類型', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
        ]
    )]
    public function index(GetRecurringExpensesRequest $request, RecurringExpenseService $recurringExpenseService): RecurringExpenseCollection
    {
        $request->validated();
        $query = RecurringExpensePaginateQueryDto::forUser((int) auth()->id(), $request->toDto());
        $recurringExpenses = $recurringExpenseService->paginate($query);

        return new RecurringExpenseCollection($recurringExpenses);
    }

    #[OA\Post(
        path: '/recurring-expenses',
        summary: '新增固定支出',
        tags: ['Recurring Expenses'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'amount_min', 'category', 'frequency_type', 'start_date'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: '車貸'),
                    new OA\Property(property: 'amount_min', type: 'number', example: 15000),
                    new OA\Property(property: 'amount_max', type: 'number', example: null),
                    new OA\Property(property: 'currency', type: 'string', example: 'TWD'),
                    new OA\Property(property: 'category', type: 'string', example: 'living'),
                    new OA\Property(property: 'frequency_type', type: 'string', example: 'monthly'),
                    new OA\Property(property: 'frequency_interval', type: 'integer', example: 1),
                    new OA\Property(property: 'day_of_month', type: 'integer', example: 15),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-02-01'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2031-02-01'),
                    new OA\Property(property: 'default_intent', type: 'string', example: 'necessity'),
                    new OA\Property(property: 'note', type: 'string', example: '中古車分期付款'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '建立成功'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function store(StoreRecurringExpenseRequest $request, RecurringExpenseService $recurringExpenseService): JsonResponse
    {
        $recurringExpense = $recurringExpenseService->create($request->toDto());

        return response()->json([
            'success' => true,
            'data' => new RecurringExpenseResource($recurringExpense),
        ], 201);
    }

    #[OA\Get(
        path: '/recurring-expenses/{id}',
        summary: '查詢單筆固定支出',
        tags: ['Recurring Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '固定支出 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 404, description: '找不到資源'),
        ]
    )]
    public function show(RecurringExpense $recurringExpense, RecurringExpenseService $recurringExpenseService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new RecurringExpenseResource($recurringExpenseService->show($recurringExpense)),
        ]);
    }

    #[OA\Put(
        path: '/recurring-expenses/{id}',
        summary: '更新固定支出',
        tags: ['Recurring Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '固定支出 ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: '車貸'),
                    new OA\Property(property: 'amount_min', type: 'number', example: 15000),
                    new OA\Property(property: 'amount_max', type: 'number', example: null),
                    new OA\Property(property: 'category', type: 'string', example: 'living'),
                    new OA\Property(property: 'frequency_type', type: 'string', example: 'monthly'),
                    new OA\Property(property: 'frequency_interval', type: 'integer', example: 1),
                    new OA\Property(property: 'day_of_month', type: 'integer', example: 15),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'default_intent', type: 'string', example: 'necessity'),
                    new OA\Property(property: 'note', type: 'string'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功'),
            new OA\Response(response: 404, description: '找不到資源'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function update(
        UpdateRecurringExpenseRequest $request,
        RecurringExpense $recurringExpense,
        RecurringExpenseService $recurringExpenseService
    ): JsonResponse {
        $updatedRecurringExpense = $recurringExpenseService->update($recurringExpense, $request->toDto());

        return response()->json([
            'success' => true,
            'data' => new RecurringExpenseResource($updatedRecurringExpense),
        ]);
    }

    #[OA\Delete(
        path: '/recurring-expenses/{id}',
        summary: '刪除固定支出',
        tags: ['Recurring Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '固定支出 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '刪除成功'),
            new OA\Response(response: 404, description: '找不到資源'),
        ]
    )]
    public function destroy(RecurringExpense $recurringExpense, RecurringExpenseService $recurringExpenseService): JsonResponse
    {
        $recurringExpenseService->delete($recurringExpense);

        return response()->json([
            'success' => true,
            'message' => '固定支出已刪除',
        ]);
    }

    #[OA\Get(
        path: '/recurring-expenses/upcoming',
        summary: '查詢即將到期的固定支出',
        tags: ['Recurring Expenses'],
        parameters: [
            new OA\Parameter(name: 'days', in: 'query', description: '天數範圍', schema: new OA\Schema(type: 'integer', default: 7)),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
        ]
    )]
    public function upcoming(
        GetUpcomingRecurringExpensesRequest $request,
        RecurringExpenseService $recurringExpenseService
    ): JsonResponse {
        $upcoming = $recurringExpenseService->getUpcoming($request->toDto((int) auth()->id()));

        return response()->json([
            'success' => true,
            'data' => RecurringExpenseResource::collection($upcoming),
        ]);
    }

    #[OA\Post(
        path: '/recurring-expenses/{id}/generate',
        summary: '手動生成消費記錄',
        tags: ['Recurring Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '固定支出 ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-02-07'),
                    new OA\Property(property: 'amount', type: 'number', example: 15000),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '生成成功'),
            new OA\Response(response: 404, description: '找不到資源'),
        ]
    )]
    public function generate(
        GenerateExpenseRequest $request,
        RecurringExpense $recurringExpense,
        RecurringExpenseService $recurringExpenseService
    ): JsonResponse {
        $payload = $request->toDto();

        $expense = $recurringExpenseService->generateManually($recurringExpense, $payload->date, $payload->amount);
        $expense->load('decision');

        return response()->json([
            'success' => true,
            'data' => new ExpenseWithDecisionResource($expense),
            'message' => '已手動生成消費記錄',
        ], 201);
    }

    #[OA\Get(
        path: '/recurring-expenses/{id}/history',
        summary: '查詢固定支出的生成歷史',
        tags: ['Recurring Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '固定支出 ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', description: '筆數限制', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 404, description: '找不到資源'),
        ]
    )]
    public function history(
        GetRecurringExpenseHistoryRequest $request,
        RecurringExpense $recurringExpense,
        RecurringExpenseService $recurringExpenseService
    ): JsonResponse {
        $expenses = $recurringExpenseService->getHistory($recurringExpense, $request->toDto()->limit);

        return response()->json([
            'success' => true,
            'data' => ExpenseWithDecisionResource::collection($expenses),
        ]);
    }
}
