<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // View Users, Create Invoice, dll
            $table->string('slug')->unique(); // users.view, invoices.create
            $table->string('module'); // users, invoices, reports, dll
            $table->text('description')->nullable();
            $table->enum('scope', ['super_admin', 'tenant', 'both'])->default('tenant');
            $table->timestamps();

            $table->index(['module', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
