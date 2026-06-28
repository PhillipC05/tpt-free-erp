<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_attendance', function (Blueprint $table) {
            $table->decimal('regular_hours', 5, 2)->default(0)->after('total_hours');
            $table->decimal('overtime_hours', 5, 2)->default(0)->after('regular_hours');
        });
    }

    public function down(): void
    {
        Schema::table('hr_attendance', function (Blueprint $table) {
            $table->dropColumn(['regular_hours', 'overtime_hours']);
        });
    }
};
