<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDecisionRequest;
use App\Http\Resources\DecisionResource;
use App\Models\Expense;
use App\Services\DecisionService;
use App\Support\AccessScope;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Decision CRUD endpoints bound to a specific expense.
 */
class DecisionController extends Controller
{
    #[OA\Post(
        path: '/expenses/{expense_id}/decision',
        summary: '新增決策標註',
        description: '為消費記錄新增決策標註，每筆消費只能有一個決策',
        tags: ['Decisions'],
        parameters: [
            new OA\Parameter(name: 'expense_id', in: 'path', required: true, description: '消費記錄 ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['intent'],
                properties: [
                    new OA\Property(property: 'intent', ref: '#/components/schemas/Intent'),
                    new OA\Property(property: 'confidence_level', ref: '#/components/schemas/ConfidenceLevel', nullable: true),
                    new OA\Property(property: 'decision_note', type: 'string', example: '這是必要的開支'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '建立成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '消費記錄不存在'),
            new OA\Response(response: 422, description: '已有決策標註或驗證失敗'),
        ]
    )]
    public function store(StoreDecisionRequest $request, Expense $expense, DecisionService $decisionService): JsonResponse
    {
        $scope = AccessScope::forUser((int) auth()->id());
        $decision = $decisionService->createForExpense($scope, $expense, $request->toCreateDto());

        if ($decision === null) {
            return response()->json([
                'success' => false,
                'message' => '此消費記錄已有決策標註，請使用更新 API',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => new DecisionResource($decision),
        ], 201);
    }

    #[OA\Get(
        path: '/expenses/{expense_id}/decision',
        summary: '查詢決策標註',
        tags: ['Decisions'],
        parameters: [
            new OA\Parameter(name: 'expense_id', in: 'path', required: true, description: '消費記錄 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '消費記錄或決策標註不存在'),
        ]
    )]
    public function show(Expense $expense, DecisionService $decisionService): JsonResponse
    {
        $scope = AccessScope::forUser((int) auth()->id());
        $decision = $decisionService->showForExpense($scope, $expense);

        if ($decision === null) {
            return response()->json([
                'success' => false,
                'message' => '此消費記錄尚無決策標註',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new DecisionResource($decision),
        ]);
    }

    #[OA\Put(
        path: '/expenses/{expense_id}/decision',
        summary: '更新決策標註',
        tags: ['Decisions'],
        parameters: [
            new OA\Parameter(name: 'expense_id', in: 'path', required: true, description: '消費記錄 ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['intent'],
                properties: [
                    new OA\Property(property: 'intent', ref: '#/components/schemas/Intent'),
                    new OA\Property(property: 'confidence_level', ref: '#/components/schemas/ConfidenceLevel', nullable: true),
                    new OA\Property(property: 'decision_note', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '消費記錄或決策標註不存在'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function update(StoreDecisionRequest $request, Expense $expense, DecisionService $decisionService): JsonResponse
    {
        $scope = AccessScope::forUser((int) auth()->id());
        $decision = $decisionService->updateForExpense($scope, $expense, $request->toUpdateDto());

        if ($decision === null) {
            return response()->json([
                'success' => false,
                'message' => '此消費記錄尚無決策標註，請使用新增 API',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new DecisionResource($decision),
        ]);
    }

    #[OA\Delete(
        path: '/expenses/{expense_id}/decision',
        summary: '刪除決策標註',
        tags: ['Decisions'],
        parameters: [
            new OA\Parameter(name: 'expense_id', in: 'path', required: true, description: '消費記錄 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '刪除成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 404, description: '消費記錄或決策標註不存在'),
        ]
    )]
    public function destroy(Expense $expense, DecisionService $decisionService): JsonResponse
    {
        $scope = AccessScope::forUser((int) auth()->id());
        $deleted = $decisionService->deleteForExpense($scope, $expense);

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => '此消費記錄尚無決策標註',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => '決策標註已刪除',
        ]);
    }
}
