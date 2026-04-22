<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete()
                  ->comment('Quien consume (cliente)');
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete()
                  ->comment('Quien registra (toolcrib)');
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->enum('type', ['checkout', 'checkin', 'transfer', 'consume', 'scrap'])->default('checkout');
            $table->unsignedInteger('qty')->default(1);
            $table->string('work_order')->nullable();
            $table->string('machine')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('return_due_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();

            $table->index(['tool_id', 'type']);
            $table->index(['customer_id', 'returned_at']);
            $table->index('occurred_at');
            $table->index('work_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
