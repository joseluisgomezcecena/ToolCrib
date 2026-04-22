<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Barcode or RFID');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->enum('type', ['durable', 'consumible'])->default('durable')
                  ->comment('durable: returns; consumible: does not');
            $table->enum('condition', ['ok', 'danado', 'scrap'])->default('ok');
            $table->unsignedInteger('qty_total')->default(1);
            $table->unsignedInteger('qty_available')->default(1);
            $table->unsignedInteger('stock_min')->default(0);
            $table->unsignedInteger('stock_max')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->unsignedInteger('life_cycles')->nullable()->comment('Max cycles/uses');
            $table->unsignedInteger('used_cycles')->default(0);
            $table->date('last_maintenance_at')->nullable();
            $table->date('next_maintenance_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'location_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
