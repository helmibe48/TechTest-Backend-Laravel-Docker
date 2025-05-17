<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run RolePermissionSeeder first.');
            return;
        }

        foreach (range(1, 30) as $i) {
            Transaction::create([
                'user_id' => $users->random()->id,
                'amount' => random_int(10000, 1000000),
                'transaction_type' => collect(['topup', 'payment', 'refund'])->random(),
                'status' => collect(['pending', 'completed', 'failed'])->random(),
                'nfc_tag_id' => Str::random(10),
                'nfc_data' => ['uid' => Str::uuid()->toString()],
                'metadata' => [
                    'note' => 'Transaksi dummy #' . $i,
                    'source' => 'seeder'
                ]
            ]);
        }
    }
}
