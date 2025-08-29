# Configuration API

REST API endpoints for system configuration backup and restore operations. All endpoints require Super Administrator permissions.

## Authentication

All configuration endpoints require:
- **Authentication**: Valid session or API token
- **Authorization**: Super Administrator role (`is_super_admin = true`)
- **Password Confirmation**: Required for all import operations

## Export Configuration

### Export Selected Categories
```http
POST /api/settings/export
Content-Type: application/json

{
  "categories": ["system", "email", "timer"],
  "include_metadata": true
}
```

**Available Categories**:
- `system` - System settings (timezone, currency, company info)
- `email` - Email system configuration (SMTP, IMAP, processing rules)
- `timer` - Timer system preferences and limits
- `advanced` - Advanced system settings and debug options
- `tax` - Tax rates and configuration
- `tickets` - Ticket statuses, categories, priorities, workflow transitions
- `billing` - Billing rates and addon templates
- `import-profiles` - Import templates and profiles (credentials masked)

**Request Parameters**:
- `categories` (array, required): List of categories to export
- `include_metadata` (boolean, optional): Include export metadata (default: true)

**Response**:
```json
{
  "success": true,
  "data": {
    "system": {
      "timezone": "America/New_York",
      "currency": "USD",
      "company_name": "Acme Corp"
    },
    "email": {
      "system_active": true,
      "outgoing_host": "smtp.example.com",
      "from_address": "noreply@example.com"
    },
    "_metadata": {
      "exported_at": "2025-08-29T10:30:00Z",
      "exported_by": "Admin User",
      "exported_by_email": "admin@example.com",
      "system_version": "1.0.0",
      "categories": ["system", "email"],
      "total_categories": 2
    }
  },
  "filename": "servicevault_config_export_2025-08-29_10-30-00.json",
  "message": "Configuration exported successfully"
}
```

**Security Notes**:
- **Credential Masking**: Passwords and API keys in import profiles are replaced with `***MASKED***`
- **Audit Logging**: All export operations are logged with user details
- **File Download**: Client should save response data as JSON file with provided filename

## Import Configuration

### Validate Configuration File
```http
POST /api/settings/validate-import
Content-Type: multipart/form-data

config_file: [JSON file upload]
```

**Response**:
```json
{
  "success": true,
  "available_categories": [
    {
      "id": "system",
      "name": "System",
      "count": 5
    },
    {
      "id": "import-profiles",
      "name": "Import Profiles", 
      "count": 7
    }
  ],
  "metadata": {
    "exported_at": "2025-08-29T10:30:00Z",
    "exported_by": "Admin User",
    "system_version": "1.0.0"
  },
  "total_categories": 2,
  "message": "Configuration file validated successfully"
}
```

### Preview Import Changes
```http
POST /api/settings/preview-import
Content-Type: multipart/form-data

config_file: [JSON file upload]
categories: ["system", "email"]
```

**Response**:
```json
{
  "success": true,
  "preview": {
    "system": {
      "additions": [
        {
          "key": "date_format",
          "new_value": "Y-m-d"
        }
      ],
      "modifications": [
        {
          "key": "timezone",
          "current_value": "UTC",
          "new_value": "America/New_York"
        }
      ],
      "unchanged": [
        {
          "key": "currency",
          "value": "USD"
        }
      ]
    }
  },
  "message": "Import preview generated successfully"
}
```

### Apply Configuration Import
```http
POST /api/settings/import
Content-Type: multipart/form-data

config_file: [JSON file upload]
categories: ["system", "email"]
password: "admin_password"
overwrite_existing: true
```

**Request Parameters**:
- `config_file` (file, required): JSON configuration file
- `categories` (array, required): Categories to import
- `password` (string, required): Administrator password for confirmation
- `overwrite_existing` (boolean, optional): Overwrite existing settings (default: true)

**Response**:
```json
{
  "success": true,
  "message": "Configuration imported successfully",
  "imported_categories": ["system", "email"]
}
```

## Error Responses

### Authentication Error
```http
HTTP/1.1 403 Forbidden
```
```json
{
  "message": "Access denied. Only Super Administrators can export configuration."
}
```

### Validation Error
```http
HTTP/1.1 422 Unprocessable Entity
```
```json
{
  "message": "Validation failed",
  "errors": {
    "categories": ["At least one category must be selected"],
    "password": ["Password is required"]
  }
}
```

### Invalid Password
```http
HTTP/1.1 422 Unprocessable Entity
```
```json
{
  "message": "Invalid password. Import cancelled."
}
```

### File Validation Error
```http
HTTP/1.1 422 Unprocessable Entity
```
```json
{
  "success": false,
  "message": "Invalid JSON file: Syntax error"
}
```

## Usage Examples

### Complete Export/Import Workflow

1. **Export Configuration**:
```javascript
const exportResponse = await fetch('/api/settings/export', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrfToken
  },
  body: JSON.stringify({
    categories: ['system', 'email', 'timer'],
    include_metadata: true
  })
});

const exportData = await exportResponse.json();
// Save exportData.data as JSON file
```

2. **Validate Import File**:
```javascript
const formData = new FormData();
formData.append('config_file', jsonFile);

const validateResponse = await fetch('/api/settings/validate-import', {
  method: 'POST',
  headers: { 'X-CSRF-TOKEN': csrfToken },
  body: formData
});

const validation = await validateResponse.json();
// Show available categories to user
```

3. **Preview Import Changes**:
```javascript
const formData = new FormData();
formData.append('config_file', jsonFile);
formData.append('categories', JSON.stringify(['system', 'email']));

const previewResponse = await fetch('/api/settings/preview-import', {
  method: 'POST',
  headers: { 'X-CSRF-TOKEN': csrfToken },
  body: formData
});

const preview = await previewResponse.json();
// Show preview diff to user
```

4. **Apply Import**:
```javascript
const formData = new FormData();
formData.append('config_file', jsonFile);
formData.append('categories', JSON.stringify(['system', 'email']));
formData.append('password', adminPassword);
formData.append('overwrite_existing', '1');

const importResponse = await fetch('/api/settings/import', {
  method: 'POST',
  headers: { 'X-CSRF-TOKEN': csrfToken },
  body: formData
});

const result = await importResponse.json();
// Handle import result
```

## Security Considerations

### Credential Protection
- **Automatic Masking**: All passwords and API keys in import profiles are masked during export
- **Import Warnings**: Users are warned that credentials must be manually reconfigured after import
- **Audit Trail**: All operations logged with user attribution and IP addresses

### Access Control
- **Super Admin Only**: All endpoints restricted to users with `is_super_admin` flag
- **Password Confirmation**: Import operations require current user password
- **Session Validation**: Standard Laravel authentication required

### Data Validation
- **File Type Validation**: Only JSON files accepted (max 10MB)
- **Structure Validation**: JSON structure validated before processing
- **Category Validation**: Only valid categories accepted
- **Data Sanitization**: All imported data sanitized and validated

## Rate Limits

- **Export Operations**: 10 requests per hour per user
- **Import Operations**: 5 requests per hour per user  
- **File Upload**: 10MB maximum file size
- **Timeout**: 60 seconds maximum processing time

---

*Last Updated: August 29, 2025*