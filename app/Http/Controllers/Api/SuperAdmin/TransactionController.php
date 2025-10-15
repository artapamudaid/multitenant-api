<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionTransactionResource;
use App\Models\SubscriptionTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SubscriptionTransaction::with(['tenant', 'package', 'user']);

        // Filter by tenant
        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('tenant', function ($q2) use ($request) {
                      $q2->where('name', 'like', '%' . $request->search . '%');
                  });
            });
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

    public function statistics(): JsonResponse
    {
        $stats = [
            'total_revenue' => SubscriptionTransaction::completed()->sum('amount'),
            'monthly_revenue' => SubscriptionTransaction::completed()
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'total_transactions' => SubscriptionTransaction::count(),
            'pending_transactions' => SubscriptionTransaction::pending()->count(),
            'by_type' => SubscriptionTransaction::completed()
                ->selectRaw('type, count(*) as count, sum(amount) as total')
                ->groupBy('type')
                ->get(),
            'recent_transactions' => SubscriptionTransactionResource::collection(
                SubscriptionTransaction::with(['tenant', 'package'])
                    ->latest()
                    ->take(10)
                    ->get()
            ),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
