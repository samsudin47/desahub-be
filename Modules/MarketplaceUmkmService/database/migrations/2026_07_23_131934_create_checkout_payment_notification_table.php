<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('checkout_payment_notification', function (Blueprint $table) {
            $table->string('uuid', 50)->primary();
            $table->string('uuid_checkout_payment', 50)->nullable();
            $table->string('order_id', 50)->nullable();
            $table->string('transaction_status', 50)->nullable();
            $table->json('payload');
            $table->boolean('signature_valid')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('uuid_checkout_payment');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_payment_notification');
    }
};
