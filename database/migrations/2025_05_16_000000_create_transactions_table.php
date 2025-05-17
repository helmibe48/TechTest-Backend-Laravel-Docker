<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 4);
            $table->string('nfc_tag_id')->nullable()->index();
            $table->json('nfc_data')->nullable();
            $table->string('transaction_type');
            $table->string('status');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['created_at', 'status']);
            $table->index(['transaction_type', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
