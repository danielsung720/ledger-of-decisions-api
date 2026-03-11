<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseWithDecisionRequest;
use App\Http\Resources\ExpenseWithDecisionResource;
use App\Services\EntryService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Endpoint for creating expense and decision in one request.
 */
class EntryController extends Controller
{
    #[OA\Post(
        path: '/entries',
        summary: '合併新增消費與決策',
        description: '一次新增消費記錄和決策標註，適合快速記帳場景。使用 transaction 確保原子性。',
        tags: ['Entries'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'category', 'occurred_at', 'intent'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', example: 250, description: '消費金額'),
                    new OA\Property(property: 'currency', type: 'string', example: 'TWD', description: '幣別，預設 TWD'),
                    new OA\Property(property: 'category', ref: '#/components/schemas/Category'),
                    new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time', example: '2026-02-06 08:00:00'),
                    new OA\Property(property: 'note', type: 'string', example: '計程車'),
                    new OA\Property(property: 'intent', ref: '#/components/schemas/Intent'),
                    new OA\Property(property: 'confidence_level', ref: '#/components/schemas/ConfidenceLevel'),
                    new OA\Property(property: 'decision_note', type: 'string', example: '趕時間必須搭計程車'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '建立成功'),
            new OA\Response(response: 401, description: '未登入或 Token 無效'),
            new OA\Response(response: 422, description: '驗證失敗'),
        ]
    )]
    public function store(StoreExpenseWithDecisionRequest $request, EntryService $entryService): JsonResponse
    {
        $expense = $entryService->create($request->toDto());

        return response()->json([
            'success' => true,
            'data' => new ExpenseWithDecisionResource($expense),
        ], 201);
    }
}
