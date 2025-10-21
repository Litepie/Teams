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
            $table->string('token', 64)->unique('team_invitations_token_unique_idx');
            $table->string('role', 50)->default('member');
            $table->json('permissions')->nullable();
            $table->string('status', 50)->default('pending');
            $table->text('message')->nullable();
            
            // Who sent the invitation (polymorphic) - explicitly defined
            $table->uuid('invited_by_id')->nullable();
            $table->string('invited_by_type')->nullable();
            
            // Who accepted the invitation (polymorphic) - explicitly defined
            $table->uuid('accepted_by_id')->nullable();
            $table->string('accepted_by_type')->nullable();
            
            // Timestamps
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            
            // Multi-tenant support
            $table->uuid('tenant_id')->nullable();
            
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('team_id', 'team_invitations_team_id_fk')->references('id')->on('teams')->onDelete('cascade');
            
            // Explicit indexes with globally unique names
            $table->index('tenant_id', 'team_invitations_tenant_id_idx');
            $table->index(['team_id', 'email'], 'team_invitations_team_email_idx');
            $table->index(['tenant_id', 'status'], 'team_invitations_tenant_status_idx');
            $table->index(['status', 'expires_at'], 'team_invitations_status_expires_idx');
            $table->index(['invited_by_type', 'invited_by_id'], 'team_invitations_invited_by_morph_idx');
            $table->index(['accepted_by_type', 'accepted_by_id'], 'team_invitations_accepted_by_morph_idx');
            $table->index('token', 'team_invitations_token_idx');
            $table->index('email', 'team_invitations_email_idx');
            $table->index('status', 'team_invitations_status_idx');
            $table->index('expires_at', 'team_invitations_expires_at_idx');
            
            // Prevent duplicate pending invitations
            $table->unique(['team_id', 'email'], 'team_invitations_unique_pending_idx');
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
