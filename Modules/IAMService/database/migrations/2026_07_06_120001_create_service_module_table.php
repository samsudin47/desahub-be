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
        Schema::create('service_module', function (Blueprint $table) {
            $table->string('uuid', 50)->primary();
            $table->string('code', 100)->unique();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(0)->comment('0: custom, 1: system');
            $table->boolean('is_active')->default(1)->comment('0: not active, 1: active');
            $table->boolean('is_deleted')->default(0)->comment('0: not deleted, 1: deleted');
            $table->string('created_by')->nullable()->comment('ID of the user who created the record');
            $table->string('updated_by')->nullable()->comment('ID of the user who updated the record');
            $table->string('deleted_by')->nullable()->comment('ID of the user who deleted the record');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_module');
    }
};
