# Customer Portal Interface

**Status**: ✅ Complete - Full customer portal architecture implemented  
**Phase**: 15A Workflow Refinements  
**User Types**: Account Users (Customers)

## Overview

The Customer Portal provides a dedicated interface for Account Users (customers) to interact with their service requests, view project progress, track time reports, and manage billing information. This interface is separate from the main agent dashboard and focuses on customer-specific needs.

## Portal Components

### 🏠 **Portal Dashboard** (`Portal/Index.vue`)

The main customer dashboard providing an overview of account activity and quick access to key functions.

#### **Key Features**
- **Quick Stats Cards**: Active projects, open tickets, hours this month, total spent
- **Recent Support Tickets**: List of recent tickets with status and creation dates
- **Project Activity Timeline**: Recent activity across customer projects
- **Quick Actions**: Create support ticket, view invoices, access projects

#### **Dashboard Statistics**
```javascript
// API: /api/portal/stats
{
  active_projects: 3,
  open_tickets: 2,
  hours_this_month: 24.5,
  total_spent: 4250.00
}
```

#### **Quick Actions Available**
- **Create Support Ticket**: Direct access to ticket creation modal
- **View Invoices**: Navigate to billing and invoice history
- **View Projects**: Access project tracking and progress

### 📊 **Portal Projects** (`Portal/Projects.vue`)

Comprehensive project management interface for customers to track their active and completed projects.

#### **Project Overview Features**
- **Project Status Filtering**: All projects, active, completed, on hold
- **Project Cards**: Visual project cards with progress indicators
- **Summary Statistics**: Active, completed, and total hour counts
- **Project Details**: Status, progress, time logged, total cost

#### **Individual Project Information**
- **Project Status**: Active, completed, on hold, cancelled, planning
- **Progress Tracking**: Visual progress bar with percentage completion
- **Time & Cost Summary**: Hours logged and financial totals
- **Recent Activity**: Latest project updates and milestones
- **Team Information**: Assigned agents and project contacts

#### **Project Actions**
- **View Details**: Detailed project information modal
- **View Time Reports**: Access time tracking for specific project
- **Contact Team**: Direct communication with assigned agents

### 📋 **Portal Time Tracking** (`Portal/TimeTracking.vue`)
*[Component structure created, pending implementation]*

Time tracking view for customers to review time entries and generate reports for their projects.

#### **Planned Features**
- **Time Entry Reports**: Detailed breakdown of time logged by agents
- **Project Time Summary**: Time allocation across different projects
- **Date Range Filtering**: Custom date ranges for time reports
- **Export Functionality**: PDF and CSV export options

### 💰 **Portal Billing** (`Portal/Billing.vue`)
*[Component structure created, pending implementation]*

Billing and invoice management interface for customer financial oversight.

#### **Planned Features**
- **Invoice History**: Complete invoice listing with payment status
- **Payment Tracking**: Payment status and outstanding balances
- **Billing Rate Information**: Current rates and billing arrangements
- **Payment Portal**: Online payment processing integration

### ⚙️ **Portal Settings** (`Portal/Settings.vue`)
*[Component structure created, pending implementation]*

Customer account settings and preferences management.

#### **Planned Features**
- **Account Information**: Company details and contact information
- **User Management**: Manage additional account users
- **Notification Preferences**: Email and system notification settings
- **Portal Customization**: Theme and display preferences

## Portal Navigation & Access

### **Route Structure**
```php
// Customer portal routes (middleware: dashboard.role:portal)
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', 'CustomerPortalController@index')
        ->name('index');
    Route::get('/projects', 'CustomerPortalController@projects')
        ->name('projects');  
    Route::get('/time-tracking', 'CustomerPortalController@timeTracking')
        ->name('time-tracking');
    Route::get('/billing', 'CustomerPortalController@billing')
        ->name('billing');
    Route::get('/settings', 'CustomerPortalController@settings')
        ->name('settings');
});
```

