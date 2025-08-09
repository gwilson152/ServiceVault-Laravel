# Architecture Documentation

System architecture, database design, and technical specifications for Service Vault.

## System Architecture

-   **[Overview](overview.md)** - High-level system architecture
-   **[Technology Stack](technology-stack.md)** - Laravel, Vue.js, PostgreSQL stack
-   **[Design Patterns](design-patterns.md)** - Laravel patterns, SOLID principles

## Database Architecture

-   **[Database Schema](database-schema.md)** - Entity relationships and structure
-   **[Hierarchical Data](hierarchical-data.md)** - Nested set model for accounts
-   **[Data Modeling](data-modeling.md)** - Domain modeling approach

## Core Systems

-   **[Authentication & Authorization](auth-system.md)** - ABAC permission system
-   **[ABAC Permission System](abac-permission-system.md)** - Detailed ABAC implementation ✅ NEW
-   **[Timer System](timer-system.md)** - Real-time timer synchronization with admin oversight ✅ UPDATED
-   **[Widget System](widget-system.md)** - Dynamic widget architecture with auto-discovery ✅ NEW
-   **[Service Ticket System](service-ticket-system.md)** - Comprehensive ticket workflow system ✅ NEW
-   **[Billing System](billing-system.md)** - Rate management and invoicing
-   **[Theme System](theme-system.md)** - Multi-tenant theming architecture

## Integration Architecture

-   **[API Design](api-design.md)** - RESTful API structure
-   **[Real-time Features](realtime-architecture.md)** - Laravel Echo, WebSockets
-   **[Event Architecture](event-architecture.md)** - Domain events, listeners
-   **[Queue Architecture](queue-architecture.md)** - Background job processing

## Security Architecture

-   **[Security Model](security-model.md)** - Authentication, authorization, data protection
-   **[Multi-tenancy](multi-tenancy.md)** - Account isolation, data segregation
-   **[API Security](api-security.md)** - Rate limiting, token management

## Performance & Scalability

-   **[Caching Strategy](caching-strategy.md)** - Redis caching, query optimization
-   **[Database Optimization](database-optimization.md)** - Indexing, query performance
-   **[Scaling Considerations](scaling.md)** - Horizontal scaling, load balancing
