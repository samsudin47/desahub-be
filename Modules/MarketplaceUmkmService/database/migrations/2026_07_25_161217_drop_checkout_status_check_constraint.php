<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Hapus sisa constraint enum lama (bukan user input).
        DB::statement('ALTER TABLE checkout DROP CONSTRAINT IF EXISTS checkout_status_check');
    }

    public function down(): void
    {
        // sengaja kosong: status sudah string bebas, constraint lama tidak dikembalikan
    }
};