### **Access Control**
- **User Type**: Only Account Users (customers) can access portal routes
- **Middleware**: `dashboard.role:portal` ensures proper access control
- **Permissions**: Portal-specific permissions for customer access

## API Integration

### **Portal-Specific Endpoints**
```bash
# Portal dashboard data
GET /api/portal/stats                    # Dashboard statistics
GET /api/portal/recent-tickets          # Recent ticket activity  
GET /api/portal/recent-activity         # Project activity timeline

# Portal projects
GET /api/portal/projects                 # Customer project listing
GET /api/portal/projects/{id}           # Project details
GET /api/portal/projects/{id}/time      # Project time reports

# Portal billing (planned)
GET /api/portal/invoices                # Customer invoice history
GET /api/portal/payments                # Payment history
GET /api/portal/billing-summary         # Current billing status
```

### **Data Structure Examples**

#### **Dashboard Stats Response**
```json
{
  "data": {
    "active_projects": 3,
    "open_tickets": 2, 
    "hours_this_month": 24.5,
    "total_spent": 4250.00
  }
}
```

#### **Project Listing Response**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Website Redesign",
      "status": "active",
      "progress": 75,
      "start_date": "2025-07-01",
      "due_date": "2025-09-15", 
      "tickets_count": 8,
      "total_hours": 124.5,
      "total_cost": 12450.00,
      "recent_activity": [...]
    }
  ],
  "summary": {
    "active": 3,
    "completed": 12,  
    "total_hours": 456.2
  }
}
```

## User Experience Design

### **Portal-Specific Design Patterns**
- **Customer-Focused Language**: Simplified terminology for non-technical users
- **Progress Visualization**: Clear progress indicators and status displays
- **Quick Access**: Prominent quick action buttons for common tasks
- **Mobile Responsive**: Optimized for mobile customer access

### **Navigation Patterns**
- **Dashboard-Centric**: All portal navigation starts from main dashboard
- **Breadcrumb Navigation**: Clear navigation paths with back buttons
- **Quick Actions**: Always-accessible common actions (create ticket, view billing)

## Security & Permissions

### **Portal Access Control**
- **Account Isolation**: Customers only see their own account data
- **Read-Only Access**: Customers cannot modify system data directly
- **Secure Data**: Sensitive financial and billing information properly protected

### **Customer Permissions**
- **Portal Access**: `pages.portal.dashboard`, `pages.portal.tickets`, etc.
- **Widget Access**: Customer-specific widgets only
- **Data Scope**: Limited to customer's own account and projects

## Integration Points

### **Main Application Integration**
- **Shared Authentication**: Uses same Laravel Sanctum authentication system  
- **Shared Components**: Leverages main application UI components where appropriate
- **Data Consistency**: Real-time updates reflect changes made by agents

### **Ticket Integration**
- **Ticket Creation**: Portal users can create tickets directly from dashboard
- **Ticket Updates**: Real-time updates on ticket status changes
- **Communication**: Messages and updates flow between portal and main system

## Implementation Status

### ✅ **Completed Components**
- **Portal Dashboard** (`Portal/Index.vue`): Complete with stats, recent activity, and quick actions
- **Portal Projects** (`Portal/Projects.vue`): Full project listing with filtering and details

### 🚧 **Pending Components**  
- **Portal Time Tracking**: Component structure created, implementation pending
- **Portal Billing**: Component structure created, implementation pending  
- **Portal Settings**: Component structure created, implementation pending

### 📋 **Next Steps**
1. Complete Portal Time Tracking implementation
2. Implement Portal Billing with invoice history
3. Build Portal Settings with account management
4. Add mobile-specific optimizations
5. Implement portal-specific notifications

---

The Customer Portal provides a dedicated, user-friendly interface for customers to manage their service relationship without the complexity of the full agent dashboard. This separation ensures customers have a focused experience tailored to their specific needs.