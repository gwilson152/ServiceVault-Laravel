# Business Account System Architecture

Comprehensive B2B customer relationship management system for Service Vault.

## Overview

The Business Account System provides comprehensive customer relationship management capabilities designed for B2B service delivery. Each account represents a business entity (customer, prospect, partner, or internal organization) with complete business information, contact details, and hierarchical relationships.

## Account Types

### Customer Accounts

-   **Primary Business Relationships**: Organizations that purchase services
-   **Complete Business Information**: Company details, contact information, addresses
-   **Billing Integration**: Tax IDs, billing addresses, payment processing
-   **Service History**: Ticket history, time tracking, service delivery records

### Prospect Accounts

-   **Potential Customers**: Organizations in sales pipeline
-   **Lead Management**: Contact information, notes, qualification status
-   **Conversion Tracking**: Progress from prospect to customer

### Partner Accounts

-   **Business Partners**: Vendors, contractors, referral partners
-   **Collaboration Management**: Joint projects, referral tracking
-   **Partnership Terms**: Contract details, commission structures

### Internal Accounts

-   **Service Provider Organizations**: Your company and divisions
-   **Department Management**: Different business units or locations
-   **Internal Operations**: Company hierarchy, internal projects

## Account Data Structure

### Core Business Information

```php
'name'              // Account display name
'company_name'      // Legal business name
'account_type'      // customer, prospect, partner, internal
'description'       // Business description
'parent_id'         // Hierarchy parent account
'is_active'         // Account status
```

### Contact Information

```php
'contact_person'    // Primary contact name
'email'            // Primary email address
'phone'            // Primary phone number
'website'          // Company website URL
```

### Address Information

```php
'address'          // Street address
'city'             // City
'state'            // State/Province
'postal_code'      // Zip/Postal code
'country'          // Country
```

### Billing Information

```php
'billing_address'       // Billing street address
'billing_city'          // Billing city
'billing_state'         // Billing state/province
'billing_postal_code'   // Billing zip/postal code
'billing_country'       // Billing country
'tax_id'               // Tax ID/VAT number
```

### Business Details

```php
'notes'             // Internal notes
'settings'          // Account-specific settings
'theme_settings'    // Custom branding/theming
```

## Account Hierarchy System

### Parent-Child Relationships

-   **Unlimited Depth**: Support for complex corporate structures
-   **Permission Inheritance**: Access rights cascade through hierarchy
-   **Context Awareness**: Users can access parent and subsidiary accounts
-   **Visual Hierarchy**: Tree-view interface showing organizational structure

### Hierarchy Use Cases

-   **Corporate Structures**: Parent company → subsidiaries → divisions
-   **Geographic Organization**: Global company → regional offices → local branches
-   **Service Delivery**: Customer → departments → projects
-   **Internal Organization**: Service provider → teams → individuals

## Database Schema

### Accounts Table

```sql
CREATE TABLE accounts (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    company_name VARCHAR(255),
    account_type ENUM('customer', 'prospect', 'partner', 'internal') DEFAULT 'customer',
    description TEXT,
    parent_id BIGINT REFERENCES accounts(id),

    -- Contact Information
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    website VARCHAR(255),

    -- Address Information
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),

    -- Billing Information
    billing_address TEXT,
    billing_city VARCHAR(100),
    billing_state VARCHAR(100),
    billing_postal_code VARCHAR(20),
    billing_country VARCHAR(100),
    tax_id VARCHAR(100),

    -- Business Details
    notes TEXT,
    settings JSON,
    theme_settings JSON,
    is_active BOOLEAN DEFAULT true,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_account_type (account_type),
    INDEX idx_parent_id (parent_id),
    INDEX idx_active (is_active),
    INDEX idx_company_search (company_name, name)
);
```

### Account Relationships

```sql
-- Many-to-many user assignments
CREATE TABLE account_user (
    account_id BIGINT REFERENCES accounts(id),
    user_id BIGINT REFERENCES users(id),
    created_at TIMESTAMP,
    PRIMARY KEY (account_id, user_id)
);
```

## Account Management Interface

### Account List View

-   **Business-Focused Columns**: Company name, contact info, type, hierarchy level
-   **Search & Filtering**: Company name, contact person, email, phone, type
-   **Visual Indicators**: Account type badges, hierarchy indentation, status indicators
-   **Quick Actions**: View, edit, manage users, delete

### Account Form Interface

Organized in logical sections:

#### Basic Information

-   Account name and company name
-   Account type selection
-   Parent account hierarchy selection
-   Description

#### Contact Information

