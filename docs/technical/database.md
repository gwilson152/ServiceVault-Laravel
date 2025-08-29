# Database Schema Reference

Complete database schema documentation for Service Vault's **consolidated PostgreSQL database**.

## Migration System

### Consolidated Migration Architecture ✅ (August 2025)

Service Vault implements a **consolidated migration system** that replaced 87+ fragmented migrations with 8 logical, comprehensive migration files for enterprise-grade deployments:

**Migration Structure**:
```
database/migrations/
├── 0001_01_01_000000_create_users_table.php          # Laravel framework
├── 0001_01_01_000001_create_cache_table.php          # Laravel framework  
├── 0001_01_01_000002_create_jobs_table.php           # Laravel framework
├── 0001_01_01_000003_create_core_user_and_account_management.php
├── 0001_01_01_000004_create_permission_and_role_management.php
├── 0001_01_01_000005_create_ticket_and_service_management.php
├── 0001_01_01_000006_create_timer_and_time_entry_system.php
├── 0001_01_01_000007_create_billing_and_invoice_system.php
├── 0001_01_01_000008_create_universal_import_system.php
├── 0001_01_01_000009_create_email_management_system.php
└── 0001_01_01_000010_create_system_configuration_and_utilities.php
```

**Key Achievements**:

**🎯 Clean Architecture**: 
- Consolidated 87+ fragmented migrations into 8 logical, comprehensive files
- No migration history baggage - all tables created with final structure
- Fresh deployment creates complete schema without incremental changes

**🔧 Composite Constraint Solution**:
- **Fixed FreeScout import duplicate email issues**
- `UNIQUE (email, user_type)` constraint on users table allows same email for different user types
- Prevents true duplicates within same user type (agent vs account_user)

**⚡ PostgreSQL Optimized**:
- UUID primary keys for all user-facing entities 
- External ID fields for import system integration
- Partial unique indexes and check constraints
- PostgreSQL triggers for enhanced functionality

**📊 Model & Seeder Compatibility**:
- Updated all Eloquent models with correct fillable fields and relationships
- Fixed all database seeders to work with consolidated schema
- Model casts align with PostgreSQL column types

**Benefits**:
- ✅ Clean deployments without migration history baggage
- ✅ All tables created with final structure in production-ready state  
- ✅ FreeScout import system fully functional with duplicate email handling
- ✅ PostgreSQL-optimized with triggers and constraints
- ✅ Enterprise-grade schema suitable for immediate deployment

## Core Tables

### Users & Authentication

#### users
```sql
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    external_id VARCHAR(255) UNIQUE, -- For import system
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULLABLE, -- Nullable for imported users
    email_verified_at TIMESTAMP,
    password VARCHAR(255) NULLABLE, -- Nullable for SSO/imported users
    account_id UUID REFERENCES accounts(id),
    user_type user_type_enum DEFAULT 'account_user', -- 'agent' | 'account_user'
    role_template_id UUID REFERENCES role_templates(id),
    visible BOOLEAN DEFAULT true,
    is_active BOOLEAN DEFAULT true,
    last_active_at TIMESTAMP,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Composite unique constraint for import system
CREATE UNIQUE INDEX users_email_user_type_partial_unique 
ON users (email, user_type) WHERE email IS NOT NULL;

-- Standard indexes
CREATE INDEX idx_users_account_id ON users(account_id);
CREATE INDEX idx_users_user_type ON users(user_type);
CREATE INDEX idx_users_active ON users(is_active);
```

**Key Features**:
- **Composite Unique Constraint**: `(email, user_type)` allows same email for different user types (solves FreeScout import)
- **Nullable Email/Password**: Supports imported users and SSO scenarios
- **External ID**: UUID for linking to external systems during imports
- **User Types**: `agent` (service providers) and `account_user` (customers)

#### role_templates
```sql
CREATE TABLE role_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_system_role BOOLEAN DEFAULT false,
    permissions JSONB DEFAULT '[]'::jsonb,
    widget_permissions JSONB DEFAULT '[]'::jsonb,
    page_permissions JSONB DEFAULT '[]'::jsonb,
    is_active BOOLEAN DEFAULT true,
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
    external_id VARCHAR(255) UNIQUE, -- For import system
    ticket_id UUID REFERENCES tickets(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    parent_id UUID REFERENCES ticket_comments(id) ON DELETE CASCADE,
    is_internal BOOLEAN DEFAULT false,
    attachments JSONB,
    edited_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_ticket_comments_ticket_parent ON ticket_comments(ticket_id, parent_id);
CREATE INDEX idx_ticket_comments_user_internal ON ticket_comments(user_id, is_internal);
CREATE INDEX idx_ticket_comments_parent ON ticket_comments(parent_id);
CREATE INDEX idx_ticket_comments_created ON ticket_comments(created_at);
```

