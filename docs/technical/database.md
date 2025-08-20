# Database Schema Reference

Complete database schema documentation for Service Vault's PostgreSQL database.

## Core Tables

### Users & Authentication

#### users
```sql
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    email_verified_at TIMESTAMP,
    password VARCHAR(255),
    user_type VARCHAR(50) DEFAULT 'employee',
    account_id UUID REFERENCES accounts(id),
    role_template_id UUID REFERENCES role_templates(id),
    is_active BOOLEAN DEFAULT true,
    is_visible BOOLEAN DEFAULT true,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_account_id ON users(account_id);
CREATE INDEX idx_users_user_type ON users(user_type);
CREATE INDEX idx_users_active ON users(is_active);
```

**Key Fields**:
- `user_type`: super_admin, agent, manager, account_user, employee
- `email`: Optional - supports users without email addresses
- `is_visible`: Controls appearance in user selection lists

#### role_templates
```sql
CREATE TABLE role_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    permissions JSONB DEFAULT '[]'::jsonb,
    widget_permissions JSONB DEFAULT '[]'::jsonb,
    page_permissions JSONB DEFAULT '[]'::jsonb,
    is_system_default BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Permission Arrays**:
- `permissions`: Functional permissions (what users can DO)
- `widget_permissions`: Dashboard widget visibility
- `page_permissions`: Page access control

#### personal_access_tokens (Laravel Sanctum)
```sql
CREATE TABLE personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities JSON,
    last_used_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Account Management

#### accounts
```sql
CREATE TABLE accounts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id UUID REFERENCES accounts(id),
    settings JSONB DEFAULT '{}'::jsonb,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_accounts_parent_id ON accounts(parent_id);
CREATE INDEX idx_accounts_active ON accounts(is_active);
```

**Account Settings JSON**:
```json
{
  "billing_terms": 30,
  "default_billing_rate_id": "uuid",
  "auto_invoice": false,
  "billing_contact": {
    "name": "Finance Department",
    "email": "billing@company.com"
  }
}
```

#### domain_mappings
```sql
CREATE TABLE domain_mappings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    domain_pattern VARCHAR(255) NOT NULL,
    account_id UUID REFERENCES accounts(id) ON DELETE CASCADE,
    priority INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_domain_mappings_pattern ON domain_mappings(domain_pattern);
CREATE INDEX idx_domain_mappings_priority ON domain_mappings(priority DESC);
```

### Ticketing System

#### tickets
```sql
CREATE TABLE tickets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_number VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'open',
    priority VARCHAR(50) DEFAULT 'normal',
    category VARCHAR(100) DEFAULT 'support',
    account_id UUID REFERENCES accounts(id) ON DELETE CASCADE,
    customer_id UUID REFERENCES users(id),
    assigned_to UUID REFERENCES users(id),
    due_date TIMESTAMP,
    tags TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_tickets_number ON tickets(ticket_number);
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_priority ON tickets(priority);
CREATE INDEX idx_tickets_account ON tickets(account_id);
CREATE INDEX idx_tickets_assigned ON tickets(assigned_to);
CREATE INDEX idx_tickets_customer ON tickets(customer_id);
CREATE INDEX idx_tickets_due_date ON tickets(due_date);
```

#### ticket_comments
```sql
CREATE TABLE ticket_comments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID REFERENCES tickets(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id),
    comment TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_ticket_comments_ticket ON ticket_comments(ticket_id);
CREATE INDEX idx_ticket_comments_user ON ticket_comments(user_id);
CREATE INDEX idx_ticket_comments_created ON ticket_comments(created_at);
```

#### ticket_categories, ticket_statuses, ticket_priorities
```sql
-- Ticket Categories
CREATE TABLE ticket_categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    sla_hours INTEGER,
    workflow_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ticket Statuses  
CREATE TABLE ticket_statuses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#6B7280',
    workflow_order INTEGER DEFAULT 0,
    is_closed_status BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ticket Priorities
CREATE TABLE ticket_priorities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(50) NOT NULL,
    level INTEGER NOT NULL,
    color VARCHAR(7) DEFAULT '#10B981',
    escalation_hours INTEGER,
    workflow_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Time Tracking & Billing

#### timers
```sql
CREATE TABLE timers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    account_id UUID REFERENCES accounts(id),
    ticket_id UUID REFERENCES tickets(id),
    billing_rate_id UUID REFERENCES billing_rates(id),
    time_entry_id UUID REFERENCES time_entries(id),
    description TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'running',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paused_at TIMESTAMP,
    stopped_at TIMESTAMP,
    total_paused_duration INTEGER DEFAULT 0,
    billable BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_timers_user ON timers(user_id);
