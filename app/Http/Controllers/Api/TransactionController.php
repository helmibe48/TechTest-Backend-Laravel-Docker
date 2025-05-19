<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var TransactionService
     */
    protected $transactionService;
    
    /**
     * Create a new controller instance.
     *
     * @param TransactionService $transactionService
     */
    public function __construct(TransactionService $transactionService)
    {
        $this->middleware('auth:sanctum');
        $this->transactionService = $transactionService;
    }
    /**
     * Display a listing of the transactions.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $this->authorize('viewAny', Transaction::class);

            $params = [
                'per_page' => $request->query('per_page', 15),
                'page' => $request->query('page', 1),
                'filters' => $request->query('filters', []),
                'search' => $request->query('search', '')
            ];

            $transactions = $this->transactionService->getPaginatedTransactions(
                $params,
                auth()->id()
            );

            return $this->successResponse([
                'transactions' => $transactions->items(),
                'meta' => [
                    'total' => $transactions->total(),
                    'per_page' => $transactions->perPage(),
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage()
                ]
            ], 'Transactions retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving transactions: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve transactions', 500, $e->getMessage());
        }
    }

    /**
     * Display the specified transaction.
     *
     * @param Transaction $transaction
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Transaction $transaction)
    {
        try {
            $this->authorize('view', $transaction);
            return $this->successResponse(['transaction' => $transaction], 'Transaction retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving transaction: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve transaction', 500, $e->getMessage());
        }
    }

    /**
     * Store a newly created transaction in storage.
     *
     * @param StoreTransactionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTransactionRequest $request)
    {
        try {
            // Authorization is handled in the FormRequest
            $transaction = $this->transactionService->createTransaction(
                $request->validated(),
                auth()->id()
            );

            return $this->successResponse(['transaction' => $transaction], 'Transaction created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Error creating transaction: ' . $e->getMessage());
            return $this->errorResponse('Failed to create transaction', 500, $e->getMessage());
        }
    }

    /**
     * Update the specified transaction in storage.
     *
     * @param UpdateTransactionRequest $request
     * @param Transaction $transaction
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        try {
            // Authorization is handled in the FormRequest
            $updatedTransaction = $this->transactionService->updateTransaction(
                $transaction,
                $request->validated()
            );

            return $this->successResponse(['transaction' => $updatedTransaction], 'Transaction updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating transaction: ' . $e->getMessage());
            return $this->errorResponse('Failed to update transaction', 500, $e->getMessage());
        }
    }

    /**
     * Remove the specified transaction from storage.
     *
     * @param Transaction $transaction
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Transaction $transaction)
    {
        try {
            $this->authorize('delete', $transaction);
            $this->transactionService->deleteTransaction($transaction);

            return $this->successResponse(null, 'Transaction deleted successfully', 200);
        } catch (\Exception $e) {
            Log::error('Error deleting transaction: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete transaction', 500, $e->getMessage());
        }
    }

    /**
     * Get transactions with NFC tags.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nfcTransactions(Request $request)
    {
        try {
            $this->authorize('viewAny', Transaction::class);
            $request->merge(['filters' => ['nfc' => true]]);
            return $this->index($request);
        } catch (\Exception $e) {
            Log::error('Error retrieving NFC transactions: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve NFC transactions', 500, $e->getMessage());
        }
    }
}
