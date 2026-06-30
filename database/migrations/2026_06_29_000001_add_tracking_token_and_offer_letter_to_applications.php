<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->string('tracking_token', 40)->nullable()->unique()->after('reviewed_at');
            $table->text('offer_letter_content')->nullable()->after('tracking_token');
            $table->timestamp('offer_letter_generated_at')->nullable()->after('offer_letter_content');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->dropColumn(['tracking_token', 'offer_letter_content', 'offer_letter_generated_at']);
        });
    }
};
