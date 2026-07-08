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
        Schema::create('user_role', function (Blueprint $table) {
            $table->string('uuid', 50)->primary();
            $table->string('uuid_user', 50);
            $table->string('uuid_role', 50);
            $table->boolean('is_active')->default(1)->comment('0: not active, 1: active');
            $table->boolean('is_deleted')->default(0)->comment('0: not deleted, 1: deleted');
            $table->string('created_by')->nullable()->comment('ID of the user who created the record');
            $table->string('updated_by')->nullable()->comment('ID of the user who updated the record');
            $table->string('deleted_by')->nullable()->comment('ID of the user who deleted the record');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('uuid_user')
                ->references('uuid')
                ->on('user')
                ->restrictOnDelete();

            $table->foreign('uuid_role')
                ->references('uuid')
                ->on('role')
                ->restrictOnDelete();

            $table->unique(['uuid_user', 'uuid_role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_role');
    }
};
