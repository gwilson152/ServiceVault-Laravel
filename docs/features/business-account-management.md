# Business Account Management

Comprehensive customer relationship management system for B2B service delivery.

## Overview

The Business Account Management system provides complete customer relationship management capabilities designed specifically for B2B service providers. It manages all types of business relationships including customers, prospects, partners, and internal organizations with comprehensive business information, contact details, and hierarchical structures.

## Account Types

### Customer Accounts
**Primary service recipients** - Organizations that purchase your services.

**Key Features:**
- Complete business profile with contact information
- Billing details including tax IDs and addresses
- Service history and ticket tracking
- Time tracking and billing integration
- Account hierarchy for corporate structures

**Typical Use Cases:**
- Main corporate customer accounts
- Subsidiary companies under parent organizations
- Department-level accounts within larger companies
- Branch offices and regional divisions

### Prospect Accounts
**Potential customers** in your sales pipeline.

**Key Features:**
- Lead qualification tracking
- Contact management and notes
- Progress tracking through sales stages
- Conversion to customer accounts
- Sales team assignment and collaboration

**Typical Use Cases:**
- Qualified leads from marketing campaigns
- Referrals from existing customers
- Cold outreach prospects
- Conference and trade show contacts

### Partner Accounts
**Business partners** and collaborative relationships.

**Key Features:**
- Partnership agreement tracking
- Referral management and commission tracking
- Joint project collaboration
- Vendor and contractor management
- Service provider partnerships

**Typical Use Cases:**
- Referral partners who send business
- Subcontractors and service vendors
- Technology integration partners  
- Strategic business alliances

### Internal Accounts
**Your organization** and internal divisions.

**Key Features:**
- Company hierarchy management
- Department and team organization
- Internal project tracking
- Resource allocation and management
- Cost center and budget tracking

**Typical Use Cases:**
- Your primary service provider company
- Internal departments and divisions
- Regional offices and locations
- Project teams and service groups

## Business Information Management

### Core Business Details
Every account captures comprehensive business information:

- **Company Information**: Legal name, DBA, business type
- **Contact Details**: Primary contact, email, phone, website
- **Physical Address**: Complete mailing address with geographic info
- **Billing Information**: Separate billing address, tax IDs, payment terms
- **Business Notes**: Internal notes and relationship history

### Contact Management
- **Primary Contact Person**: Main point of contact
- **Contact Information**: Email, phone, website
- **Communication Preferences**: Preferred contact methods
- **Contact History**: Track all interactions and communications

### Address Management  
- **Physical Address**: Company location and mailing address
- **Billing Address**: Separate billing location if different
- **Address Validation**: Ensure accurate shipping and billing
- **Geographic Organization**: Regional and location-based reporting

### Business Relationship Tracking
- **Relationship History**: Track relationship development over time
- **Service History**: All tickets, projects, and service delivery
- **Communication Log**: Emails, calls, meetings, and interactions
- **Account Health**: Relationship status and engagement metrics

## Account Hierarchy System

### Hierarchical Structures
Support unlimited depth organizational hierarchies:

- **Parent-Child Relationships**: Unlimited nesting levels
- **Corporate Structures**: Holding companies → subsidiaries → divisions
- **Geographic Organization**: Global → regional → local offices
- **Departmental Structure**: Company → departments → teams

### Hierarchy Management
- **Visual Tree View**: Interactive hierarchy visualization
- **Permission Inheritance**: Access rights cascade through structure
- **Bulk Operations**: Manage multiple accounts in hierarchy
- **Relationship History**: Track hierarchy changes over time

### Access Control Integration
- **Hierarchical Permissions**: Access parent and child accounts automatically
- **Account Context Switching**: Users can switch between authorized accounts
- **Scoped Data Access**: Account-specific data filtering
- **Inheritance Rules**: Permission flow through organizational structure

## Account Management Interface

### Account List View
Professional business-focused interface with hierarchical display:

**Display Columns:**
- **Company/Account**: Business name with visual hierarchy tree connectors (└─, │)
- **Contact Information**: Primary contact with email and phone
- **Account Type**: Visual badges for customer/prospect/partner/internal  
- **Hierarchy Level**: Visual indicators showing Root Account vs Subsidiary L1/L2/etc with children count
- **Status & Activity**: Active status and recent activity indicators

