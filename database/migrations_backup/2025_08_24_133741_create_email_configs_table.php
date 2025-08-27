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
        Schema::create('email_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->string('name')->default('Default'); // Configuration name
            $table->enum('direction', ['incoming', 'outgoing', 'both'])->default('both');
            $table->string('driver')->default('smtp'); // smtp, ses, postmark, mailgun, etc.
            
            // SMTP/General Settings
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->enum('encryption', ['tls', 'ssl', 'starttls', 'none'])->nullable();
            
            // From Address Settings
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            
            // Incoming Email Settings
            $table->string('incoming_protocol')->nullable(); // imap, pop3
            $table->string('incoming_host')->nullable();
            $table->integer('incoming_port')->nullable();
            $table->string('incoming_username')->nullable();
            $table->string('incoming_password')->nullable();
            $table->string('incoming_encryption')->nullable();
            $table->string('incoming_folder')->default('INBOX');
            
            // Configuration Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(100); // Higher number = higher priority
            
            // Additional Settings (JSON)
            $table->json('settings')->nullable();
            
            // Metadata
            $table->timestamp('last_tested_at')->nullable();
            $table->json('test_results')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['account_id', 'is_active']);
            $table->index(['direction', 'is_active']);
            $table->unique(['account_id', 'name', 'direction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_configs');
    }
};
