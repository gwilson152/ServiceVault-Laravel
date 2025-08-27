<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportTemplateController extends Controller
{
    /**
     * Display a listing of import templates.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ImportTemplate::class);

        $query = ImportTemplate::query();

        // Filter by source type if specified
        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        // Legacy support: map database_type to source_type
        if ($request->filled('database_type')) {
            $query->where('source_type', 'database');
        }

        // Legacy support: map platform to source_type  
        if ($request->filled('platform')) {
            // Map common platform values to source_type
            $platformMap = [
                'freescout' => 'database',
                'freescout_api' => 'api',
                'csv' => 'file',
                'excel' => 'file',
            ];
            $sourceType = $platformMap[$request->platform] ?? 'database';
            $query->where('source_type', $sourceType);
        }

        // Filter by active status
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%'.$request->search.'%');
        }

        // Only show active templates by default
        if (! $request->has('active')) {
            $query->where('is_active', true);
        }

        $templates = $query->orderBy('source_type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $templates,
            'meta' => [
                'total' => $templates->count(),
                'filters_applied' => $request->only(['source_type', 'database_type', 'platform', 'active', 'search']),
            ],
        ]);
    }

    /**
     * Display the specified import template.
     */
    public function show(ImportTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        return response()->json($template);
    }
}
