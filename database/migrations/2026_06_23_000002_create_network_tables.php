<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_discoverable')->default(false);
            $table->json('open_to')->nullable(); // ['leads','hiring','partnerships','investments']
            $table->unsignedInteger('profile_views')->default(0);
            $table->timestamp('opted_in_at')->nullable();
            $table->timestamp('opted_out_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_profile_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // industry|technology|topic|service
            $table->string('value');
            $table->timestamps();

            $table->index(['user_profile_id', 'type']);
        });

        Schema::create('user_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('following_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_id', 'following_id']);
        });

        Schema::create('user_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('addressee_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending|accepted|declined|blocked
            $table->string('message')->nullable();
            $table->timestamps();

            $table->unique(['requester_id', 'addressee_id']);
            $table->index(['addressee_id', 'status']);
        });

        Schema::create('network_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('type')->default('update'); // update|article|opportunity
            $table->string('visibility')->default('public'); // public|connections|followers
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('network_post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('network_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('like'); // like|insightful|celebrate
            $table->timestamps();

            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('network_post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('network_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('network_post_comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_post_comments');
        Schema::dropIfExists('network_post_reactions');
        Schema::dropIfExists('network_posts');
        Schema::dropIfExists('user_connections');
        Schema::dropIfExists('user_follows');
        Schema::dropIfExists('user_profile_interests');
        Schema::dropIfExists('user_profiles');
    }
};
