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
        Schema::table('accounts', function (Blueprint $table) {
            // Remove slug field - accounts are not web entities
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
            
            // Add business information fields
            $table->string('company_name')->nullable()->after('name');
            $table->enum('account_type', ['customer', 'prospect', 'partner', 'internal'])->default('customer')->after('company_name');
            
            // Contact information
            $table->string('contact_person')->nullable()->after('account_type');
            $table->string('email')->nullable()->after('contact_person');
            $table->string('phone')->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            
            // Address information
            $table->text('address')->nullable()->after('website');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');
            
            // Billing information
            $table->text('billing_address')->nullable()->after('country');
            $table->string('billing_city')->nullable()->after('billing_address');
            $table->string('billing_state')->nullable()->after('billing_city');
            $table->string('billing_postal_code')->nullable()->after('billing_state');
            $table->string('billing_country')->nullable()->after('billing_postal_code');
            
            // Business details
            $table->string('tax_id')->nullable()->after('billing_country');
            $table->text('notes')->nullable()->after('tax_id');
            
            // Add indexes for business queries
            $table->index(['account_type', 'is_active']);
            $table->index('company_name');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Remove business fields
            $table->dropIndex(['account_type', 'is_active']);
            $table->dropIndex(['company_name']);
            $table->dropIndex(['email']);
            
            $table->dropColumn([
                'company_name',
                'account_type',
                'contact_person',
                'email',
                'phone',
                'website',
                'address',
                'city',
                'state',
                'postal_code',
                'country',
                'billing_address',
                'billing_city',
                'billing_state',
                'billing_postal_code',
                'billing_country',
                'tax_id',
                'notes'
            ]);
            
            // Restore slug field
            $table->string('slug')->unique()->after('name');
        });
    }
};