-   Primary contact person
-   Email address and phone number
-   Website URL

#### Address Information

-   Complete mailing address
-   City, state, postal code, country

#### Billing Information

-   Separate billing address (with copy function)
-   Tax ID/VAT number
-   "Copy from address above" convenience feature

#### Business Details

-   Internal notes
-   Active/inactive status toggle

### Account Management Features

-   **Hierarchy Visualization**: Tree view showing parent-child relationships
-   **User Assignment**: Assign users to accounts with role-based permissions
-   **Permission Context**: Account-scoped access controls
-   **Activity History**: Track changes and service history
-   **Integration Points**: Service tickets, time tracking, billing

## API Endpoints

### Account Management

```bash
# Standard CRUD operations
GET    /api/accounts                    # List accounts with business info
POST   /api/accounts                    # Create account with validation
GET    /api/accounts/{account}          # Get account with hierarchy
PUT    /api/accounts/{account}          # Update account information
DELETE /api/accounts/{account}          # Delete (with safety checks)

# Hierarchy and Selection
GET    /api/accounts/selector/hierarchical  # Hierarchical account selector
GET    /api/accounts?with_hierarchy=true    # Accounts with parent/children
```

### Account Validation

```php
// Creation/Update Validation Rules
'name' => 'required|string|max:255',
'company_name' => 'nullable|string|max:255',
'account_type' => 'required|in:customer,prospect,partner,internal',
'parent_id' => 'nullable|exists:accounts,id',
'email' => 'nullable|email|max:255',
'phone' => 'nullable|string|max:50',
'website' => 'nullable|url|max:255',
// ... address and billing fields
'tax_id' => 'nullable|string|max:100',
'is_active' => 'boolean'
```

## Account Context System

### Context Switching

-   **Service Provider Mode**: Can access all customer accounts
-   **Account User Mode**: Restricted to assigned accounts only
-   **Hierarchy Access**: Automatic access to parent and child accounts
-   **Permission Scoping**: Account-specific permission filtering

### Data Isolation

-   **Account-Scoped Data**: Service tickets, time entries, billing records
-   **Permission Boundaries**: Users can only access authorized accounts
-   **Data Segregation**: Complete isolation between unrelated accounts

## Business Logic

### Account Relationships

```php
// Eloquent relationships
public function parent()
{
    return $this->belongsTo(Account::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Account::class, 'parent_id');
}

public function users()
{
    return $this->belongsToMany(User::class);
}

public function tickets()
{
    return $this->hasMany(ServiceTicket::class);
}
```

### Business Information Accessors

```php
public function getDisplayNameAttribute(): string
{
    return $this->company_name ?: $this->name;
}

public function getFullAddressAttribute(): ?string
{
    return implode(', ', array_filter([
        $this->address, $this->city,
        $this->state, $this->postal_code, $this->country
    ]));
}

public function getHierarchyLevelAttribute(): int
{
    $level = 0;
    $parent = $this->parent;
    while ($parent) {
        $level++;
        $parent = $parent->parent;
    }
    return $level;
}
```

## Setup Integration

### Initial Account Creation

During system setup, an **internal** account is created:

```php
$account = Account::create([
    'name' => $request->company_name,
    'company_name' => $request->company_name,
    'account_type' => 'internal',           // Service provider account
    'description' => 'Primary company account (Service Provider)',
    'email' => $request->company_email,
    // ... other business fields
]);
```

This ensures the super admin is assigned to the service provider's internal account, not a customer account.

## Migration Path

The account system was migrated from web-focused to business-focused:

### Removed Fields

-   `slug` - No longer needed for business accounts
-   Web-specific metadata that doesn't apply to business relationships

### Added Fields

-   Complete business information schema
-   Contact and address management
-   Billing information support
-   Business-specific metadata

### Data Migration

```php
// Migration preserves existing accounts while adding business fields
Schema::table('accounts', function (Blueprint $table) {
    $table->dropColumn('slug');
    // Add all new business fields
});
```

## Future Enhancements

### Advanced Features (Post-MVP)

-   **Account Health Scoring**: Business metrics and engagement tracking
-   **Advanced Hierarchy Management**: Bulk operations, permission inheritance rules
-   **Integration Capabilities**: CRM integration, external system sync
-   **Reporting & Analytics**: Account performance, service delivery metrics
-   **Communication History**: All interactions, emails, calls, meetings
-   **Document Management**: Contracts, agreements, compliance documents

---

**Last Updated**: August 11, 2025
**Status**: ✅ Completed - Business-focused account system fully implemented
