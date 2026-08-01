<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_payment', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('paid_at');
            $table->string('verification_source', 50)->nullable()->after('verified_at');
            $table->json('last_status_payload')->nullable()->after('verification_source');
            $table->timestamp('cancelled_at')->nullable()->after('last_status_payload');
            $table->string('cancel_reason')->nullable()->after('cancelled_at');
        });

        Schema::table('checkout_payment_notification', function (Blueprint $table) {
            $table->string('reject_reason', 100)->nullable()->after('signature_valid');
            $table->boolean('verified_via_api')->default(false)->after('reject_reason');
            $table->string('ip_address', 45)->nullable()->after('verified_via_api');
            $table->string('payload_hash', 64)->nullable()->after('ip_address');
            $table->unsignedSmallInteger('http_status')->nullable()->after('payload_hash');
            $table->index('payload_hash');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('checkout_payment', function (Blueprint $table) {
            $table->dropColumn([
                'verified_at',
                'verification_source',
                'last_status_payload',
                'cancelled_at',
                'cancel_reason',
            ]);
        });

        Schema::table('checkout_payment_notification', function (Blueprint $table) {
            $table->dropIndex(['payload_hash']);
            $table->dropIndex(['created_at']);
            $table->dropColumn([
                'reject_reason',
                'verified_via_api',
                'ip_address',
                'payload_hash',
                'http_status',
            ]);
        });
    }
};
