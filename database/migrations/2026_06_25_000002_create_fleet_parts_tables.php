<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_part_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('fleet_part_categories');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fleet_parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number', 50)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('fleet_part_categories');
            $table->string('manufacturer', 200)->nullable();
            $table->string('supplier', 200)->nullable();
            $table->string('unit', 20)->default('pcs');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('sell_price', 12, 2)->nullable();
            $table->decimal('quantity_on_hand', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->decimal('reorder_quantity', 10, 2)->default(0);
            $table->string('bin_location', 100)->nullable();
            $table->string('compatible_vehicles', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fleet_part_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('fleet_parts');
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles');
            $table->foreignId('maintenance_id')->nullable()->constrained('fleet_maintenance_records');
            $table->foreignId('trip_id')->nullable()->constrained('fleet_trips');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->date('used_date');
            $table->foreignId('used_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('fleet_part_usages', function (Blueprint $table) {
            $table->index('part_id');
            $table->index('vehicle_id');
            $table->index('used_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_part_usages');
        Schema::dropIfExists('fleet_parts');
        Schema::dropIfExists('fleet_part_categories');
    }
};
