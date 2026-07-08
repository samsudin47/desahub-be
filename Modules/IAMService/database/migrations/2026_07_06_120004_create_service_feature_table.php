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
        Schema::create('service_feature', function (Blueprint $table) {
            $table->string('uuid', 50)->primary();
            $table->string('uuid_service_module', 50);
            $table->string('service_module', 100)->comment('Module code, e.g. IAMService');
            $table->string('service_feature_name', 100)->comment('Feature code, e.g. IAM_USER_MANAGEMENT');
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

            $table->foreign('uuid_service_module')
                ->references('uuid')
                ->on('service_module')
                ->restrictOnDelete();

            $table->unique(['service_module', 'service_feature_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_feature');
    }
};
