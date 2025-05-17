<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('view transactions');
    }

    public function view(User $user, Transaction $transaction)
    {
        return $user->id === $transaction->user_id || 
               $user->can('view any transactions');
    }

    public function create(User $user)
    {
        return $user->can('create transactions');
    }

    public function update(User $user, Transaction $transaction)
    {
        return $user->can('update transactions');
    }

    public function delete(User $user, Transaction $transaction)
    {
        return $user->can('delete transactions');
    }
}
