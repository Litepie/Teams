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
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type', 50)->default('project');
            $table->string('status', 50)->default('draft');
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            
            // Multi-tenant support
            $table->uuid('tenant_id')->nullable()->index();
            
            // Owner (polymorphic)
            $table->uuidMorphs('owner');
            
            // Counters for performance
            $table->unsignedInteger('members_count')->default(0);
            $table->unsignedInteger('files_count')->default(0);
            $table->unsignedBigInteger('storage_used')->default(0); // bytes
            
            // Activity tracking
            $table->timestamp('last_activity_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['owner_type', 'owner_id']);
            $table->index(['type', 'status']);
            $table->index('last_activity_at');
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
