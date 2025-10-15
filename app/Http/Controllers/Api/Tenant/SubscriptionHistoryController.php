<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionTransactionResource;
use App\Models\SubscriptionTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionHistoryController extends Controller
{
    /**
     * Get subscription history for current tenant
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = config('app.current_tenant');

        $query = SubscriptionTransaction::forTenant($tenant->id)
            ->with(['package', 'previousPackage', 'user']);

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transactions = $query->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => SubscriptionTransactionResource::collection($transactions),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    /**
     * Get single transaction detail
     */
    public function show(Request $request, SubscriptionTransaction $transaction): JsonResponse
    {
        $tenant = config('app.current_tenant');

        // Check if transaction belongs to current tenant
        if ($transaction->tenant_id !== $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SubscriptionTransactionResource(
                $transaction->load(['package', 'previousPackage', 'user'])
            ),
        ]);
    }

    /**
     * Get subscription statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $tenant = config('app.current_tenant');

        $stats = [
            'total_transactions' => SubscriptionTransaction::forTenant($tenant->id)->count(),
            'total_spent' => SubscriptionTransaction::forTenant($tenant->id)
                ->completed()
                ->sum('amount'),
            'upgrades_count' => SubscriptionTransaction::forTenant($tenant->id)
                ->byType('upgrade')
                ->completed()
                ->count(),
            'renewals_count' => SubscriptionTransaction::forTenant($tenant->id)
                ->byType('renewal')
                ->completed()
                ->count(),
            'last_transaction' => new SubscriptionTransactionResource(
                SubscriptionTransaction::forTenant($tenant->id)
                    ->with(['package'])
                    ->latest()
                    ->first()
            ),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Download invoice (placeholder)
     */
    public function downloadInvoice(Request $request, SubscriptionTransaction $transaction): JsonResponse
    {
        $tenant = config('app.current_tenant');

        if ($transaction->tenant_id !== $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        // TODO: Generate PDF invoice
        // For now, return transaction data
        return response()->json([
            'success' => true,
            'message' => 'Invoice generation coming soon',
            'data' => new SubscriptionTransactionResource($transaction->load(['package', 'tenant'])),
        ]);
    }
}
