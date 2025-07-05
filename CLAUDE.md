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

## Recent Development (2025-06-22)

### Email Verification System (/verify.php)
- Created modern 6-digit code verification page
- Auto-advance between input fields
- Support for numeric-only codes via `?type=numeric` parameter
- Direct link verification with `?code=XXXXXX` and auto-submit
- Real validation using database (no fake test codes)
- "Send a new code" as hard link (not AJAX) for better tracking
- Responsive design with proper error/success messaging

### Universal Layout System
- Implemented universal header spacing in `bg_header.inc`
- Default 2rem spacing between header and content
- Override with `$header_flush = true` for pages needing flush content
- Fixed sticky footer implementation across all pages
- Proper flexbox layout with `page-wrapper` structure

### Code Standards Updates
- Use `$additionalstyles` for CSS (not inline `<style>` tags)
- Real processes in test mode (no fake data/messages)
- Test mode (`?test=1`) bypasses account lifecycle but uses real validation
- Always check `headers_sent()` before redirects in production code
- **NEVER use apostrophes in ANY comments (PHP, JavaScript, CSS)** - Always use unconjugated forms (e.g., "do not" instead of "don't", "cannot" instead of "can't", "it is" instead of "it's")

### Database Enhancements
- Added `numeric_only` flag to `getvalidationcodes()` function
- Generates true random 6-digit codes when requested
- Maintains backward compatibility with alphanumeric codes
- Fixed test mode to use numeric user_id (999999) for database compatibility

## Header System Updates (2025-06-22a)

### Homepage Header Enhancements
- Transparent header with black 80% opacity and blur effect
- White glow/shadow effects for text visibility
- Special styling for homepage vs regular pages

### Navigation Improvements
- Right-aligned navigation menu (updated center-nav in bg_header.css)
- Responsive breakpoints:
  - Nav menu hidden at <992px (large screens only)
  - Sign Up button visible down to 576px
  - Buttons always flush right with `ms-auto`

### Button Styling Standards
- Sign Up: `btn-secondary` with transparent background
- Login: `btn-primary` with transparent background
- Both use 25px border-radius for oval shape
- Hover effects with background color fill
- `white-space: nowrap` prevents text wrapping

### Header Layout Structure
- Logo: `col-auto` (minimal space)
- Navigation: `col` (fills available space)
- Buttons: `col-auto ms-auto` (flush right)
- This ensures proper responsive behavior

## Coding Patterns and Standards

### Bootstrap 5 Implementation
- **Primary Framework**: Bootstrap 5.3.x (loaded from CDN)
- **Grid System**: Use Bootstrap's 12-column grid with responsive breakpoints
- **Components**: Utilize Bootstrap components (cards, modals, alerts, etc.) before custom solutions
- **Utilities**: Leverage Bootstrap utility classes for spacing, colors, and responsive design

### Include File System (.inc files)
- **Component Includes**: Located in `/core/components/v3/` and `/core/components/v7/`
- **Page Structure**:
  ```php
  include 'bg_pagestart.inc';    // HTML head, meta tags, CSS/JS includes
  include 'bg_header.inc';       // Site header and navigation
  // Page content here
  include 'bg_footer.inc';       // Footer and closing tags
  ```
- **Conditional Includes**: Use variables to control include behavior (e.g., `$header_flush = true`)
- **Path Resolution**: Use `$installpath` for absolute paths to includes

### PHP Coding Standards
- **File Naming**: 
  - Classes: `class.{name}.php` (lowercase)
  - Includes: `{component}.inc` or `{component}.inc.php`
  - Pages: `{pagename}.php` (lowercase, no spaces)
- **Variable Naming**: 
  - Use descriptive names with underscores: `$user_id`, `$session_token`
  - Global variables from site-controller: `$site`, `$mode`, `$installpath`
- **Database Queries**:
  - Always use prepared statements via PDO
  - Use the Database class methods: `query()`, `get_rows()`, `get_row()`
  - Escape output with `htmlspecialchars()` or `$system->cleanforhtml()`

### Frontend Development
- **CSS Organization**:
  - Main styles: `/public/css/core-v3-main.css`
  - Component styles: `/public/css/bg_{component}.css`
  - Use `$additionalstyles` array for page-specific CSS
- **JavaScript**:
  - jQuery 3.6.0 is available globally
  - Main scripts: `/public/js/core-v3-main.js`
  - Component scripts: `/public/js/{component}.js`
  - Use `$additionalscripts` array for page-specific JS

### Bootstrap 5 Specific Patterns
- **Forms**:
  ```html
  <div class="mb-3">
    <label for="fieldname" class="form-label">Label</label>
    <input type="text" class="form-control" id="fieldname" name="fieldname">
  </div>
  ```
