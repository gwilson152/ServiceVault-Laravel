# Settings API

System configuration management including email settings, timer configuration, and system preferences.

## Overview

The Settings API provides comprehensive configuration management for Service Vault, including:
- Email configuration (SMTP/IMAP) with OAuth and app password support
- Timer system settings and preferences
- User management automation settings
- System-wide configuration options

## Authentication

All settings endpoints require the `system.configure` permission or equivalent administrative access.

**Required Permissions:**
- `system.configure` - System configuration access
- Alternative permissions: `admin.read`, `admin.write`

## Endpoints

### Get All Settings
Retrieve all system settings organized by category.

```http
GET /api/settings
```

**Response:**
```json
{
  "data": {
    "system": {
      "company_name": "Service Company",
      "company_email": "admin@company.com",
      "timezone": "America/New_York",
      "currency": "USD",
      "date_format": "Y-m-d",
      "time_format": "H:i"
    },
    "email": {
      "smtp_host": "smtp-mail.outlook.com",
      "smtp_port": "587",
      "smtp_encryption": "tls",
      "smtp_username": "support@company.com",
      "from_address": "support@company.com",
      "from_name": "Company Support",
      "enable_email_to_ticket": true,
      "imap_host": "outlook.office365.com",
      "imap_port": "993",
      "imap_encryption": "ssl"
    },
    "timer": {
      "default_auto_stop": false,
      "allow_concurrent_timers": true,
      "sync_interval_seconds": 5
    }
  }
}
```

### Update System Settings
Update company information and system configuration.

```http
PUT /api/settings/system
```

**Request Body:**
```json
{
  "company_name": "Updated Company Name",
  "company_email": "admin@company.com",
  "company_website": "https://company.com",
  "timezone": "America/New_York",
  "currency": "USD",
  "date_format": "Y-m-d",
  "time_format": "H:i",
  "enable_real_time": true,
  "enable_notifications": true,
  "max_users": 100
}
```

**Validation Rules:**
- `company_name`: string, max 255 characters
- `company_email`: valid email, max 255 characters  
- `company_website`: valid URL or null
- `timezone`: valid timezone string
- `currency`: 3-character currency code (USD, EUR, etc.)
- `enable_real_time`: boolean
- `max_users`: integer, 1-10000

**Response:**
```json
{
  "message": "System settings updated successfully"
}
```

### Update Email Settings
Configure SMTP and IMAP email settings.

```http
PUT /api/settings/email
```

**Request Body:**
```json
{
  "smtp_host": "smtp-mail.outlook.com",
  "smtp_port": "587",
  "smtp_username": "support@company.com",
  "smtp_password": "app-password-or-null",
  "smtp_encryption": "tls",
  "from_address": "support@company.com",
  "from_name": "Company Support",
  
  "enable_email_to_ticket": true,
  "imap_host": "outlook.office365.com",
  "imap_port": "993",
  "imap_username": "support@company.com",
  "imap_password": "app-password-or-null",
  "imap_encryption": "ssl",
  "imap_folder": "INBOX",
  "email_check_interval": "5"
}
```

**Validation Notes:**
- All fields are optional for partial updates
- Passwords can be null (for OAuth configurations)
- Settings are saved immediately without validation
- Use test endpoints to verify configuration

**Response:**
```json
{
  "message": "Email settings saved successfully"
}
```

### Test SMTP Configuration
Validate SMTP settings by sending a test email.

```http
POST /api/settings/email/test-smtp
```

**Request Body:**
```json
{
  "test_email": "admin@company.com",
  "smtp_host": "smtp-mail.outlook.com",
  "smtp_port": 587,
  "smtp_username": "support@company.com",
  "smtp_password": "app-password",
  "smtp_encryption": "tls",
  "from_address": "support@company.com",
  "from_name": "Company Support"
}
```

**Validation Rules:**
- `test_email`: required, valid email address
- `smtp_host`: required string
- `smtp_port`: required integer
- `smtp_username`: optional string (null for no auth)
- `smtp_password`: optional string (null for no auth)
- `smtp_encryption`: nullable, one of: 'tls', 'ssl', or empty string
- `from_address`: required valid email
- `from_name`: optional string

**Success Response:**
```json
{
  "success": true,
  "message": "Test email sent successfully! Check your inbox."
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "SMTP test failed: Connection refused"
}
```

### Test IMAP Configuration
Validate IMAP settings and connection.

```http
POST /api/settings/email/test-imap
```

**Request Body:**
```json
{
  "imap_host": "outlook.office365.com",
  "imap_port": 993,
  "imap_username": "support@company.com",
  "imap_password": "app-password",
  "imap_encryption": "ssl",
  "imap_folder": "INBOX"
}
```

**Validation Rules:**
- `imap_host`: required string
- `imap_port`: required integer
- `imap_username`: required string
- `imap_password`: required string
- `imap_encryption`: nullable, one of: 'tls', 'ssl', or empty string
- `imap_folder`: optional string, defaults to "INBOX"

**Success Response:**
```json
{
  "success": true,
  "message": "IMAP connection successful!",
  "details": {
    "total_messages": 42,
    "unread_messages": 3
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "IMAP test failed: Invalid credentials"
}
```

### Get Ticket Configuration
Retrieve ticket statuses, categories, and workflow settings.

```http
GET /api/settings/ticket-config
```

**Required Permissions:** `system.configure`, `tickets.admin`, or `admin.read`

