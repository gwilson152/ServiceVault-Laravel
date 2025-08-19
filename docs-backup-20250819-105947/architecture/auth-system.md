# Authentication & Authorization System Architecture

Service Vault implements a comprehensive hybrid authentication system supporting both session-based web access and token-based API access with granular permission control.

## Overview

### Hybrid Authentication Architecture
- **Session Authentication**: Laravel Breeze for web dashboard access
- **Token Authentication**: Laravel Sanctum for API/mobile access
- **ABAC Authorization**: Attribute-Based Access Control with role templates
- **Domain-Based Assignment**: Automatic user-account mapping via email patterns

### Core Components
1. **Authentication Layer**: Laravel Sanctum + Breeze integration
2. **Authorization Layer**: ABAC with role templates and hierarchical permissions
3. **Token Management**: Granular abilities with predefined scopes
4. **Domain Mapping**: Automatic user assignment based on email domains

## Authentication Layer

### Laravel Sanctum Configuration

#### Sanctum Configuration (`config/sanctum.php`)
```php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
    ))),
    
    'guard' => ['web'],
    'expiration' => env('SANCTUM_EXPIRATION', 525600), // 1 year default
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'sv_'),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
```

#### User Model Integration
```php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    protected $fillable = [
        'name', 'email', 'password', 'current_account_id'
    ];
    
    protected $hidden = [
        'password', 'remember_token'
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    // ABAC Permission Integration
    public function hasPermissionForAccount(string $permission, Account $account): bool
    {
        // Check token abilities if API authenticated
        if ($this->currentAccessToken()) {
            return $this->tokenCan($this->mapPermissionToAbility($permission));
        }
        
        // Default ABAC permissions for web users
        return $this->roleTemplates()
            ->whereHas('accounts', fn($q) => $q->where('accounts.id', $account->id))
            ->get()
            ->some(fn($template) => $template->hasPermission($permission));
    }
    
    private function mapPermissionToAbility(string $permission): string
    {
        $mapping = [
            'timers.view' => 'timers:read',
            'timers.create' => 'timers:write',
            'timers.update' => 'timers:write',
            'timers.delete' => 'timers:delete',
            // ... more mappings
        ];
        
        return $mapping[$permission] ?? 'admin:read';
    }
}
```

### Session Authentication (Web Dashboard)

#### Laravel Breeze Integration
```php
// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
```

#### Registration with Domain-Based Assignment
```php
class RegisteredUserController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Automatic domain-based account assignment
        $account = app(DomainAssignmentService::class)->assignUserToAccount($user->email);
        if ($account) {
            $user->update(['current_account_id' => $account->id]);
            $user->accounts()->attach($account->id);
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
```

## Token Authentication System

### Token Abilities Architecture

#### TokenAbilityService
```php
class TokenAbilityService
{
    public const ABILITIES = [
        // Timer Management
        'timers:read' => 'View timer data',
        'timers:write' => 'Create and update timers',
        'timers:delete' => 'Delete timers',
        'timers:sync' => 'Cross-device timer synchronization',
        
        // Time Entry Management
        'time-entries:read' => 'View time entry data',
        'time-entries:write' => 'Create and modify time entries',
        'time-entries:approve' => 'Approve time entries (manager level)',
        'time-entries:delete' => 'Delete time entries',
        
        // Project Management
        'projects:read' => 'View project information',
        'projects:write' => 'Create and modify projects',
        'projects:assign' => 'Assign users to projects',
        
        // Account Management
        'accounts:read' => 'View account data',
        'accounts:write' => 'Modify account settings',
        'accounts:billing' => 'Access billing information',
        
        // User Management
        'users:read' => 'View user information',
        'users:write' => 'Modify user data',
        'users:invite' => 'Send user invitations',
        
        // Reports & Analytics
        'reports:read' => 'View reports and analytics',
        'reports:export' => 'Export report data',
        
        // Billing Management
        'billing:read' => 'View billing data',
        'billing:write' => 'Modify billing settings',
        'billing:rates' => 'Manage billing rates',
        
        // Administrative
        'admin:read' => 'View administrative data',
        'admin:write' => 'Administrative operations'
    ];
    
    public const SCOPES = [
        'employee' => [
            'timers:read', 'timers:write', 'timers:sync',
            'time-entries:read', 'time-entries:write',
            'projects:read'
        ],
        'manager' => [
            'timers:read', 'timers:write', 'timers:sync',
            'time-entries:read', 'time-entries:write', 'time-entries:approve',
            'projects:read', 'projects:write', 'projects:assign',
            'reports:read', 'users:read'
        ],
        'mobile-app' => [
            'timers:read', 'timers:write', 'timers:sync',
            'time-entries:read', 'time-entries:write',
            'projects:read', 'accounts:read', 'users:read'
        ],
        'admin' => ['*']
    ];
    
    public static function getAbilitiesForScope(string $scope): array
    {
        return self::SCOPES[$scope] ?? [];
    }
    
    public static function validateAbilities(array $abilities): array
    {
        return array_intersect($abilities, array_keys(self::ABILITIES));
    }
}
```

