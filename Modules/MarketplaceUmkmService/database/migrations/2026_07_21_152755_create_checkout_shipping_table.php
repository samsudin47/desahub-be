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
        Schema::create('checkout_shipping', function (Blueprint $table) {
            $table->string('uuid', 50)->primary();
            $table->string('uuid_checkout', 50)->unique();
            $table->string('nama_penerima', 100)->nullable();
            $table->string('no_hp_penerima', 20)->nullable();
            $table->text('alamat_penerima')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
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
        Schema::dropIfExists('checkout_shipping');
    }
};