**Key Features**:
- **Comment Threading**: `parent_id` enables nested comment structures
- **External ID**: Support for import system linking
- **Edit Tracking**: `edited_at` timestamp for comment modifications
- **Internal/External**: Boolean flag for comment visibility (replaces enum type)
- **Attachments**: JSONB array for file attachments

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

## Email System Tables

### email_system_config
```sql
CREATE TABLE email_system_config (
    id SERIAL PRIMARY KEY,
    configuration_name VARCHAR(255) DEFAULT 'Default Email System Configuration',
    
    -- System Status
    system_active BOOLEAN DEFAULT false,
    
    -- Incoming Email Service Configuration
    incoming_enabled BOOLEAN DEFAULT false,
    incoming_provider VARCHAR(50), -- imap, gmail, outlook, exchange
    incoming_host VARCHAR(255),
    incoming_port INTEGER,
    incoming_username VARCHAR(255),
    incoming_password VARCHAR(255), -- Encrypted
    incoming_encryption VARCHAR(20), -- tls, ssl, starttls, none
    incoming_folder VARCHAR(100) DEFAULT 'INBOX',
    incoming_settings JSONB, -- Provider-specific settings
    
    -- Outgoing Email Service Configuration
    outgoing_enabled BOOLEAN DEFAULT false,
    outgoing_provider VARCHAR(50), -- smtp, gmail, outlook, ses, sendgrid, postmark, mailgun
    outgoing_host VARCHAR(255),
    outgoing_port INTEGER,
    outgoing_username VARCHAR(255),
    outgoing_password VARCHAR(255), -- Encrypted
    outgoing_encryption VARCHAR(20), -- tls, ssl, starttls, none
    outgoing_settings JSONB, -- Provider-specific settings
    
    -- From Address Configuration
    from_address VARCHAR(255),
    from_name VARCHAR(255),
    reply_to_address VARCHAR(255),
    
    -- Processing Settings
    auto_create_tickets BOOLEAN DEFAULT true,
    process_commands BOOLEAN DEFAULT true,
    send_confirmations BOOLEAN DEFAULT true,
    max_retries INTEGER DEFAULT 3,
    processing_rules JSONB, -- Advanced processing rules
    
    -- Post-Processing Settings (August 2025)
    post_processing_action VARCHAR(20) DEFAULT 'none', -- none, mark_read, move_folder, delete
    move_to_folder_id VARCHAR(255), -- Target folder ID for move operations
    move_to_folder_name VARCHAR(255), -- Fallback folder name
    email_retrieval_mode VARCHAR(20) DEFAULT 'unread_only', -- unread_only, all, recent
    
    -- Email Processing Strategy
    enable_email_processing BOOLEAN DEFAULT true,
    auto_create_users BOOLEAN DEFAULT true,
    unmapped_domain_strategy VARCHAR(50) DEFAULT 'assign_default_account', -- assign_default_account, require_manual_assignment, reject_email
    default_account_id UUID REFERENCES accounts(id),
    default_role_template_id UUID REFERENCES role_templates(id),
    require_email_verification BOOLEAN DEFAULT true,
    require_admin_approval BOOLEAN DEFAULT true,
    
    -- Testing & Monitoring
    last_tested_at TIMESTAMP,
    test_results JSONB,
    
    -- Audit
    updated_by_id UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Single configuration record enforced by application
CREATE UNIQUE INDEX idx_email_config_singleton ON email_system_config(id) WHERE id = 1;
```

**Key Features**:
- **Application-Wide**: Single configuration for entire platform
- **Multi-Provider**: Support for multiple email services (IMAP, SMTP, Microsoft 365, Gmail, etc.)
- **Encrypted Credentials**: Password fields are encrypted in storage
- **Test Integration**: Built-in configuration testing and results storage
- **Post-Processing Actions**: Configurable email handling after processing (mark read, move, delete)
- **Intelligent Retrieval**: Respects UI-configured email retrieval modes
- **User Management**: Automated user creation with domain mapping and approval workflows
- **Background Processing**: Queue-based post-processing with retry mechanisms