### Token Management API

#### Token Controller
```php
class TokenController extends Controller
{
    public function index(Request $request)
    {
        return TokenResource::collection(
            $request->user()->tokens()->paginate()
        );
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array'],
            'abilities.*' => ['string', 'in:' . implode(',', array_keys(TokenAbilityService::ABILITIES))],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'password' => ['required', 'current_password']
        ]);
        
        $abilities = TokenAbilityService::validateAbilities($request->abilities);
        
        $token = $request->user()->createToken(
            $request->name,
            $abilities,
            $request->expires_at ? Carbon::parse($request->expires_at) : null
        );
        
        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }
    
    public function createScoped(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'string', 'in:employee,manager,mobile-app,admin'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'password' => ['required', 'current_password']
        ]);
        
        $abilities = TokenAbilityService::getAbilitiesForScope($request->scope);
        
        $token = $request->user()->createToken(
            $request->name,
            $abilities,
            $request->expires_at ? Carbon::parse($request->expires_at) : null
        );
        
        return response()->json([
            'token' => $token->plainTextToken,
            'scope' => $request->scope,
            'abilities' => $abilities,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }
}
```

## Domain-Based User Assignment

### Domain Mapping System

#### DomainMapping Model
```php
class DomainMapping extends Model
{
    protected $fillable = [
        'domain_pattern',
        'account_id',
        'priority',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer'
    ];
    
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    
    public function matchesDomain(string $emailDomain): bool
    {
        $pattern = $this->domain_pattern;
        
        // Handle wildcard patterns (*.company.com)
        if (str_contains($pattern, '*')) {
            $regexPattern = preg_quote($pattern, '/');
            $regexPattern = str_replace('\\*', '[^.]*', $regexPattern);
            $regexPattern = '/^' . $regexPattern . '$/i';
            return preg_match($regexPattern, $emailDomain) === 1;
        }
        
        // Exact domain match
        return strcasecmp($pattern, $emailDomain) === 0;
    }
}
```

#### Domain Assignment Service
```php
class DomainAssignmentService
{
    public function assignUserToAccount(string $email): ?Account
    {
        $emailDomain = $this->extractDomain($email);
        
        // Get domain mappings ordered by priority
        $mappings = DomainMapping::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
        
        foreach ($mappings as $mapping) {
            if ($mapping->matchesDomain($emailDomain)) {
                return $mapping->account;
            }
        }
        
        // Fallback to default account if configured
        return $this->getDefaultAccount();
    }
    
    public function previewAssignment(string $email): array
    {
        $emailDomain = $this->extractDomain($email);
        $mappings = DomainMapping::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();
            
        foreach ($mappings as $mapping) {
            if ($mapping->matchesDomain($emailDomain)) {
                return [
                    'matched_mapping' => $mapping,
                    'would_assign_to' => $mapping->account
                ];
            }
        }
        
        return [
            'matched_mapping' => null,
            'would_assign_to' => $this->getDefaultAccount()
        ];
    }
    
    private function extractDomain(string $email): string
    {
        return strtolower(substr(strrchr($email, '@'), 1));
    }
}
```