- **Buttons**:
  - Primary actions: `btn btn-primary`
  - Secondary actions: `btn btn-secondary`
  - Danger/Delete: `btn btn-danger`
  - Custom border radius: `style="border-radius: 25px"`
- **Cards**: Use for content sections and forms
  ```html
  <div class="card">
    <div class="card-header">Title</div>
    <div class="card-body">Content</div>
  </div>
  ```
- **Responsive Utilities**:
  - Hide on mobile: `d-none d-md-block`
  - Mobile only: `d-block d-md-none`
  - Responsive columns: `col-12 col-md-6 col-lg-4`

### Page Layout Pattern
```php
<?php
// Standard page setup
include '../core/site-controller.php';
$pagetitle = "Page Title";
$additionalstyles = [];
$additionalscripts = [];

// Page logic here

// Output
include $installpath . 'core/components/v3/bg_pagestart.inc';
include $installpath . 'core/components/v3/bg_header.inc';
?>

<div class="container mt-4">
    <!-- Page content -->
</div>

<?php
include $installpath . 'core/components/v3/bg_footer.inc';
?>
```

### AJAX Patterns
- AJAX endpoints in `/myaccount/ajax/` or `/admin/ajax/`
- Return JSON responses: `header('Content-Type: application/json')`
- Use POST for data modifications, GET for retrievals
- Include CSRF token validation for POST requests

### Error Handling
- Development mode: Errors displayed (`$errormode = 'showerrors'`)
- Production mode: Errors logged to `bg_errors` table
- User-friendly error messages via `$system->addmessage()`
- Redirect with messages: `$system->addmessage('error', 'Error text'); header('Location: ...');`

### Session Management
- Sessions initialized in site-controller.php
- Access user data: `$account->getuser()` or `$_SESSION['user']`
- Check authentication: `if ($account->isloggedin()) { ... }`
- Role checking: `if ($account->checkrole('admin')) { ... }`

### Mobile Responsiveness
- Mobile-first approach using Bootstrap breakpoints
- Test at: 576px (sm), 768px (md), 992px (lg), 1200px (xl)
- Use Bootstrap's responsive utilities extensively
- Touch-friendly UI elements (minimum 44px touch targets)
## Documentation Process

### Posting to docs.birthdaygold.cloud

When creating documentation for new features or processes, post them to the Birthday Gold documentation site:

```bash
python3 /mnt/w/BIRTHDAY_SERVER/outline_api/post_content_directly.py \
  --title="[Category]: [Feature Name]" \
  --content="[Markdown content]" \
  --publish
```

### Documentation Standards

Follow the Birthday Gold documentation template:
- **Title Format**: `[Category]: [Feature/Process Name]` (e.g., "Account: Multi-Method Login System")
- **Section Headers**: Use emojis (✅, 📁, 🧰, 🐍, 🖥️, 🌐, 🚀)
- **Structure**:
  1. Overview
  2. ✅ Feature Implementation
  3. 📁 File Structure
  4. 🧰 Key Components
  5. 🐍 Core Functions (with code examples)
  6. 🖥️ User Experience Flow
  7. 🌐 Implementation Details
  8. ✅ Testing Checklist
  9. 🚀 Security Considerations
  10. **Intent** section (with horizontal rules)
  11. **Technical Details** section (dates, files, commits)
  12. Future Enhancements

### Quick Documentation Command

Post documentation without creating local files:
```bash
# Generate content inline and post directly
python3 /mnt/w/BIRTHDAY_SERVER/outline_api/post_content_directly.py \
  --title="Feature: New Feature Name" \
  --content="# Content here..." \
  --collection="752557d2-4486-42cd-aff2-8efa8bed3ee8" \
  --publish
```

Default collection: BG-Internal (`752557d2-4486-42cd-aff2-8efa8bed3ee8`)

### Example Documentation Template

When asked to document a feature, use this structure:

```markdown
# [Category]: [Feature Name]

## Overview
Brief description of the feature and its purpose.

## ✅ Feature Implementation

### 📁 1. File Structure
List of modified/created files

### 🧰 2. Key Components
Main components and their roles

### 🐍 3. Core Functions
Code examples with syntax highlighting

### 🖥️ 4. User Experience Flow
Step-by-step user interaction

### 🌐 5. Implementation Details
Technical specifications

### ✅ 6. Testing Checklist
- [ ] Test item 1
- [ ] Test item 2

### 🚀 7. Security Considerations
Security measures and validations

---

**Intent:**
Purpose and use cases

---

**Technical Details:**
* **Implementation Date:** [Date]
* **Pages:** relevant files
* **Classes:** relevant classes
* **Database Tables:** relevant tables
* **Commits:** commit hashes

---

## Future Enhancements
Potential improvements
```
EOF < /dev/null
