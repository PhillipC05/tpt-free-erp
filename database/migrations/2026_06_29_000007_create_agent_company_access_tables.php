<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_company_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_profile_id')->constrained('agent_profiles')->cascadeOnDelete();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreignId('granted_by')->constrained('users');
            $table->string('access_level', 20)->default('use');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['agent_profile_id', 'company_id']);
        });

        Schema::table('agent_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->index()->after('agent_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('agent_tokens', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::dropIfExists('agent_company_access');
    }
};
