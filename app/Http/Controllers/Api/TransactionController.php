<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', Transaction::class);

        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        $filters = $request->query('filters', []);
        $search = $request->query('search', '');

        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        $userId = $auth->id();

        // Build cache key based on request parameters
        $cacheKey = 'transactions_' . md5(
            json_encode([
                'page' => $page,
                'per_page' => $perPage,
                'filters' => $filters,
                'search' => $search,
                'user_id' => $userId
            ])
        );

        // Try to get from cache first
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Use database query builder with proper indexing
        $query = Transaction::query()
            ->with(['user'])
            ->when($filters, function ($query) use ($filters) {
                if (isset($filters['nfc'])) {
                    $query->withNfc();
                }
                if (isset($filters['type'])) {
                    $query->where('transaction_type', $filters['type']);
                }
                if (isset($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('amount', 'like', '%' . $search . '%')
                        ->orWhere('transaction_type', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%');
                });
            })
            ->recent();

        // Apply pagination
        $transactions = $query->paginate($perPage);

        // Cache the result for 5 minutes
        Cache::put($cacheKey, $transactions, now()->addMinutes(5));

        return response()->json([
            'data' => $transactions,
            'meta' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage()
            ]
        ]);
    }

    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);
        return response()->json($transaction);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Transaction::class);

        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        $userId = $auth->id();

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'transaction_type' => 'required|string|max:50',
            'status' => 'required|string|max:20',
            'nfc_tag_id' => 'nullable|string|max:50',
            'nfc_data' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            throw new BadRequestHttpException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'user_id' => $userId,
                'amount' => $request->amount,
                'transaction_type' => $request->transaction_type,
                'status' => $request->status,
                'nfc_tag_id' => $request->nfc_tag_id,
                'nfc_data' => $request->nfc_data,
                'metadata' => $request->metadata
            ]);

            DB::commit();
            Cache::forget('transactions_*'); // Clear related caches

            return response()->json($transaction, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestHttpException('Failed to create transaction');
        }
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0',
            'transaction_type' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:20',
            'nfc_tag_id' => 'nullable|string|max:50',
            'nfc_data' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            throw new BadRequestHttpException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $transaction->update($request->all());
            DB::commit();
            Cache::forget('transactions_*'); // Clear related caches

            return response()->json($transaction);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestHttpException('Failed to update transaction');
        }
    }

    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        DB::beginTransaction();
        try {
            $transaction->delete();
            DB::commit();
            Cache::forget('transactions_*'); // Clear related caches

            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestHttpException('Failed to delete transaction');
        }
    }

    public function nfcTransactions(Request $request)
    {
        $this->authorize('viewAny', Transaction::class);
        return $this->index($request->merge(['filters' => ['nfc' => true]]));
    }
}
