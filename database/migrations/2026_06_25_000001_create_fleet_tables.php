<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_code', 20)->unique();
            $table->string('make', 100);
            $table->string('model', 100);
            $table->integer('year');
            $table->string('vin', 17)->nullable()->unique();
            $table->string('license_plate', 20)->unique();
            $table->string('color', 50)->nullable();
            $table->enum('type', ['car', 'truck', 'van', 'motorcycle', 'bus', 'trailer', 'other'])->default('car');
            $table->enum('fuel_type', ['gasoline', 'diesel', 'electric', 'hybrid', 'other'])->default('gasoline');
            $table->decimal('current_odometer', 12, 1)->default(0);
            $table->decimal('fuel_capacity', 10, 2)->nullable();
            $table->decimal('fuel_level', 5, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance', 'retired'])->default('active');
            $table->foreignId('assigned_driver_id')->nullable()->constrained('hr_employees');
            $table->foreignId('warehouse_id')->nullable()->constrained('inventory_warehouses');
            $table->date('registration_expiry')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fleet_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees');
            $table->string('license_number', 50);
            $table->string('license_class', 10)->nullable();
            $table->date('license_expiry');
            $table->decimal('license_fee', 10, 2)->nullable();
            $table->text('certifications')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fleet_trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number', 50)->unique();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles');
            $table->foreignId('driver_id')->constrained('fleet_drivers');
            $table->string('start_location', 300);
            $table->string('end_location', 300)->nullable();
            $table->decimal('start_odometer', 12, 1);
            $table->decimal('end_odometer', 12, 1)->nullable();
            $table->decimal('distance', 10, 1)->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fleet_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles');
            $table->foreignId('trip_id')->nullable()->constrained('fleet_trips');
            $table->date('date');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 8, 4);
            $table->decimal('total_cost', 12, 2);
            $table->enum('fuel_type', ['gasoline', 'diesel', 'electric', 'hybrid', 'other']);
            $table->decimal('odometer', 12, 1);
            $table->string('station', 200)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('fleet_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles');
            $table->enum('type', ['preventive', 'corrective', 'emergency', 'inspection']);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('service_provider', 200)->nullable();
            $table->decimal('odometer_at_service', 12, 1)->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('fleet_vehicles', function (Blueprint $table) {
            $table->index('status');
            $table->index('type');
        });

        Schema::table('fleet_trips', function (Blueprint $table) {
            $table->index('vehicle_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index('start_time');
        });

        Schema::table('fleet_fuel_logs', function (Blueprint $table) {
            $table->index('vehicle_id');
            $table->index('date');
        });

        Schema::table('fleet_maintenance_records', function (Blueprint $table) {
            $table->index('vehicle_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_maintenance_records');
        Schema::dropIfExists('fleet_fuel_logs');
        Schema::dropIfExists('fleet_trips');
        Schema::dropIfExists('fleet_drivers');
        Schema::dropIfExists('fleet_vehicles');
    }
};
