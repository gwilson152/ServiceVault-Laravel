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
        Schema::table('ticket_addons', function (Blueprint $table) {
            // Add missing columns that exist in the model but not in the database
            $table->uuid('added_by_user_id')->nullable()->after('status');
            $table->string('category')->nullable()->after('description');
            $table->string('sku')->nullable()->after('category');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percent');
            $table->decimal('tax_rate', 5, 4)->default(0)->after('discount_amount');
            $table->boolean('is_taxable')->default(true)->after('billable');
            $table->string('billing_category')->default('addon')->after('is_taxable');
            $table->uuid('addon_template_id')->nullable()->after('billing_category');
            $table->uuid('approved_by_user_id')->nullable()->after('addon_template_id');
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->json('metadata')->nullable()->after('approval_notes');

            // Add foreign key constraints
            $table->foreign('added_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('addon_template_id')->references('id')->on('addon_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_addons', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['added_by_user_id']);
            $table->dropForeign(['approved_by_user_id']);
            $table->dropForeign(['addon_template_id']);

            // Drop added columns
            $table->dropColumn([
                'added_by_user_id',
                'category',
                'sku',
                'discount_amount',
                'tax_rate',
                'is_taxable',
                'billing_category',
                'addon_template_id',
                'approved_by_user_id',
                'approved_at',
                'approval_notes',
                'metadata',
            ]);
        });
    }
};
