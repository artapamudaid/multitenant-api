<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan kolom tenant_id lebih dulu
            $table->uuid('tenant_id')->nullable()->after('id');

            // Tambahan kolom lain
            $table->string('authentik_id')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('authentik_id');
            $table->boolean('is_tenant_owner')->default(false)->after('avatar');

            // Tambahkan foreign key constraint
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->cascadeOnDelete();

            // Tambahkan index
            $table->index(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'email']);
            $table->dropColumn(['tenant_id', 'authentik_id', 'avatar', 'is_tenant_owner']);
        });
    }
};
