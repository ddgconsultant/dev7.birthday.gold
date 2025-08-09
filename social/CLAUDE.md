# CLAUDE.md - Social Module Instructions

## Overview
The `/social` module is a prototype social networking feature for Birthday Gold, allowing users to share birthday experiences, tips, and celebrations. Currently in mockup stage with UI templates but no backend implementation.

## Current State: UI Prototype Only
- **Status**: Non-functional mockup
- **Database**: No tables exist yet
- **Backend**: No API endpoints implemented
- **Data**: All placeholder/random generated

## Directory Structure
```
/social/
├── Main Pages (UI mockups):
│   ├── index.php          - Social feed with comments panel
│   ├── create.php         - Post creation form
│   ├── activity.php       - User activity feed
│   ├── search.php         - Search interface
│   ├── settings.php       - Social settings
│   ├── soundtrack.php     - Audio features
│   └── user-profile.php   - Profile page (minimal)
│
└── components/
    ├── Post Actions (empty stubs):
    │   ├── post-like.php
    │   ├── post-follow.php
    │   ├── post-comment-like.php
    │   ├── post-bookmark.php
    │   └── post-share.php
    │
    ├── Content Display:
    │   ├── postcontent-images.inc
    │   ├── postcontent-text.inc
    │   ├── postcontent-video.inc
    │   └── postcontent-*_audio.inc
    │
    └── UI Components:
        ├── write-comment.inc
        ├── overlay.inc
        ├── header-nav.inc
        └── js-*.inc (scrolling, etc.)
```

## Development Guidelines

### When Working on Social Module

1. **Understand Current State**
   - This is a UI prototype, not production code
   - All data is placeholder - no real functionality
   - Empty handler files are intentional placeholders

2. **Maintain Prototype Nature Until Backend Ready**
   - Keep using random/placeholder data for now
   - Do not create database tables without explicit request
   - Do not implement real API endpoints without approval

3. **UI Patterns to Follow**
   ```php
   // Split panel layout (desktop)
   <div class="left-panel">  // Comments
   <div class="right-panel"> // Content
   
   // Mobile overlay for comments
   <div class="comment-overlay d-lg-none">
   ```

4. **Planned Features (Not Yet Implemented)**
   - User posts with text, images, video, audio
   - Comments and nested replies
   - Like, bookmark, share functionality
   - Following other users
   - Activity notifications
   - Content search
   - Soundtrack/audio integration

## Future Implementation Requirements

### Database Tables Needed (Not Created Yet)
```sql
-- Proposed structure (DO NOT CREATE without approval)
bg_social_posts
bg_social_comments
bg_social_likes
bg_social_follows
bg_social_bookmarks
bg_social_media
bg_social_activity
```

### API Endpoints Needed
```
POST /api/social/post/create
GET  /api/social/feed
POST /api/social/post/{id}/like
POST /api/social/post/{id}/comment
GET  /api/social/user/{id}/activity
```

### Security Considerations for Future
- Content moderation system
- Rate limiting for posts/comments
- File upload validation for media
- XSS protection for user content
- Privacy settings per post
- Blocking/reporting system

## Current Mock Data Patterns

### Random Generation Examples
```php
// Activity feed
$activities = [
    'viewed post' => 'Title here',
    'commented on' => 'Comment text'
];
$numActivities = rand(10, 50);

// User avatars
$avatarNumber = rand(1, 10);
$avatarSrc = "/public/avatars/sample_users/placeholder_$avatarNumber.png";

// Placeholder content
$numComments = rand(0, 35);
```

## Component Interactions

### Comment System Structure
- Desktop: Fixed left panel with scrollable comments
- Mobile: Bottom overlay activated by button
- Write comment area at top of list
- Like counts and reply links per comment

### Media Handling (Planned)
- Image carousels with navigation
- Video player with controls
- Audio/soundtrack integration
- Mixed media posts support

## Styling Patterns

### CSS Classes
```css
.left-panel     /* Comments section */
.right-panel    /* Main content */
.comment-overlay /* Mobile comments */
.post-header    /* Post metadata */
.comment-item   /* Individual comment */
```

### Responsive Breakpoints
- Desktop: > 992px (shows both panels)
- Mobile: < 992px (overlay system)
- Touch targets: 44px minimum

## DO NOT Do These Things

1. **Do not implement real functionality** without explicit request
2. **Do not create database tables** - schema not finalized
3. **Do not add authentication checks** - handled by site-controller
4. **Do not remove placeholder data** - needed for UI testing
5. **Do not create API endpoints** - architecture not decided

## When Asked to Work on Social

### If request is for UI/display:
- Modify existing mockup files
- Maintain placeholder data approach
- Follow Bootstrap 5 patterns
- Ensure mobile responsiveness

### If request is for functionality:
- Confirm if real implementation is wanted
- Ask about database schema approval
- Verify API endpoint patterns
- Check security requirements

## Testing the Module

### Current Testing (UI Only)
```bash
# View social feed mockup
https://dev7.birthday.gold/social/

# Test responsive design
https://dev7.birthday.gold/social/index.php

# Check post creation form
https://dev7.birthday.gold/social/create.php
```

### Future Testing Needs
- Unit tests for API endpoints
- Integration tests for interactions
- Load testing for feed performance
- Security testing for user content
- Accessibility testing

## Common Tasks

### Adding New Mockup Content
```php
// Add to random generation arrays
$samplePosts[] = "New post title";
$sampleUsers[] = "NewUser";
```

### Modifying Layout
```php
// Keep split-panel structure
// Maintain mobile overlay system
// Use Bootstrap 5 components
```

### Preparing for Backend
```php
// Mark areas for future implementation
// TODO: Replace with API call
// TODO: Connect to database
// TODO: Add authentication
```

## Notes for Future Development

1. **Content Moderation**: Will need automated and manual review
2. **Performance**: Feed pagination and lazy loading required
3. **Storage**: Media files should use CDN (Backblaze B2)
4. **Analytics**: Track engagement metrics
5. **Notifications**: Real-time updates for interactions
6. **Privacy**: User-controlled visibility settings

---

**Status**: UI Prototype
**Ready for Production**: No
**Estimated Completion**: TBD
**Dependencies**: Database schema approval, API architecture decision