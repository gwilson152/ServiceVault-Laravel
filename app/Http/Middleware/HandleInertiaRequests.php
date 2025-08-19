<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? (new UserResource($request->user()))->resolve($request) : null,
            ],
            'settings' => $this->getSystemSettings(),
            'csrf_token' => csrf_token(),
        ];
    }

    /**
     * Get system settings for frontend consumption
     */
    private function getSystemSettings(): array
    {
        try {
            // Get essential system settings for formatting
            $essentialSettings = [
                'date_format',
                'time_format',
                'currency',
                'language',
                'timezone',
            ];

            $settings = [];
            foreach ($essentialSettings as $key) {
                $value = Setting::getValue($key);
                if ($value !== null) {
                    $settings[$key] = $value;
                }
            }

            // Set defaults if not configured
            return array_merge([
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'currency' => 'USD',
                'language' => 'en',
                'timezone' => 'UTC',
            ], $settings);

        } catch (\Exception $e) {
            // Return defaults if database is not available (during setup)
            return [
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'currency' => 'USD',
                'language' => 'en',
                'timezone' => 'UTC',
            ];
        }
    }
}
