<?php

namespace App\Console\Commands;

use App\Services\TemplateService;
use Illuminate\Console\Command;

class CreateSystemTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:create-system-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create system import templates (FreeScout, Custom, etc.)';

    /**
     * Execute the console command.
     */
    public function handle(TemplateService $templateService)
    {
        $this->info('Creating system import templates...');

        try {
            $templateService->createSystemTemplates();

            $this->info('✅ System templates created successfully:');
            $this->line('  - FreeScout Standard');
            $this->line('  - Custom Database');

        } catch (\Exception $e) {
            $this->error('❌ Failed to create system templates: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