**Hierarchical Display Features:**
- **Tree Structure**: Visual tree connectors showing parent-child relationships
- **Level Indicators**: Clear Root/Subsidiary level badges with different colors
- **Indentation**: Proper indentation based on hierarchy depth
- **Icon Differentiation**: Different icons for root accounts vs subsidiaries
- **Children Count**: Shows number of direct subsidiaries per account

**Search & Filtering:**
- **Global Search**: Company name, contact person, email, phone
- **Type Filtering**: Filter by account type
- **Hierarchy Filtering**: Show specific levels or branches
- **Status Filtering**: Active/inactive account filtering
- **Advanced Search**: Multiple criteria combinations

**Bulk Operations:**
- **Bulk Edit**: Update multiple accounts simultaneously
- **Bulk Status Changes**: Activate/deactivate multiple accounts
- **Bulk Assignment**: Assign users to multiple accounts
- **Export Operations**: Export filtered account lists

### Account Creation & Editing

#### Account Form Organization
**Basic Information Section:**
- Account name (display name)
- Company name (legal business name)  
- Account type selection
- Parent account (hierarchy placement)
- Business description

**Contact Information Section:**
- Primary contact person
- Email address
- Phone number  
- Website URL

**Address Information Section:**
- Complete physical address
- City, state, postal code, country
- Address validation and formatting

**Billing Information Section:**
- Billing address (with copy function)
- Tax ID / VAT number
- Payment terms and preferences

**Business Details Section:**
- Internal notes and history
- Account settings and preferences
- Active/inactive status toggle

#### Smart Form Features
- **Address Copy Function**: Copy physical to billing address
- **Validation**: Real-time field validation and error handling
- **Auto-complete**: Smart suggestions for common fields
- **Save States**: Draft saving and recovery

### Account Management Actions

#### Account Operations
- **View Account Details**: Complete account profile and history
- **Edit Account Information**: Update all business details
- **Manage Account Users**: Assign users and set permissions
- **View Account Hierarchy**: Navigate parent/child relationships
- **Account Activity History**: Track all changes and interactions

#### Account Relationships
- **User Assignment**: Assign team members to accounts with appropriate roles
- **Hierarchy Management**: Set parent accounts and create sub-accounts
- **Service History**: View all tickets, time entries, and service delivery
- **Billing History**: Track invoices, payments, and financial history

#### Data Management
- **Account Merge**: Combine duplicate or related accounts
- **Account Split**: Separate accounts when business relationships change
- **Data Export**: Export account information for external use
- **Account Archive**: Safely archive inactive accounts

## Account Context System

### Context Awareness
The system provides context-aware access based on user roles:

**Service Provider Mode:**
- Access to all customer and prospect accounts
- Ability to create and manage accounts
- Cross-account reporting and analytics
- Administrative functions and settings

**Account User Mode:**
- Access limited to assigned accounts only
- Hierarchy access to parent and child accounts
- Account-specific data and reporting
- Limited administrative functions

### Context Switching
- **Seamless Switching**: Quick context changes between accounts
- **Context Indicators**: Clear indication of current account context
- **Permission Scoping**: Automatic permission adjustment per context
- **Data Filtering**: Account-specific data visibility

## Integration Points

### Service Ticket Integration
- **Account-Scoped Tickets**: All tickets belong to specific accounts
- **Cross-Account Tickets**: Handle multi-account projects
- **Account Context**: Tickets inherit account permissions and settings
- **Service History**: Complete ticket history per account

### Time Tracking Integration
- **Account Time Tracking**: All time entries associated with accounts
- **Billable Time**: Integration with billing and invoicing
- **Project Time**: Track time across account projects
- **Reporting**: Account-specific time reports and analytics

### Billing Integration
- **Account Billing**: All billing tied to specific accounts
- **Tax Handling**: Account-specific tax rates and regulations
- **Payment Terms**: Account-specific payment and billing terms
- **Invoice Generation**: Automatic invoice generation per account

### User Management Integration
- **Account Assignment**: Users assigned to specific accounts
- **Role Scoping**: Account-specific role and permission assignment
- **Access Control**: Account-based access restrictions
- **Team Management**: Account-specific team organization

