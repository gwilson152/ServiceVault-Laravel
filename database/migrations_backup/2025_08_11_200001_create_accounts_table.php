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
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('account_type', ['customer', 'prospect', 'partner', 'internal'])->default('customer');
            $table->text('description')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('parent_account_id')->nullable(); // Enhanced hierarchy support
            $table->uuid('root_account_id')->nullable();
            $table->integer('hierarchy_level')->default(0);
            $table->string('hierarchy_path')->nullable(); // "/id1/id2/id3" for path traversal

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
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['account_type', 'is_active']);
            $table->index(['parent_id', 'is_active']);
        });

        // Add self-referencing foreign keys after table creation
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('parent_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('root_account_id')->references('id')->on('accounts')->onDelete('set null');
        });

        // Add foreign key for users table account_id after accounts table is created
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
