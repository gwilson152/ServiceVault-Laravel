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
        // Create accounts table (hierarchy columns removed)
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable()->unique();
            $table->string('name');
            $table->enum('account_type', ['customer', 'prospect', 'partner', 'internal'])->default('customer');
            $table->text('description')->nullable();

            // Contact Information
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Address Information
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            // Billing Address
            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country')->nullable();

            $table->string('tax_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->json('theme_settings')->nullable();
            $table->json('tax_preferences')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['account_type', 'is_active']);
        });

        // Update users table with all modifications
        Schema::table('users', function (Blueprint $table) {
            // Add external_id after id
            $table->string('external_id')->nullable()->unique()->after('id');
            
            // Make email nullable (for agents imported without email)
            $table->string('email')->nullable()->change();
            
            // Make password nullable (for SSO users or imported users)
            $table->string('password')->nullable()->change();
            
            // Add is_visible flag
            $table->boolean('is_visible')->default(true)->after('is_active');
            
            // Add foreign key constraint for account_id
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
        });

        // Create partial unique index for non-null emails with user_type (PostgreSQL)
        DB::statement('CREATE UNIQUE INDEX users_email_user_type_partial_unique ON users (email, user_type) WHERE email IS NOT NULL');

        // Domain mappings table
        Schema::create('domain_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('domain');
            $table->uuid('account_id');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->unique(['domain', 'account_id']);
            $table->index(['domain', 'is_active']);
        });

        // User invitations table
        Schema::create('user_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email');
            $table->uuid('account_id');
            $table->uuid('role_template_id');
            $table->uuid('invited_by');
            $table->string('token')->unique();
            $table->enum('status', ['pending', 'accepted', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['email', 'status']);
            $table->index(['token', 'status']);
        });

        // User preferences table
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('key');
            $table->json('value');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('user_invitations');
        Schema::dropIfExists('domain_mappings');
        
        // Drop partial unique index
        DB::statement('DROP INDEX IF EXISTS users_email_user_type_partial_unique');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn(['external_id', 'is_visible']);
        });
        
        Schema::dropIfExists('accounts');
    }
};