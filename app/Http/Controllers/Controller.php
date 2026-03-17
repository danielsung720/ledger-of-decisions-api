<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Ledger of Decisions API',
    description: '決策驅動的記帳 API - 每筆消費都標註決策意圖'
)]
#[OA\Server(
    url: 'http://localhost:8080/api',
    description: '本地開發環境'
)]
#[OA\Tag(name: 'Expenses', description: '消費記錄')]
#[OA\Tag(name: 'Decisions', description: '決策標註')]
#[OA\Tag(name: 'Entries', description: '合併新增')]
#[OA\Tag(name: 'Statistics', description: '統計分析')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    in: 'cookie',
    name: 'laravel_session',
    description: 'Sanctum stateful session cookie'
)]
#[OA\SecurityScheme(
    securityScheme: 'xsrf',
    type: 'apiKey',
    in: 'header',
    name: 'X-XSRF-TOKEN',
    description: 'Required for stateful unsafe methods (POST/PUT/PATCH/DELETE)'
)]
#[OA\Schema(
    schema: 'Category',
    type: 'string',
    enum: ['food', 'transport', 'training', 'living', 'other'],
    description: '消費類別：food=飲食, transport=交通, training=學習/訓練, living=生活, other=其他'
)]
#[OA\Schema(
    schema: 'Intent',
    type: 'string',
    enum: ['necessity', 'efficiency', 'enjoyment', 'recovery', 'impulse'],
    description: '決策意圖：necessity=必要性, efficiency=效率, enjoyment=享受, recovery=恢復, impulse=衝動'
)]
#[OA\Schema(
    schema: 'ConfidenceLevel',
    type: 'string',
    enum: ['high', 'medium', 'low'],
    description: '信心程度：high=高, medium=中, low=低'
)]
#[OA\Schema(
    schema: 'Decision',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'expense_id', type: 'integer', example: 1),
        new OA\Property(property: 'intent', ref: '#/components/schemas/Intent'),
        new OA\Property(property: 'intent_label', type: 'string', example: '必要性'),
        new OA\Property(property: 'confidence_level', ref: '#/components/schemas/ConfidenceLevel', nullable: true),
        new OA\Property(property: 'confidence_level_label', type: 'string', nullable: true, example: '高'),
        new OA\Property(property: 'decision_note', type: 'string', nullable: true, example: '這是必要開支'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Expense',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'amount', type: 'string', example: '150.00'),
        new OA\Property(property: 'currency', type: 'string', example: 'TWD'),
        new OA\Property(property: 'category', ref: '#/components/schemas/Category'),
        new OA\Property(property: 'category_label', type: 'string', example: '飲食'),
        new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: '午餐'),
        new OA\Property(property: 'decision', ref: '#/components/schemas/Decision', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
abstract class Controller
{
}