## Authorization Layer (ABAC)

### Role Template System

#### RoleTemplate Model
```php
class RoleTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'permissions',
        'is_system_role',
        'is_default'
    ];
    
    protected $casts = [
        'permissions' => 'array',
        'is_system_role' => 'boolean',
        'is_default' => 'boolean'
    ];
    
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('account_id')
            ->withTimestamps();
    }
    
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
    
    public function grantPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }
}
```

### Permission System Integration

#### Laravel Policy Integration
```php
class TimerPolicy
{
    public function view(User $user, Timer $timer): bool
    {
        // Token-based authentication
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:read') && 
                   $this->userHasAccessToAccount($user, $timer->account);
        }
        
        // Session-based authentication with ABAC
        return $user->hasPermissionForAccount('timers.view', $timer->account);
    }
    
    public function create(User $user): bool
    {
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:write');
        }
        
        return $user->roleTemplates()
            ->whereHas('permissions', fn($q) => $q->where('permission', 'timers.create'))
            ->exists();
    }
    
    public function update(User $user, Timer $timer): bool
    {
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:write') && 
                   ($timer->user_id === $user->id || $user->tokenCan('admin:write'));
        }
        
        return $timer->user_id === $user->id || 
               $user->hasPermissionForAccount('timers.update.any', $timer->account);
    }
    
    private function userHasAccessToAccount(User $user, Account $account): bool
    {
        return $user->accounts()->where('accounts.id', $account->id)->exists() ||
               $user->accounts()->whereIn('accounts.id', $account->descendants()->pluck('id'))->exists();
    }
}
```

## Security Architecture

### Authentication Security
- **Password Hashing**: Bcrypt with configurable rounds
- **Session Management**: Secure session cookies with CSRF protection
- **Token Security**: Prefixed tokens with configurable expiration

### Authorization Security
- **Principle of Least Privilege**: Granular token abilities
- **Account Scoping**: Users only access permitted accounts
- **Hierarchical Permissions**: Account-based permission inheritance

### API Security
```php
// Rate limiting configuration
protected function configureRateLimiting()
{
    RateLimiter::for('api', function (Request $request) {
        return $request->user()
            ? Limit::perMinute(120)->by($request->user()->id)
            : Limit::perMinute(60)->by($request->ip());
    });
    
    RateLimiter::for('auth', function (Request $request) {
        return [
            Limit::perMinute(5)->by($request->email.$request->ip()),
            Limit::perMinute(20)->by($request->ip())
        ];
    });
}
```

## Monitoring & Analytics

### Authentication Metrics
- **Login Success/Failure Rates**: Track authentication attempts
- **Token Usage Patterns**: Monitor API token usage
- **Session Duration**: Web session analytics
- **Failed Authorization Attempts**: Security monitoring

### Performance Monitoring
```php
class AuthSystemHealthCheck
{
    public function check(): array
    {
        return [
            'active_sessions' => $this->getActiveSessionCount(),
            'active_tokens' => $this->getActiveTokenCount(),
            'failed_logins_last_hour' => $this->getFailedLoginCount(),
            'domain_mapping_coverage' => $this->getDomainMappingCoverage(),
            'average_auth_response_time' => $this->getAverageAuthTime()
        ];
    }
}
```

## Scalability Considerations

### Session Scaling
- **Redis Session Storage**: Distributed session management
- **Database Connection Pooling**: Efficient database usage
- **Load Balancer Integration**: Sticky sessions or session replication

### Token Management Scaling
- **Token Pruning**: Automatic cleanup of expired tokens
- **Caching**: Permission caching with appropriate TTL
- **Database Optimization**: Indexed queries for token validation