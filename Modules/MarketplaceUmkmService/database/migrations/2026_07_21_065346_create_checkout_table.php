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
        Schema::create('checkout', function (Blueprint $table) {
            $table->string('uuid', 50)->primary();
            $table->string('uuid_user');
            $table->string('uuid_cart');
            $table->string('total_items');
            $table->string('total_price');
            $table->enum('status', ['draft', 'cancelled', 'pending', 'paid', 'failed', 'expired'])->default('draft');
            $table->string('created_by')->nullable()->comment('ID of the user who created the record');
            $table->string('updated_by')->nullable()->comment('ID of the user who updated the record');
            $table->string('deleted_by')->nullable()->comment('ID of the user who deleted the record');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->boolean('is_deleted')->default(0)->comment('0: not deleted, 1: deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout');
    }
};