## API Integration

### Account Management Endpoints
```bash
# Core CRUD Operations
GET    /api/accounts                    # List all accounts with business info
POST   /api/accounts                    # Create new account with validation
GET    /api/accounts/{account}          # Get account with full details
PUT    /api/accounts/{account}          # Update account information
DELETE /api/accounts/{account}          # Delete account (with safety checks)

# Hierarchy Operations  
GET    /api/accounts                              # Returns accounts in hierarchical order by default
GET    /api/accounts/selector/hierarchical        # Hierarchical account selector with tree structure
GET    /api/accounts/{account}                    # Get account with parent/children relationships loaded
POST   /api/accounts                             # Create account with optional parent_id for hierarchy placement

# Search and Filtering
GET    /api/accounts?search={query}               # Search accounts
GET    /api/accounts?type={type}                  # Filter by account type
GET    /api/accounts?status={status}              # Filter by active status
GET    /api/accounts?parent_id={id}               # Get accounts by parent
```

### Account Data Validation
```php
// Account creation/update validation
'name' => 'required|string|max:255',
'company_name' => 'nullable|string|max:255', 
'account_type' => 'required|in:customer,prospect,partner,internal',
'description' => 'nullable|string',
'parent_id' => 'nullable|exists:accounts,id',

// Contact information
'contact_person' => 'nullable|string|max:255',
'email' => 'nullable|email|max:255',
'phone' => 'nullable|string|max:50', 
'website' => 'nullable|url|max:255',

// Address information
'address' => 'nullable|string',
'city' => 'nullable|string|max:100',
'state' => 'nullable|string|max:100',
'postal_code' => 'nullable|string|max:20',
'country' => 'nullable|string|max:100',

// Billing information  
'billing_address' => 'nullable|string',
'billing_city' => 'nullable|string|max:100',
'billing_state' => 'nullable|string|max:100', 
'billing_postal_code' => 'nullable|string|max:20',
'billing_country' => 'nullable|string|max:100',
'tax_id' => 'nullable|string|max:100',

// Business details
'notes' => 'nullable|string',
'is_active' => 'boolean'
```

## Permissions & Security

### Account Access Permissions
```php
// Basic account permissions
'accounts.view'         // View account information
'accounts.create'       // Create new accounts
'accounts.edit'         // Edit account information  
'accounts.delete'       // Delete accounts
'accounts.manage'       // Full account management

// Hierarchy permissions
'accounts.hierarchy.access'    // Access account hierarchies
'accounts.hierarchy.manage'    // Manage parent-child relationships
'accounts.context.switch'      // Switch between account contexts

// Advanced permissions
'accounts.reports.access'      // Access account reports
'accounts.billing.access'      // Access billing information
'accounts.users.manage'        // Manage account user assignments
```

### Data Security
- **Account Isolation**: Complete data separation between accounts
- **Permission-Based Access**: Granular access control per account
- **Audit Logging**: Track all account changes and access
- **Data Encryption**: Sensitive information encrypted at rest

## Reporting & Analytics

### Account Analytics
- **Account Health Metrics**: Engagement, activity, and relationship strength
- **Service Delivery Reports**: Tickets, time, and project completion
- **Revenue Analytics**: Account profitability and billing analysis
- **Growth Tracking**: Account expansion and relationship development

### Business Intelligence
- **Account Segmentation**: Group accounts by various criteria
- **Predictive Analytics**: Identify at-risk accounts and opportunities
- **Performance Dashboards**: Real-time account performance metrics
- **Comparative Analysis**: Benchmark accounts against industry standards

## Migration & Data Import

### Account Migration
- **Legacy System Import**: Import from CRM and ERP systems
- **Data Mapping**: Map existing data to new business account schema
- **Validation**: Ensure data quality during migration
- **Rollback**: Safe rollback procedures for failed migrations

### Ongoing Data Management
- **Data Quality**: Automated data quality checks and validation
- **Duplicate Detection**: Identify and merge duplicate accounts
- **Data Enrichment**: Enhance account data with external sources
- **Regular Cleanup**: Automated maintenance and data hygiene

---

**Last Updated**: August 11, 2025  
**Status**: ✅ Completed - Comprehensive business account management system fully implemented