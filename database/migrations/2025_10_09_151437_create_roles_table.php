<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Admin, Manager, Staff, dll
            $table->string('slug');
            $table->text('description')->nullable();
            $table->enum('scope', ['super_admin', 'tenant'])->default('tenant');
            $table->uuid('tenant_id')->nullable();
            $table->boolean('is_system')->default(false); // System role (tidak bisa dihapus)
            $table->timestamps();


            // Tambahkan foreign key constraint
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->cascadeOnDelete();

            $table->index(['tenant_id', 'slug']);
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
