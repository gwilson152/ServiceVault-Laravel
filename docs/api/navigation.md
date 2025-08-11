# Navigation API

Dynamic navigation system based on user permissions and context.

## Features
- **Permission-Based**: Navigation adapts to user roles
- **Context-Aware**: Different menus for service providers vs account users
- **Breadcrumbs**: Dynamic breadcrumb generation
- **Access Control**: Check page accessibility

## Authentication
- **Session/Token Auth**: Uses current user's permissions

## Endpoints

### Navigation Data
```http
GET /api/navigation                                    # Get user's navigation menu
GET /api/navigation/breadcrumbs?path=/tickets/123      # Get breadcrumbs for path
GET /api/navigation/can-access?page=tickets.manage     # Check page access
```

## Response Formats

### Navigation Response
```json
{
  "menu": [
    {
      "name": "Dashboard",
      "path": "/dashboard",
      "icon": "home",
      "permission": "pages.dashboard.access"
    },
    {
      "name": "Tickets",
      "path": "/tickets", 
      "icon": "ticket",
      "permission": "pages.tickets.manage",
      "children": [
        {
          "name": "My Tickets",
          "path": "/tickets/my",
          "permission": "tickets.view.own"
        }
      ]
    }
  ],
  "context": "service_provider"
}
```

### Breadcrumbs Response
```json
{
  "breadcrumbs": [
    { "name": "Dashboard", "path": "/dashboard" },
    { "name": "Tickets", "path": "/tickets" },
    { "name": "Ticket #123", "path": "/tickets/123" }
  ]
}
```