CREATE INDEX idx_timers_account ON timers(account_id);
CREATE INDEX idx_timers_ticket ON timers(ticket_id);
CREATE INDEX idx_timers_status ON timers(status);
CREATE INDEX idx_timers_started ON timers(started_at);
```

**Timer Status Values**:
- `running`: Currently active
- `paused`: Temporarily stopped
- `stopped`: Completed but not committed
- `committed`: Converted to time entry (August 2025: properly updates in database)
- `canceled`: Abandoned without creating time entry

**Duration Storage**: Timer durations stored in **seconds** (calculated from start/stop times)

#### time_entries
```sql
CREATE TABLE time_entries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    account_id UUID REFERENCES accounts(id) ON DELETE CASCADE,
    ticket_id UUID REFERENCES tickets(id),
    billing_rate_id UUID REFERENCES billing_rates(id),
    timer_id UUID REFERENCES timers(id),
    description TEXT NOT NULL,
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NOT NULL,
    duration INTEGER NOT NULL,
    calculated_amount DECIMAL(10,2),
    billable BOOLEAN DEFAULT true,
    status VARCHAR(20) DEFAULT 'pending',
    approved_by UUID REFERENCES users(id),
    approved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_time_entries_user ON time_entries(user_id);
CREATE INDEX idx_time_entries_account ON time_entries(account_id);
CREATE INDEX idx_time_entries_ticket ON time_entries(ticket_id);
CREATE INDEX idx_time_entries_status ON time_entries(status);
CREATE INDEX idx_time_entries_started ON time_entries(started_at);
CREATE INDEX idx_time_entries_billable ON time_entries(billable);
```

**Time Entry Status Values**:
- `pending`: Awaiting approval
- `approved`: Approved for billing
- `rejected`: Rejected, not billable

**Duration Storage**: Time entry durations stored in **minutes** (converted from timer seconds)
**Timer Linkage**: `timer_id` links time entries back to originating timers (August 2025 enhancement)

#### billing_rates
```sql
CREATE TABLE billing_rates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    rate DECIMAL(10,2) NOT NULL,
    account_id UUID REFERENCES accounts(id),
    is_default BOOLEAN DEFAULT false,
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_billing_rates_account ON billing_rates(account_id);
CREATE INDEX idx_billing_rates_default ON billing_rates(is_default);
CREATE INDEX idx_billing_rates_active ON billing_rates(is_active);
```

**Rate Hierarchy**:
- `account_id = NULL`: Global default rates
- `account_id = UUID`: Account-specific rates (higher priority)

### Invoice & Financial Management

#### invoices
```sql
CREATE TABLE invoices (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    account_id UUID REFERENCES accounts(id) ON DELETE CASCADE,
    total_amount DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'draft',
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    terms INTEGER DEFAULT 30,
    notes TEXT,
    sent_at TIMESTAMP,
    paid_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_invoices_number ON invoices(invoice_number);
CREATE INDEX idx_invoices_account ON invoices(account_id);
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE INDEX idx_invoices_due_date ON invoices(due_date);
```

#### invoice_line_items
```sql
CREATE TABLE invoice_line_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    invoice_id UUID REFERENCES invoices(id) ON DELETE CASCADE,
    description TEXT NOT NULL,
    quantity DECIMAL(8,2) NOT NULL,
    rate DECIMAL(10,2) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    line_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_invoice_items_invoice ON invoice_line_items(invoice_id);
CREATE INDEX idx_invoice_items_order ON invoice_line_items(line_order);
```

## Import System Tables

### import_profiles
```sql
CREATE TABLE import_profiles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'freescout-postgres',
    host VARCHAR(255) NOT NULL,
    port INTEGER DEFAULT 5432,
    database VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password TEXT NOT NULL, -- Encrypted using Laravel Crypt
    ssl_mode VARCHAR(20) DEFAULT 'prefer',
    description TEXT,
    connection_options JSONB,
    is_active BOOLEAN DEFAULT true,
    created_by UUID NOT NULL REFERENCES users(id),
    last_tested_at TIMESTAMP,
    last_test_result JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_import_profiles_type_active ON import_profiles(type, is_active);
CREATE INDEX idx_import_profiles_created_by ON import_profiles(created_by);
```

**Key Features**:
- **UUID Primary Keys**: Consistent with Service Vault architecture
- **Encrypted Passwords**: Database credentials encrypted at rest
- **Connection Testing**: Stores last test results for validation
- **Type Support**: Currently supports FreeScout PostgreSQL imports

### import_jobs
```sql
CREATE TABLE import_jobs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    profile_id UUID NOT NULL REFERENCES import_profiles(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'pending',
    import_options JSONB,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    records_processed INTEGER DEFAULT 0,
    records_imported INTEGER DEFAULT 0,
    records_skipped INTEGER DEFAULT 0,
    records_failed INTEGER DEFAULT 0,
    summary JSONB,
    errors TEXT,
    progress_percentage INTEGER DEFAULT 0,
    current_operation VARCHAR(255),
    created_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_import_jobs_profile ON import_jobs(profile_id);
CREATE INDEX idx_import_jobs_status ON import_jobs(status);
CREATE INDEX idx_import_jobs_created_by ON import_jobs(created_by);
CREATE INDEX idx_import_jobs_created_at ON import_jobs(created_at DESC);
```

**Status Values**:
- `pending` - Job queued for execution
- `running` - Import in progress
- `completed` - Import finished successfully
- `failed` - Import encountered fatal errors
- `cancelled` - Job cancelled by user

**Import Options Structure**:
```json
{
  "selected_tables": ["users", "customers", "conversations", "threads"],
  "import_filters": {
    "date_from": "2024-01-01",
    "date_to": "2024-12-31",
    "ticket_status": "1",
    "limit": 1000,
    "active_users_only": true
  },
  "batch_size": 100,
  "overwrite_existing": false
}
```

### import_mappings
```sql
CREATE TABLE import_mappings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    profile_id UUID NOT NULL REFERENCES import_profiles(id) ON DELETE CASCADE,
    source_table VARCHAR(255) NOT NULL,
    destination_table VARCHAR(255) NOT NULL,
    field_mappings JSONB NOT NULL,
    where_conditions TEXT,
    transformation_rules JSONB,
    is_active BOOLEAN DEFAULT true,
    import_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_import_mappings_profile_active ON import_mappings(profile_id, is_active);
CREATE INDEX idx_import_mappings_source_dest ON import_mappings(source_table, destination_table);
CREATE INDEX idx_import_mappings_order ON import_mappings(import_order);

-- Unique constraint
CREATE UNIQUE INDEX idx_import_mappings_unique ON import_mappings(profile_id, source_table, destination_table);
```

**Field Mappings Structure** (Enhanced FreeScout Example):
```json
{
  "name": {
    "type": "combine_fields",
    "fields": ["first_name", "last_name"],
    "separator": " "
  },
  "email": {
    "type": "direct_mapping",
    "source_field": "email"
  },
  "user_type": {
    "type": "static_value",
    "static_value": "agent"
  },
  "id": {
    "type": "integer_to_uuid",
    "source_field": "id",
    "prefix": "freescout_user_"
  },
  "normalized_email": {
    "type": "transform_function",
    "source_field": "email",
    "transform_function": "lowercase"
  }
}
```

**Transformation Types:**
- `direct_mapping` - One-to-one field mapping
- `combine_fields` - Merge multiple fields with separator
- `static_value` - Set fixed value for all records  
- `integer_to_uuid` - Convert integers to deterministic UUIDs
- `transform_function` - Apply data transformations (lowercase, uppercase, trim, date_format, boolean_convert)

## Key Constraints & Features

### UUID Primary Keys
All tables use UUID primary keys for:
- Security (non-sequential, unpredictable)
- Distributed system compatibility
- Cross-database uniqueness

### Soft Deletes
Implemented via application logic rather than database-level:
- `is_active` flags for logical deletion
- Maintains referential integrity
- Audit trail preservation

### JSON Columns
PostgreSQL JSONB used for:
- Role template permissions
- Account settings
- Token abilities
- Flexible configuration storage

### Timestamp Management
- `created_at`: Set on record creation
- `updated_at`: Updated on record modification
- Laravel handles automatic timestamp management

### Indexes & Performance
- Strategic indexing on frequently queried columns
- Composite indexes for common query patterns
- JSON path indexing for JSONB columns

## Migration Strategy

### Version Control
- Laravel migrations in `database/migrations/`
- Sequential timestamp-based naming
- Rollback capability for schema changes

### Recent Schema Changes
- **Complete Field Mapping System**: Enhanced import_mappings table with comprehensive field transformation support
- **PostgreSQL Schema Introspection**: Fixed stdClass object handling in schema queries for reliable database inspection
- **Import System API Enhancement**: Added field mapping endpoints and missing job status methods
- **Import Profile UUID Architecture**: Complete UUID migration with deterministic conversion for consistent data integrity
- **Filter-Based Import Controls**: Enhanced import job options structure with comprehensive filtering capabilities
- **Production Stability Fixes**: Resolved API route binding issues and controller method inconsistencies

### Best Practices
- Always add existence checks in migrations
- Use transactions for complex schema changes
- Test migrations on staging before production
- Document breaking changes in migration comments

For development setup and migration commands, see [Setup Guide](../guides/setup.md).