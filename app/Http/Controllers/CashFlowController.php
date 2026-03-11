<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\CashFlow\CashFlowProjectionQueryDto;
use App\DTO\CashFlow\CashFlowSummaryQueryDto;
use App\Http\Requests\GetCashFlowProjectionRequest;
use App\Services\CashFlowService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Cash flow analytics endpoints for summary and projection views.
 */
class CashFlowController extends Controller
{
    #[OA\Get(
        path: '/cash-flow/summary',
        summary: '取得現金流摘要',
        description: '計算總收入、總支出、淨現金流和儲蓄率',
        tags: ['Cash Flow'],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 401, description: '未授權'),
        ]
    )]
    public function summary(CashFlowService $cashFlowService): JsonResponse
    {
        $summary = $cashFlowService->getSummary(
            CashFlowSummaryQueryDto::forUser((int) auth()->id())
        );

        return response()->json([
            'success' => true,
            'data' => $summary->toArray(),
        ]);
    }

    #[OA\Get(
        path: '/cash-flow/projection',
        summary: '取得多月現金流預測',
        description: '計算指定月數的現金流預測，包含每月收入、支出、淨額和累計餘額',
        tags: ['Cash Flow'],
        parameters: [
            new OA\Parameter(name: 'months', in: 'query', description: '預測月數', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1, maximum: 12)),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
            new OA\Response(response: 401, description: '未授權'),
            new OA\Response(response: 422, description: '參數驗證失敗'),
        ]
    )]
    public function projection(GetCashFlowProjectionRequest $request, CashFlowService $cashFlowService): JsonResponse
    {
        $request->validated();
        $query = CashFlowProjectionQueryDto::forUser((int) auth()->id(), $request->toDto());
        $projections = $cashFlowService->getProjection($query);

        return response()->json([
            'success' => true,
            'data' => $projections->map(static fn ($item): array => $item->toArray())->all(),
        ]);
    }
}
