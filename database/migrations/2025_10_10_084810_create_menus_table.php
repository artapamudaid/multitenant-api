<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Dashboard, Users, Reports
            $table->string('icon')->nullable(); // Icon class/name
            $table->string('route')->nullable(); // Route name atau path
            $table->string('url')->nullable(); // Direct URL (optional)
            $table->foreignId('parent_id')->nullable()->constrained('menus')->cascadeOnDelete();
            $table->enum('scope', ['super_admin', 'tenant']); // Menu untuk siapa
            $table->string('permission')->nullable(); // Permission required
            $table->string('package_feature')->nullable(); // Feature paket required
            $table->integer('order')->default(0); // Sort order
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable(); // Badge, label, dll
            $table->timestamps();

            $table->index(['scope', 'parent_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
