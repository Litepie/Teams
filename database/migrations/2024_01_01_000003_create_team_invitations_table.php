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
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('team_id');
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->string('role', 50)->default('member');
            $table->json('permissions')->nullable();
            $table->string('status', 50)->default('pending');
            $table->text('message')->nullable();
            
            // Who sent the invitation (polymorphic)
            $table->nullableUuidMorphs('invited_by');
            
            // Who accepted the invitation (polymorphic)
            $table->nullableUuidMorphs('accepted_by');
            
            // Timestamps
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            
            // Multi-tenant support
            $table->uuid('tenant_id')->nullable()->index();
            
            $table->timestamps();
            
            // Indexes
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->index(['team_id', 'email']);
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index(['invited_by_type', 'invited_by_id']);
            $table->index('token');
            
            // Prevent duplicate pending invitations
            $table->unique(['team_id', 'email'], 'unique_pending_team_invitation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
