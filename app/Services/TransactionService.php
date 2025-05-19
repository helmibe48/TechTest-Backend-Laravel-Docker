<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionService
{
    /**
     * Get paginated transactions with filtering
     *
     * @param array $params
     * @param int $userId
     * @return LengthAwarePaginator
     */
    public function getPaginatedTransactions(array $params, int $userId): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;
        $filters = $params['filters'] ?? [];
        $search = $params['search'] ?? '';

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

        return $transactions;
    }

    /**
     * Create a new transaction
     *
     * @param array $data
     * @param int $userId
     * @return Transaction
     * @throws \Exception
     */
    public function createTransaction(array $data, int $userId): Transaction
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'user_id' => $userId,
                'amount' => $data['amount'],
                'transaction_type' => $data['transaction_type'],
                'status' => $data['status'],
                'nfc_tag_id' => $data['nfc_tag_id'] ?? null,
                'nfc_data' => $data['nfc_data'] ?? null,
                'metadata' => $data['metadata'] ?? null
            ]);

            DB::commit();
            $this->clearTransactionCache();

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing transaction
     *
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     * @throws \Exception
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        DB::beginTransaction();
        try {
            $transaction->update($data);
            DB::commit();
            $this->clearTransactionCache();

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a transaction
     *
     * @param Transaction $transaction
     * @return bool
     * @throws \Exception
     */
    public function deleteTransaction(Transaction $transaction): bool
    {
        DB::beginTransaction();
        try {
            $transaction->delete();
            DB::commit();
            $this->clearTransactionCache();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Clear transaction cache
     *
     * @return void
     */
    private function clearTransactionCache(): void
    {
        Cache::forget('transactions_*');
    }
}
