<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('team_id');
            
            // User (polymorphic) - explicitly defined to avoid naming conflicts
            $table->uuid('user_id')->nullable();
            $table->string('user_type')->nullable();
            
            // Role and permissions
            $table->string('role', 50)->default('member');
            $table->json('permissions')->nullable();
            $table->string('status', 50)->default('active');
            
            // Activity tracking
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            
            // Multi-tenant support
            $table->uuid('tenant_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign Keys
            $table->foreign('team_id', 'team_members_team_id_fk')->references('id')->on('teams')->onDelete('cascade');
            
            // Explicit indexes with globally unique names
            $table->index('tenant_id', 'team_members_tenant_id_idx');
            $table->index(['tenant_id', 'status'], 'team_members_tenant_status_idx');
            $table->index(['user_type', 'user_id'], 'team_members_user_morph_idx');
            $table->index(['team_id', 'role'], 'team_members_team_role_idx');
            $table->index('joined_at', 'team_members_joined_at_idx');
            $table->index('last_activity_at', 'team_members_last_activity_idx');
            $table->index('status', 'team_members_status_idx');
            $table->index('role', 'team_members_role_idx');
            
            // Unique constraint to prevent duplicate memberships
            $table->unique(['team_id', 'user_id', 'user_type'], 'team_members_unique_membership_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
