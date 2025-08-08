# Deployment Documentation

Production deployment, server configuration, and infrastructure management.

## Environment Setup
- **[Production Requirements](production-requirements.md)** - Server specs, dependencies
- **[Environment Configuration](environment-config.md)** - .env files, secrets management
- **[Database Setup](database-setup.md)** - PostgreSQL configuration, connection pooling

## Server Configuration
- **[Nginx Configuration](nginx-config.md)** - Web server optimization
- **[PHP-FPM Setup](php-fpm-setup.md)** - PHP process management
- **[Redis Configuration](redis-config.md)** - Cache and session storage
- **[SSL/TLS Setup](ssl-setup.md)** - HTTPS configuration

## Application Deployment
- **[Deployment Process](deployment-process.md)** - Step-by-step deployment
- **[Zero-downtime Deployment](zero-downtime.md)** - Rolling deployments
- **[Database Migrations](production-migrations.md)** - Safe production migrations
- **[Asset Pipeline](asset-pipeline.md)** - Frontend build and CDN

## Infrastructure
- **[Docker Setup](docker-setup.md)** - Containerization (optional)
- **[Load Balancing](load-balancing.md)** - Multiple server configuration
- **[Monitoring](monitoring.md)** - Application monitoring, logging
- **[Backup Strategy](backup-strategy.md)** - Database and file backups

## DevOps & CI/CD
- **[GitHub Actions](github-actions.md)** - Automated testing and deployment
- **[Testing Pipeline](testing-pipeline.md)** - Automated test execution
- **[Quality Gates](quality-gates.md)** - Code quality checks

## Maintenance
- **[Updates & Patches](updates.md)** - Laravel updates, security patches
- **[Performance Monitoring](performance-monitoring.md)** - APM, profiling
- **[Troubleshooting](troubleshooting.md)** - Common production issues