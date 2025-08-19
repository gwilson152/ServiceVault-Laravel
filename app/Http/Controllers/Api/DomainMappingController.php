<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DomainMapping;
use App\Services\DomainAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DomainMappingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', DomainMapping::class);

        $mappings = DomainMapping::with(['account', 'roleTemplate'])
            ->byPriority()
            ->get();

        return response()->json([
            'data' => $mappings->map(function ($mapping) {
                return [
                    'id' => $mapping->id,
                    'domain_pattern' => $mapping->domain_pattern,
                    'account' => [
                        'id' => $mapping->account->id,
                        'name' => $mapping->account->name,
                    ],
                    'role_template' => $mapping->roleTemplate ? [
                        'id' => $mapping->roleTemplate->id,
                        'name' => $mapping->roleTemplate->name,
                    ] : null,
                    'is_active' => $mapping->is_active,
                    'priority' => $mapping->priority,
                    'description' => $mapping->description,
                    'example_domains' => $mapping->getExampleDomains(),
                    'created_at' => $mapping->created_at,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', DomainMapping::class);

        $validator = Validator::make($request->all(), [
            'domain_pattern' => 'required|string|max:255',
            'account_id' => 'required|exists:accounts,id',
            'role_template_id' => 'nullable|exists:role_templates,id',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:1000',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for duplicate domain pattern + account combination
        $exists = DomainMapping::where('domain_pattern', $request->domain_pattern)
            ->where('account_id', $request->account_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A mapping for this domain pattern and account already exists.',
            ], 422);
        }

        $mapping = DomainMapping::create($validator->validated());
        $mapping->load(['account', 'roleTemplate']);

        return response()->json([
            'message' => 'Domain mapping created successfully',
            'data' => $mapping,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(DomainMapping $domainMapping): JsonResponse
    {
        $this->authorize('view', $domainMapping);

        $domainMapping->load(['account', 'roleTemplate']);

        return response()->json([
            'data' => [
                'id' => $domainMapping->id,
                'domain_pattern' => $domainMapping->domain_pattern,
                'account' => [
                    'id' => $domainMapping->account->id,
                    'name' => $domainMapping->account->name,
                ],
                'role_template' => $domainMapping->roleTemplate ? [
                    'id' => $domainMapping->roleTemplate->id,
                    'name' => $domainMapping->roleTemplate->name,
                ] : null,
                'is_active' => $domainMapping->is_active,
                'priority' => $domainMapping->priority,
                'description' => $domainMapping->description,
                'example_domains' => $domainMapping->getExampleDomains(),
                'created_at' => $domainMapping->created_at,
                'updated_at' => $domainMapping->updated_at,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DomainMapping $domainMapping): JsonResponse
    {
        $this->authorize('update', $domainMapping);

        $validator = Validator::make($request->all(), [
            'domain_pattern' => 'sometimes|required|string|max:255',
            'account_id' => 'sometimes|required|exists:accounts,id',
            'role_template_id' => 'nullable|exists:role_templates,id',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0|max:1000',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for duplicate if domain_pattern or account_id is being updated
        if ($request->has('domain_pattern') || $request->has('account_id')) {
            $domainPattern = $request->get('domain_pattern', $domainMapping->domain_pattern);
            $accountId = $request->get('account_id', $domainMapping->account_id);

            $exists = DomainMapping::where('domain_pattern', $domainPattern)
                ->where('account_id', $accountId)
                ->where('id', '!=', $domainMapping->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'A mapping for this domain pattern and account already exists.',
                ], 422);
            }
        }

        $domainMapping->update($validator->validated());
        $domainMapping->load(['account', 'roleTemplate']);

        return response()->json([
            'message' => 'Domain mapping updated successfully',
            'data' => $domainMapping,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DomainMapping $domainMapping): JsonResponse
    {
        $this->authorize('delete', $domainMapping);

        $domainMapping->delete();

        return response()->json([
            'message' => 'Domain mapping deleted successfully',
        ]);
    }

    /**
     * Preview assignment for an email address.
     */
    public function preview(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DomainMapping::class);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $assignmentService = new DomainAssignmentService;
        $preview = $assignmentService->previewAssignmentForEmail($request->email);

        return response()->json([
            'data' => $preview,
        ]);
    }

    /**
     * Validate system requirements for domain assignment.
     */
    public function validateRequirements(): JsonResponse
    {
        $this->authorize('viewAny', DomainMapping::class);

        $assignmentService = new DomainAssignmentService;
        $validation = $assignmentService->validateAssignmentRequirements();

        return response()->json([
            'data' => $validation,
        ]);
    }
}
