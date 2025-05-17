<?php

use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('transactions', TransactionController::class);

    // Custom endpoints
    Route::get('transactions/nfc', [TransactionController::class, 'nfcTransactions'])
        ->name('transactions.nfc');
});

Route::post('/login', function (Request $request) {
    $credentials = $request->only('email', 'password');

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = User::where('email', $request->email)->firstOrFail();
    $token = $user->createToken('postman-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
});

Route::get('/tes', function () {
    return response()->json(['message' => 'Invalid credentials']);
});
