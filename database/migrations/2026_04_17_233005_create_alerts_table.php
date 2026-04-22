<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->nullable()->constrained('tools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()
                  ->comment('Usuario afectado o responsable');
            $table->foreignId('movement_id')->nullable()->constrained('movements')->nullOnDelete();
            $table->enum('type', [
                'stock_bajo',
                'no_devolucion',
                'mantenimiento_vencido',
                'mantenimiento_proximo',
                'vida_util',
                'calibracion_vencida',
                'reorden',
            ]);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'resolved_at']);
            $table->index(['user_id', 'read_at']);
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
