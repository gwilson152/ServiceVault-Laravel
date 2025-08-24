<?php

namespace App\Services;

use App\Models\ImportProfile;
use App\Models\ImportQuery;
use App\Models\ImportTemplate;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TemplateService
{
    protected PostgreSQLConnectionService $connectionService;

    public function __construct(PostgreSQLConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Get all available templates.
     */
    public function getAvailableTemplates(): Collection
    {
        return ImportTemplate::active()
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get templates for a specific platform.
     */
    public function getTemplatesForPlatform(string $platform): Collection
    {
        return ImportTemplate::active()
            ->forPlatform($platform)
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Apply a template to an import profile.
     */
    public function applyTemplate(ImportProfile $profile, ImportTemplate $template): void
    {
        // Set the template relationship
        $profile->update(['template_id' => $template->id]);

        // Create queries from template configuration
        $this->createQueriesFromTemplate($profile, $template->configuration);
    }

    /**
     * Create import queries from template configuration.
     */
    public function createQueriesFromTemplate(ImportProfile $profile, array $templateConfig): array
    {
        $createdQueries = [];
        $queries = $templateConfig['queries'] ?? [];

        foreach ($queries as $index => $queryConfig) {
            $query = ImportQuery::create([
                'profile_id' => $profile->id,
                'name' => $queryConfig['name'] ?? 'Query '.($index + 1),
                'description' => $queryConfig['description'] ?? null,
                'base_table' => $queryConfig['base_table'],
                'joins' => $queryConfig['joins'] ?? null,
                'where_conditions' => $queryConfig['where_conditions'] ?? null,
                'select_fields' => $queryConfig['select_fields'] ?? null,
                'order_by' => $queryConfig['order_by'] ?? null,
                'limit_clause' => $queryConfig['limit_clause'] ?? null,
                'destination_table' => $queryConfig['destination_table'],
                'field_mappings' => $queryConfig['field_mappings'] ?? [],
                'transformation_rules' => $queryConfig['transformation_rules'] ?? null,
                'validation_rules' => $queryConfig['validation_rules'] ?? null,
                'import_order' => $queryConfig['import_order'] ?? $index,
                'is_active' => $queryConfig['is_active'] ?? true,
            ]);

            $createdQueries[] = $query;
        }

        return $createdQueries;
    }

    /**
     * Attempt to detect the best template for a database.
     */
    public function detectBestTemplate(string $connectionName): ?ImportTemplate
    {
        try {
            $tables = $this->connectionService->getTables($connectionName);
            $tableNames = array_map(fn ($table) => $table->table_name ?? $table['table_name'], $tables);

            // No automatic platform detection - require manual template selection
            // Return null to indicate manual selection is needed
            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Create a new custom template.
     */
    public function createTemplate(array $data): ImportTemplate
    {
        return ImportTemplate::create([
            'name' => $data['name'],
            'platform' => $data['platform'] ?? 'custom',
            'description' => $data['description'] ?? null,
            'database_type' => $data['database_type'] ?? 'postgresql',
            'configuration' => $data['configuration'] ?? ['queries' => []],
            'is_system' => false,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Update an existing template.
     */
    public function updateTemplate(ImportTemplate $template, array $data): ImportTemplate
    {
        // Only allow updating non-system templates or by admin users
        if ($template->is_system && ! Auth::user()?->isAdmin()) {
            throw new Exception('Cannot modify system templates');
        }

        $template->update([
            'name' => $data['name'] ?? $template->name,
            'platform' => $data['platform'] ?? $template->platform,
            'description' => $data['description'] ?? $template->description,
            'database_type' => $data['database_type'] ?? $template->database_type,
            'configuration' => $data['configuration'] ?? $template->configuration,
            'is_active' => $data['is_active'] ?? $template->is_active,
        ]);

        return $template;
    }

    /**
     * Export a template configuration.
     */
    public function exportTemplate(ImportTemplate $template): array
    {
        return [
            'name' => $template->name,
            'platform' => $template->platform,
            'description' => $template->description,
            'database_type' => $template->database_type,
            'configuration' => $template->configuration,
            'version' => '1.0',
            'exported_at' => now()->toISOString(),
        ];
    }

    /**
     * Import a template configuration.
     */
    public function importTemplate(array $templateData): ImportTemplate
    {
        return $this->createTemplate([
            'name' => $templateData['name'].' (Imported)',
            'platform' => $templateData['platform'] ?? 'custom',
            'description' => $templateData['description'] ?? null,
            'database_type' => $templateData['database_type'] ?? 'postgresql',
            'configuration' => $templateData['configuration'] ?? ['queries' => []],
        ]);
    }

    /**
     * Create system templates (called during installation/setup).
     */
    public function createSystemTemplates(): void
    {

        // Custom Template
        $this->createCustomTemplate();
    }

    /**
     * Create the custom system template.
     */
    protected function createCustomTemplate(): ImportTemplate
    {
        $configuration = [
            'metadata' => [
                'platform' => 'custom',
                'version' => '1.0',
                'description' => 'Blank template for custom database imports',
            ],
            'settings' => [
                'instructions' => 'Use the query builder to create custom import queries for your database',
            ],
            'queries' => [], // Empty - user will build their own
        ];

        return ImportTemplate::updateOrCreate(
            ['platform' => 'custom', 'is_system' => true],
            [
                'name' => 'Custom Database',
                'platform' => 'custom',
                'description' => 'Blank template for custom database imports',
                'database_type' => 'postgresql',
                'configuration' => $configuration,
                'is_system' => true,
                'is_active' => true,
                'created_by' => null,
            ]
        );
    }
}
