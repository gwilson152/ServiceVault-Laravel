<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class UserPreferenceController extends Controller
{
    /**
     * Display a listing of the user's preferences.
     */
    public function index(Request $request): JsonResponse
    {
        $preferences = $request->user()->preferences()
            ->get()
            ->keyBy('key')
            ->map->value;

        return response()->json(['data' => $preferences]);
    }

    /**
     * Store or update a preference.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:100',
            'value' => 'required',
        ]);

        $request->user()->setPreference(
            $request->input('key'),
            $request->input('value')
        );

        return response()->json([
            'message' => 'Preference saved successfully',
            'data' => [
                'key' => $request->input('key'),
                'value' => $request->input('value')
            ]
        ]);
    }

    /**
     * Display the specified preference.
     */
    public function show(Request $request, string $key): JsonResponse
    {
        $value = $request->user()->getPreference($key);

        if ($value === null) {
            return response()->json([
                'message' => 'Preference not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'key' => $key,
                'value' => $value
            ]
        ]);
    }

    /**
     * Update the specified preference.
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value' => 'required',
        ]);

        $request->user()->setPreference($key, $request->input('value'));

        return response()->json([
            'message' => 'Preference updated successfully',
            'data' => [
                'key' => $key,
                'value' => $request->input('value')
            ]
        ]);
    }

    /**
     * Remove the specified preference.
     */
    public function destroy(Request $request, string $key): JsonResponse
    {
        UserPreference::remove($request->user()->id, $key);

        return response()->json([
            'message' => 'Preference deleted successfully'
        ]);
    }

    /**
     * Bulk update preferences.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.key' => 'required|string|max:100',
            'preferences.*.value' => 'required',
        ]);

        $user = $request->user();
        $savedPreferences = [];

        foreach ($request->input('preferences') as $preference) {
            $user->setPreference($preference['key'], $preference['value']);
            $savedPreferences[$preference['key']] = $preference['value'];
        }

        return response()->json([
            'message' => 'Preferences updated successfully',
            'data' => $savedPreferences
        ]);
    }
}