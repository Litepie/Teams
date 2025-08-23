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
            
            // User (polymorphic)
            $table->uuidMorphs('user');
            
            // Role and permissions
            $table->string('role', 50)->default('member');
            $table->json('permissions')->nullable();
            $table->string('status', 50)->default('active');
            
            // Activity tracking
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            
            // Multi-tenant support
            $table->uuid('tenant_id')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index(['user_type', 'user_id']);
            $table->index(['team_id', 'role']);
            $table->index('joined_at');
            $table->index('last_activity_at');
            
            // Unique constraint to prevent duplicate memberships
            $table->unique(['team_id', 'user_id', 'user_type'], 'unique_team_user_membership');
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
