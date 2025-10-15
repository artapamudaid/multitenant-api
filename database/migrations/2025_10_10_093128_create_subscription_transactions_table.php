<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->uuid('user_id')->nullable();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->foreignId('previous_package_id')->nullable()->constrained('packages')->restrictOnDelete();

            $table->enum('type', ['registration', 'upgrade', 'downgrade', 'renewal', 'cancellation', 'trial_start', 'trial_end']);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');

            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->integer('duration_months')->default(1);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('invoice_number')->nullable()->unique();

            $table->json('metadata')->nullable(); // Additional info
            $table->text('notes')->nullable();

            $table->timestamps();

            // Tambahkan foreign key constraint\
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->cascadeOnDelete();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_transactions');
    }
};
