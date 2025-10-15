<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('package_id')->after('subdomain')
                ->nullable()
                ->constrained('packages')
                ->nullOnDelete();

            $table->timestamp('package_started_at')->nullable();
            $table->timestamp('package_expires_at')->nullable();
            $table->enum('subscription_status', ['active', 'expired', 'cancelled', 'trial'])
                ->default('trial');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn([
                'package_id',
                'package_started_at',
                'package_expires_at',
                'subscription_status'
            ]);
        });
    }
};
