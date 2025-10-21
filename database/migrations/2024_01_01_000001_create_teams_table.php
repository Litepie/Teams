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
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique('teams_slug_unique_idx');
            $table->text('description')->nullable();
            $table->string('type', 50)->default('project');
            $table->string('status', 50)->default('draft');
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            
            // Multi-tenant support
            $table->uuid('tenant_id')->nullable();
            
            // Owner (polymorphic) - explicitly named to avoid conflicts
            $table->uuid('owner_id')->nullable();
            $table->string('owner_type')->nullable();
            
            // Counters for performance
            $table->unsignedInteger('members_count')->default(0);
            $table->unsignedInteger('files_count')->default(0);
            $table->unsignedBigInteger('storage_used')->default(0); // bytes
            
            // Activity tracking
            $table->timestamp('last_activity_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Explicit indexes with globally unique names
            $table->index('tenant_id', 'teams_tenant_id_idx');
            $table->index(['tenant_id', 'status'], 'teams_tenant_status_idx');
            $table->index(['owner_type', 'owner_id'], 'teams_owner_morph_idx');
            $table->index(['type', 'status'], 'teams_type_status_idx');
            $table->index('last_activity_at', 'teams_last_activity_idx');
            $table->index('status', 'teams_status_idx');
            $table->index('type', 'teams_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
