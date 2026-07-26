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
        Schema::table('checkout_shipping', function (Blueprint $table) {
            $table->string('courier', 50)->nullable()->after('alamat_penerima');
            $table->string('tracking_number', 100)->nullable()->after('courier');
            $table->timestamp('shipped_at')->nullable()->after('tracking_number');
            $table->timestamp('completed_at')->nullable()->after('shipped_at');
            $table->string('cancel_reason')->nullable()->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkout_shipping', function (Blueprint $table) {
            $table->dropColumn([
                'courier',
                'tracking_number',
                'shipped_at',
                'completed_at',
                'cancel_reason',
            ]);
        });
    }
};
