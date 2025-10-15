<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bawah, Menengah, Atas
            $table->string('slug')->unique(); // bawah, menengah, atas
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2); // Harga per bulan
            $table->json('features'); // List fitur dalam paket
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