### email_domain_mappings
```sql
CREATE TABLE email_domain_mappings (
    id SERIAL PRIMARY KEY,
    
    -- Domain/Email Pattern Matching
    domain_pattern VARCHAR(255) NOT NULL, -- @acme.com, support@acme.com, *@acme.com
    pattern_type VARCHAR(20) DEFAULT 'domain', -- domain, email, wildcard
    
    -- Business Account Assignment
    account_id UUID NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    
    -- Assignment Rules
    default_assigned_user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    default_category VARCHAR(255),
    default_priority VARCHAR(20) DEFAULT 'medium', -- low, medium, high, urgent
    
    -- Processing Rules
    auto_create_tickets BOOLEAN DEFAULT true,
    send_auto_reply BOOLEAN DEFAULT false,
    auto_reply_template TEXT,
    custom_rules JSONB, -- Additional processing rules
    
    -- Status and Priority
    is_active BOOLEAN DEFAULT true,
    priority INTEGER DEFAULT 100, -- Higher number = higher priority for matching
    
    -- Audit
    created_by_id UUID REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX idx_domain_mappings_pattern_active ON email_domain_mappings(domain_pattern, is_active);
CREATE INDEX idx_domain_mappings_account_active ON email_domain_mappings(account_id, is_active);
CREATE INDEX idx_domain_mappings_priority_active ON email_domain_mappings(priority, is_active);

-- Unique constraint to prevent duplicate patterns
CREATE UNIQUE INDEX idx_domain_mappings_unique ON email_domain_mappings(domain_pattern, pattern_type);
```

**Key Features**:
- **Pattern Matching**: Support for domain, exact email, and wildcard patterns
- **Priority-Based**: Configurable priority for pattern matching order
- **Account Routing**: Direct mapping to business accounts for ticket assignment
- **Flexible Rules**: JSON-based custom processing rules

### email_processing_logs
```sql
CREATE TABLE email_processing_logs (
    id SERIAL PRIMARY KEY,
    email_id VARCHAR(255) UNIQUE NOT NULL, -- Unique identifier for the email
    
    -- Email Metadata
    status VARCHAR(20) DEFAULT 'pending', -- pending, processing, processed, failed, retry
    direction VARCHAR(20) NOT NULL, -- incoming, outgoing
    from_address VARCHAR(255),
    to_addresses JSONB, -- Array of recipient addresses
    subject TEXT,
    message_id VARCHAR(255), -- Email Message-ID header
    received_at TIMESTAMP,
    
    -- Processing Information
    processing_duration_ms INTEGER,
    account_id UUID REFERENCES accounts(id),
    created_new_ticket BOOLEAN DEFAULT false,
    ticket_id UUID REFERENCES tickets(id),
    ticket_comment_id UUID REFERENCES ticket_comments(id),
    
    -- Command Processing
    commands_processed INTEGER DEFAULT 0,
    commands_executed_count INTEGER DEFAULT 0,
    commands_failed_count INTEGER DEFAULT 0,
    command_processing_success BOOLEAN,
    command_results JSONB, -- Command execution results
    
    -- Error Handling
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    next_retry_at TIMESTAMP,
    
    -- Email Content (for debugging/audit)
    raw_email_content TEXT,
    parsed_content JSONB, -- Structured email content
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for monitoring and reporting
CREATE INDEX idx_email_logs_status ON email_processing_logs(status);
CREATE INDEX idx_email_logs_direction ON email_processing_logs(direction);
CREATE INDEX idx_email_logs_account ON email_processing_logs(account_id);
CREATE INDEX idx_email_logs_created_at ON email_processing_logs(created_at);
CREATE INDEX idx_email_logs_ticket ON email_processing_logs(ticket_id);
CREATE INDEX idx_email_logs_retry ON email_processing_logs(next_retry_at) WHERE next_retry_at IS NOT NULL;
```

**Key Features**:
- **Comprehensive Logging**: Complete audit trail of email processing
- **Command Tracking**: Detailed command execution results
- **Error Management**: Retry logic with configurable attempts
- **Performance Monitoring**: Processing duration tracking

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
- **Universal Database Support**: Supports any PostgreSQL database with template-based configuration

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
- **Universal Import System**: Complete overhaul from FreeScout-specific to template-based universal database import system
- **Template Architecture**: Added support for platform-specific templates (FreeScout, Custom) with JSON-based configuration storage
- **Visual Query Builder**: Database schema introspection support for TableSelector, JoinBuilder, FieldMapper, and FilterBuilder components
- **Simplified Workflow**: Separated database connection creation from template configuration for improved user experience
- **PostgreSQL Schema Introspection**: Enhanced database metadata queries for visual query builder components
- **Production Stability**: Resolved API inconsistencies and improved error handling throughout the import system

### Best Practices
- Always add existence checks in migrations
- Use transactions for complex schema changes
- Test migrations on staging before production
- Document breaking changes in migration comments

For development setup and migration commands, see [Setup Guide](../guides/setup.md).

---

*Last Updated: August 28, 2025 - Enhanced Email System Schema & Fixed Ticket Comments Threading*