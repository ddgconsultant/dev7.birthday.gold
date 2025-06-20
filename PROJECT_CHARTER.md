# Birthday Gold Project Charter

## Project Overview

Birthday Gold is a SaaS platform that automates enrollment in birthday reward programs from various businesses. Our mission is to help users never miss out on birthday rewards by simplifying the enrollment process and managing their reward programs in one centralized location.

## Design Philosophy: Modern Minimalist SaaS

Our design approach follows the Modern Minimalist SaaS (Clean Enterprise UI) principles, emphasizing clarity, functionality, and professional aesthetics.

### Core Design Principles

#### 1. Minimalist Approach
- **Generous Whitespace**: Allow content to breathe with ample padding and margins
- **Clean Lines**: Use subtle shadows and borders for definition without clutter
- **Purpose-Driven Elements**: Every design element must serve a functional purpose
- **Content Hierarchy**: Clear visual hierarchy guides users through information

#### 2. Professional Typography
- **Type Hierarchy**:
  - Headlines: Bold, larger size (24-32px)
  - Subheadings: Semi-bold, medium size (18-20px)
  - Body Text: Regular weight, readable size (14-16px)
  - Captions/Labels: Light gray, smaller size (12-14px)
- **Font Family**: System fonts for optimal performance and native feel
  - Primary: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial
- **Line Height**: 1.5-1.6 for body text, 1.2-1.3 for headings
- **Letter Spacing**: Normal to slightly relaxed for improved readability

#### 3. Subtle Depth & Elevation
- **Shadow System**:
  - Cards: `box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08)`
  - Hover States: `box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12)`
  - Modals/Dropdowns: `box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15)`
- **Border Styles**: Light borders using `#e9ecef` or similar neutral colors
- **No Heavy Gradients**: Solid colors preferred, subtle gradients only when necessary

#### 4. Restrained Color Palette

##### Primary Colors
- **Primary Blue**: `#2563eb` (Buttons, links, active states)
- **Primary Hover**: `#1d4ed8` (Darker shade for interactions)

##### Neutral Palette
- **Pure White**: `#ffffff` (Backgrounds, cards)
- **Light Gray**: `#f8f9fa` (Page backgrounds, alternate sections)
- **Border Gray**: `#e9ecef` (Borders, dividers)
- **Text Gray**: `#6c757d` (Secondary text, labels)
- **Dark Gray**: `#343a40` (Primary text, headings)
- **Near Black**: `#212529` (Maximum contrast text)

##### Semantic Colors
- **Success**: `#10b981` (Green - confirmations, success messages)
- **Warning**: `#f59e0b` (Amber - warnings, attention needed)
- **Error**: `#ef4444` (Red - errors, destructive actions)
- **Info**: `#3b82f6` (Blue - informational messages)

#### 5. Functional Over Decorative
- **Clear CTAs**: Primary actions stand out without being aggressive
- **Icon Usage**: Icons support text, never replace critical labels
- **Hover States**: Subtle transitions (150-200ms) for interactive elements
- **Focus States**: Clear, accessible focus indicators for keyboard navigation

### Component Guidelines

#### Buttons
```css
/* Primary Button */
.btn-primary {
  background-color: #2563eb;
  color: #ffffff;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 500;
  border: none;
  transition: all 0.15s ease;
}

.btn-primary:hover {
  background-color: #1d4ed8;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

/* Secondary Button */
.btn-secondary {
  background-color: #ffffff;
  color: #343a40;
  border: 1px solid #e9ecef;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 500;
}
```

#### Cards
```css
.card {
  background: #ffffff;
  border-radius: 8px;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  padding: 24px;
  margin-bottom: 20px;
}
```

#### Forms
```css
.form-control {
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 10px 14px;
  font-size: 14px;
  transition: border-color 0.15s ease;
}

.form-control:focus {
  border-color: #2563eb;
  outline: none;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
```

### Layout Principles

#### Spacing System
- Base unit: 4px
- Common spacing values: 8px, 16px, 24px, 32px, 48px, 64px
- Consistent padding within components
- Generous margins between sections

#### Grid System
- 12-column responsive grid
- Maximum content width: 1200px
- Mobile-first approach
- Breakpoints:
  - Mobile: < 576px
  - Tablet: 576px - 768px
  - Desktop: > 768px

#### Page Structure
1. **Clean Header**: Logo left, navigation center/right, minimal height
2. **Hero Sections**: Large typography, clear value proposition, single CTA
3. **Content Sections**: Card-based layouts with clear separation
4. **Footer**: Organized link groups, subdued styling

### Implementation Guidelines

#### CSS Architecture
- Use CSS custom properties for theme values
- Implement utility classes for common patterns
- Maintain component-based styling
- Follow BEM or similar naming convention

#### Accessibility
- WCAG 2.1 AA compliance minimum
- Proper color contrast ratios (4.5:1 for normal text, 3:1 for large text)
- Keyboard navigation support
- Screen reader friendly markup

#### Performance
- Optimize for Core Web Vitals
- Lazy load images and non-critical resources
- Minimize CSS and JavaScript bundles
- Use system fonts to reduce load time

### Example Implementation

The password reset page (as shown in the screenshot) exemplifies our design principles:
- Clean, centered card layout with ample whitespace
- Clear typography hierarchy (heading, body text, labels)
- Subtle shadows and borders for depth
- Restrained use of brand color (blue button)
- Functional icons with supporting text
- Professional, trustworthy appearance

### Design Review Checklist

Before implementing any UI component, ensure it meets these criteria:
- [ ] Does it serve a clear functional purpose?
- [ ] Is the visual hierarchy obvious?
- [ ] Are interactive elements clearly indicated?
- [ ] Does it maintain consistency with existing components?
- [ ] Is the color usage restrained and meaningful?
- [ ] Are accessibility requirements met?
- [ ] Does it work well on mobile devices?
- [ ] Is the code clean and maintainable?

### Future Considerations

As the platform evolves, maintain these design principles while:
- Adapting to new user needs and feedback
- Incorporating modern web technologies
- Ensuring consistency across all touchpoints
- Balancing innovation with familiarity

---

*This charter serves as the foundation for all design decisions in the Birthday Gold platform. When in doubt, prioritize clarity, functionality, and user experience over decorative elements.*