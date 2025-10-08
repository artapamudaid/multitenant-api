<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Ubah kolom deleted_at jadi timestamp with timezone (PostgreSQL)
            $table->timestampTz('deleted_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Kembalikan ke timestamp biasa jika dibatalkan
            $table->timestamp('deleted_at')->nullable()->change();
        });
    }
};
