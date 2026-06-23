<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            // Enables fast lookups of all permissions for a given role
            $table->index('role_id', 'idx_role_permissions_role_id');
            // Enables fast reverse lookups: which roles have a given permission
            $table->index('permission_id', 'idx_role_permissions_permission_id');
        });

        Schema::table('user_roles', function (Blueprint $table) {
            // Enables fast lookups of all roles for a given user
            $table->index('user_id', 'idx_user_roles_user_id');
            // Enables efficient filtering of expired assignments
            $table->index('expires_at', 'idx_user_roles_expires_at');
            // Speeds up soft-delete filtering (deleted_at IS NULL is the hot path)
            $table->index('deleted_at', 'idx_user_roles_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropIndex('idx_role_permissions_role_id');
            $table->dropIndex('idx_role_permissions_permission_id');
        });

        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropIndex('idx_user_roles_user_id');
            $table->dropIndex('idx_user_roles_expires_at');
            $table->dropIndex('idx_user_roles_deleted_at');
        });
    }
};