**Response:**
```json
{
  "data": {
    "statuses": [
      {
        "id": "uuid",
        "name": "Open",
        "color": "#059669",
        "is_active": true,
        "sort_order": 1
      }
    ],
    "categories": [
      {
        "id": "uuid", 
        "name": "Technical Support",
        "description": "Technical issues and questions",
        "is_active": true
      }
    ],
    "workflow_transitions": {
      "open": ["in_progress", "closed"],
      "in_progress": ["open", "resolved", "closed"]
    }
  }
}
```

### Get Billing Configuration
Retrieve billing rates and addon templates.

```http
GET /api/settings/billing-config
```

**Required Permissions:** `system.configure`, `billing.manage`, or `admin.read`

**Response:**
```json
{
  "data": {
    "billing_rates": [
      {
        "id": "uuid",
        "name": "Standard Rate",
        "rate": "150.00",
        "is_active": true,
        "account": {
          "id": "uuid",
          "name": "Client Company"
        }
      }
    ],
    "addon_templates": [
      {
        "id": "uuid",
        "name": "Server Setup",
        "description": "Initial server configuration",
        "category": "service",
        "default_price": "500.00",
        "default_quantity": "1.00",
        "is_active": true
      }
    ],
    "addon_categories": {
      "product": "Product",
      "service": "Service",
      "license": "License"
    }
  }
}
```

### Update Timer Settings
Configure timer behavior and synchronization.

```http
PUT /api/settings/timer
```

**Request Body:**
```json
{
  "default_auto_stop": false,
  "allow_concurrent_timers": true,
  "sync_interval_seconds": 5,
  "auto_commit_on_stop": false,
  "require_description": true,
  "default_billable": true
}
```

**Validation Rules:**
- All fields are boolean except `sync_interval_seconds`
- `sync_interval_seconds`: integer, 1-60 seconds

**Response:**
```json
{
  "message": "Timer settings updated successfully"
}
```

### Get User Management Settings
Retrieve user automation and role settings.

```http
GET /api/settings/user-management
```

**Response:**
```json
{
  "data": {
    "accounts": [
      {
        "id": "uuid",
        "name": "Service Provider",
        "account_type": "service_provider"
      }
    ],
    "role_templates": [
      {
        "id": "uuid",
        "name": "Account Manager",
        "context": "account_user"
      }
    ],
    "auto_user_settings": {
      "enable_auto_user_creation": false,
      "default_account_for_new_users": null,
      "require_email_verification": true
    }
  }
}
```

### Update User Management Settings
Configure automatic user creation and assignment.

```http
PUT /api/settings/user-management
```

**Request Body:**
```json
{
  "enable_auto_user_creation": true,
  "default_account_for_new_users": "account-uuid",
  "default_role_template_for_new_users": "role-uuid",
  "require_email_verification": true,
  "require_admin_approval": false
}
```

**Response:**
```json
{
  "message": "User management settings updated successfully"
}
```

## Email Provider Configuration

### Microsoft 365 OAuth Setup

For OAuth authentication, configure application registration:

```json
{
  "smtp_host": "smtp-mail.outlook.com",
  "smtp_port": "587",
  "smtp_encryption": "tls",
  "smtp_username": "user@company.com",
  "smtp_password": null,
  "oauth_provider": "microsoft365",
  "oauth_client_id": "your-client-id",
  "oauth_tenant_id": "your-tenant-id"
}
```

### App Password Authentication

For providers requiring app passwords:

```json
{
  "smtp_host": "smtp.gmail.com",
  "smtp_port": "587", 
  "smtp_encryption": "tls",
  "smtp_username": "user@gmail.com",
  "smtp_password": "16-char-app-password"
}
```

## Error Handling

### Common Error Codes

**403 Forbidden:**
```json
{
  "message": "Unauthorized. Required permission: system.configure"
}
```

**422 Validation Error:**
```json
{
  "message": "Validation failed",
  "errors": {
    "smtp_port": ["The smtp port must be an integer."],
    "from_address": ["The from address must be a valid email address."]
  }
}
```

**400 Configuration Test Failed:**
```json
{
  "success": false,
  "message": "SMTP test failed: Authentication failed"
}
```

## Rate Limiting

Settings API endpoints are subject to rate limiting:
- **GET requests**: 120 per minute
- **PUT/POST requests**: 60 per minute per user
- **Test endpoints**: 10 per minute (to prevent abuse)

## Integration Examples

### Frontend Email Configuration

```javascript
// Test SMTP configuration
async function testSmtpConfig(config) {
  const response = await axios.post('/api/settings/email/test-smtp', {
    test_email: 'admin@company.com',
    smtp_host: config.smtp_host,
    smtp_port: parseInt(config.smtp_port),
    smtp_username: config.smtp_username,
    smtp_password: config.smtp_password,
    smtp_encryption: config.smtp_encryption,
    from_address: config.from_address,
    from_name: config.from_name
  })
  
  return response.data
}

// Save email settings
async function saveEmailSettings(settings) {
  await axios.put('/api/settings/email', settings)
  console.log('Email settings saved successfully')
}
```

### Laravel Backend Integration

```php
// Get email settings in a service class
class EmailService {
    public function getSmtpConfig(): array {
        $settings = Setting::getByType('email');
        return [
            'host' => $settings['smtp_host'] ?? '',
            'port' => $settings['smtp_port'] ?? 587,
            'encryption' => $settings['smtp_encryption'] ?? 'tls',
            'username' => $settings['smtp_username'] ?? '',
            'password' => $settings['smtp_password'] ?? '',
        ];
    }
}
```

The Settings API provides comprehensive configuration management with built-in validation, testing capabilities, and integration with Service Vault's permission system.