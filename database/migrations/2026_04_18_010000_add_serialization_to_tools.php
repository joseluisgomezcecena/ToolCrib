<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->enum('tracking_mode', ['bulk', 'serialized'])->default('bulk')->after('type');
        });

        Schema::create('tool_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->string('tag')->unique();
            $table->enum('status', ['available', 'in_use', 'maintenance', 'lost', 'scrapped'])->default('available');
            $table->enum('condition', ['ok', 'danado', 'scrap'])->default('ok');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->date('last_maintenance_at')->nullable();
            $table->date('next_maintenance_at')->nullable();
            $table->unsignedInteger('used_cycles')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tool_id', 'status']);
        });

        Schema::table('movements', function (Blueprint $table) {
            $table->foreignId('tool_item_id')->nullable()->after('tool_id')
                  ->constrained('tool_items')->nullOnDelete();
        });

        Schema::table('maintenances', function (Blueprint $table) {
            $table->foreignId('tool_item_id')->nullable()->after('tool_id')
                  ->constrained('tool_items')->nullOnDelete();
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->foreignId('tool_item_id')->nullable()->after('tool_id')
                  ->constrained('tool_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tool_item_id');
        });
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tool_item_id');
        });
        Schema::table('movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tool_item_id');
        });
        Schema::dropIfExists('tool_items');
        Schema::table('tools', function (Blueprint $table) {
            $table->dropColumn('tracking_mode');
        });
    }
};
