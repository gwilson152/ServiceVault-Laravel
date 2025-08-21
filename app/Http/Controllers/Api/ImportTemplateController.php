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

        // Filter by database type if specified
        if ($request->filled('database_type')) {
            $query->where('database_type', $request->database_type);
        }

        // Filter by platform if specified
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        // Filter by active status
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Only show active templates by default
        if (!$request->has('active')) {
            $query->where('is_active', true);
        }

        $templates = $query->orderBy('platform')
                          ->orderBy('name')
                          ->get();

        return response()->json([
            'data' => $templates,
            'meta' => [
                'total' => $templates->count(),
                'filters_applied' => $request->only(['database_type', 'platform', 'active', 'search'])
            ]
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