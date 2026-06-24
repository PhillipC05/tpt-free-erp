<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_signatures', function (Blueprint $table) {
            $table->id();
            $table->morphs('signable'); // signable_type, signable_id
            $table->string('token', 64)->unique(); // secret token for signing link
            $table->string('status', 20)->default('pending'); // pending|signed|declined|expired
            $table->string('signer_name');
            $table->string('signer_email');
            $table->string('signer_ip', 45)->nullable();
            $table->text('signer_user_agent')->nullable();
            $table->longText('signature_data')->nullable(); // base64 canvas PNG or typed name
            $table->string('signature_type', 10)->nullable(); // drawn|typed
            $table->string('document_hash', 64)->nullable(); // SHA-256 of signable at request time
            $table->string('signed_hash', 64)->nullable(); // SHA-256 of signable at sign time
            $table->json('audit_log')->nullable();
            $table->text('message')->nullable(); // optional message shown to signer
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('signed_by_user_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['signable_type', 'signable_id']);
            $table->index('status');
            $table->index('signer_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_signatures');
    }
};
