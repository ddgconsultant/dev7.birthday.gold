# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Birthday Gold is a SaaS platform that automates enrollment in birthday reward programs from various businesses. It's a full-stack PHP web application with user dashboards, admin interfaces, payment processing, and comprehensive business enrollment automation.

## Architecture

### Core Framework
- **Custom PHP MVC-style framework** located in `/core/`
- Main bootstrap: `/core/site-controller.php`
- Classes directory: `/core/classes/` (Database, Account, App, System, etc.)
- UI components: `/core/components/v3/` (current version)

### Technology Stack
- **Backend**: PHP 7.4+ with PDO MySQL
- **Frontend**: Bootstrap 5.x, jQuery 3.6.0, custom SCSS
- **Database**: MySQL with comprehensive session tracking and analytics
- **Dependencies**: Managed via Composer (Stripe, Guzzle, PHPMailer, Firebase JWT, etc.)

### Directory Structure
```
/core/               - Application framework and classes
/admin/              - Administrative interface and tools
/myaccount/          - User dashboard and account management  
/api/                - RESTful API endpoints
/public/             - Static assets (CSS, JS, images)
/admin_actions/      - Deployment and automation scripts
/avatars/            - Avatar generation system
/presentation/       - WebSlides presentation system
```

## Environment Configuration

### Site Variables (in `/core/site-controller.php`)
```php
$site = 'dev7';        // Current environment (dev7, www, etc.)
$mode = 'dev';         // Environment mode (dev, production)
$errormode = 'showerrors'; // Error display (showerrors, hideerrors)
```

### Configuration Files
- External configs in `../ENV_CONFIGS/` directory
- Database config: `config-main-{mode}6.inc`
- AI config: `config-ai.inc`

## Development Workflow

### Local Development
- Development environment runs at: `https://dev7.birthday.gold`
- Error reporting enabled in dev mode
- Comprehensive session tracking for debugging

### Deployment
```bash
# Deploy from development to production
./admin_actions/deploy_www.sh -s dev7

# Manual deployment steps:
# 1. Clone from GitHub (private repo)
# 2. Update site-controller.php variables
# 3. Set proper file permissions
# 4. Generate version strings
```

### Database Operations
- Schema files: `/core/dbschema/`
- Main tables: users, enrollments, session tracking, payments
- Comprehensive error logging and rate limiting

## Key Components

### Authentication & Security
- Session-based authentication with comprehensive tracking
- CSRF protection with tokens
- IP-based rate limiting and lockout system
- Role-based access (admin, staff, user levels)

### Business Logic
- **Enrollment System**: Automated birthday reward program enrollment
- **Payment Processing**: Stripe integration with plan management
- **User Management**: Multi-level accounts (individual, parental, business)
- **Analytics**: Comprehensive session and error tracking

### API Structure
- RESTful endpoints in `/api/`
- OpenAPI documentation available
- CORS configured for subdomain access
- JWT authentication support

## Integration Points

### Third-party Services
- **Stripe**: Payment processing (`STRIPECONFIG`)
- **Backblaze B2**: CDN and file storage
- **PHPMailer**: Email services
- **IP Info**: Geolocation services
- **Telegram**: SMS/notification services

### External Systems
- **Rocket.Chat**: Team communication integration
- **Uptime Kuma**: Monitoring integration
- **Metabase**: Analytics dashboard
- **Leantime**: Project management

## Testing

### Available Test Suites
- Cypress tests in `/admin/cypress/` for user flows
- WebSlides has npm-based testing (`npm test`)
- No comprehensive PHP test suite currently

### Testing Commands
```bash
# Presentation/WebSlides testing
cd presentation/
npm install
npm run lint
npm test
```

## Maintenance

### Regular Tasks
- Database backups via `/admin_actions/scheduler--backup_database.sh`
- CDN file cleanup via `/admin_actions/scheduler--deleteoldcdnfiles.php`
- Statistics updates via `/admin_actions/scheduler--updatestats.php`

### Monitoring
- Session tracking in `bg_sessiontracking` table
- Error logging in `bg_errors` table  
- System availability monitoring via `/admin/systemavailability.php`

## Development Notes

### Code Conventions
- PHP classes use lowercase filenames: `class.{name}.php`
- UI versioning system (currently v3)
- Comprehensive error handling and logging
- Session-based state management

### Special Features
- **Avatar System**: Custom avatar generation in `/avatars/`
- **Social Features**: User posts and interactions in `/social/`
- **Multi-tenant**: Support for business/brand management
- **Presentation System**: WebSlides integration for admin presentations

### Database Schema
- Extensive user profile management
- Comprehensive enrollment tracking
- Payment and subscription management
- Analytics and session tracking
- Geographic and demographic data collection

## Security Considerations

- Rate limiting: 40 requests/second, 150 requests/minute
- IP-based lockout with exponential backoff
- CORS restricted to `*.birthday.gold` domains
- Comprehensive audit logging
- Session fingerprinting and device tracking