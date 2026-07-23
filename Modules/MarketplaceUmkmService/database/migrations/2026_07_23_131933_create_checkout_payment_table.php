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
        Schema::create('checkout_payment', function (Blueprint $table) {
            $table->string('uuid', 50)->primary();
            $table->string('uuid_checkout', 50);
            $table->string('order_id', 50)->unique();
            $table->string('snap_token')->nullable();
            $table->unsignedBigInteger('gross_amount');
            $table->string('payment_type', 50)->nullable();
            $table->string('bank', 50)->nullable();
            $table->string('va_number', 50)->nullable();
            $table->string('bill_key', 50)->nullable();
            $table->string('biller_code', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->string('transaction_status', 50)->nullable();
            $table->string('fraud_status', 50)->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('created_by')->nullable()->comment('ID of the user who created the record');
            $table->string('updated_by')->nullable()->comment('ID of the user who updated the record');
            $table->string('deleted_by')->nullable()->comment('ID of the user who deleted the record');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->boolean('is_deleted')->default(0)->comment('0: not deleted, 1: deleted');

            $table->index('uuid_checkout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_payment');
    }
};
