# Floating Labels Form Conversion Instructions

## Overview
Convert existing PHP forms to use floating labels for text inputs while maintaining traditional labels for complex fields (dropdowns, checkboxes). Implementation should be primarily CSS-based with minimal HTML changes.

## 1. Text Input Fields (name, email, phone, password, etc.)

### HTML Structure:
```html
<div class="floating-label-group">
    <input type="text" 
           class="form-control floating-input" 
           id="firstname" 
           name="firstname" 
           placeholder=" "
           value="<?php echo htmlspecialchars($values['firstname'] ?? ''); ?>"
           required>
    <label for="firstname" class="floating-label">First Name</label>
    <?php if (!empty($errors['firstname'])): ?>
        <div class="invalid-feedback"><?php echo $errors['firstname']; ?></div>
    <?php endif; ?>
</div>
```

### Key Points:
- Use `placeholder=" "` (single space) to trigger CSS :placeholder-shown selector
- Add `floating-label-group` wrapper div
- Add `floating-input` and `floating-label` classes
- Keep existing PHP error handling

## 2. Date of Birth Fields

### HTML Structure:
```html
<div class="form-group">
    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
    <div class="date-input-group">
        <select class="form-control date-select" name="birth_month" required>
            <option value="">Month</option>
            <!-- options -->
        </select>
        <select class="form-control date-select" name="birth_day" required>
            <option value="">Day</option>
            <!-- options -->
        </select>
        <select class="form-control date-select" name="birth_year" required>
            <option value="">Year</option>
            <!-- options -->
        </select>
    </div>
    <small class="form-text text-muted">We'll use this to notify you of birthday rewards</small>
</div>
```

### Key Points:
- Keep traditional label above dropdowns
- Use `date-input-group` for consistent styling
- Maintain existing dropdown logic

## 3. Checkbox Fields

### HTML Structure:
```html
<div class="checkbox-group">
    <input type="checkbox" 
           class="form-check-input custom-checkbox" 
           id="terms" 
           name="terms" 
           value="1" 
           <?php echo !empty($values['terms']) ? 'checked' : ''; ?>>
    <label class="form-check-label checkbox-label" for="terms">
        I agree to the <a href="/terms">Terms</a> and <a href="/privacy">Privacy Policy</a>
    </label>
    <?php if (!empty($errors['terms'])): ?>
        <div class="invalid-feedback d-block"><?php echo $errors['terms']; ?></div>
    <?php endif; ?>
</div>
```

### Key Points:
- Use `checkbox-group` wrapper
- Add `custom-checkbox` and `checkbox-label` classes
- Keep clickable label functionality

## 4. Required CSS Implementation

```css
/* Floating Label Styles */
.floating-label-group {
    position: relative;
    margin-bottom: 1.5rem;
}

.floating-input {
    background: transparent;
    border: none;
    border-bottom: 2px solid #e9ecef;
    border-radius: 0;
    padding: 1rem 0 0.5rem 0;
    font-size: 1rem;
    line-height: 1.5;
    transition: all 0.3s ease;
    width: 100%;
    min-height: 44px; /* Touch target */
}

.floating-input:focus {
    outline: none;
    border-bottom-color: var(--bs-primary);
    box-shadow: none;
}

.floating-input.is-invalid {
    border-bottom-color: #dc3545;
}

.floating-label {
    position: absolute;
    left: 0;
    top: 1rem;
    color: #6c757d;
    font-size: 1rem;
    transition: all 0.3s ease;
    pointer-events: none;
    transform-origin: left top;
}

/* Float label when input is focused or has content */
.floating-input:focus + .floating-label,
.floating-input:not(:placeholder-shown) + .floating-label {
    transform: translateY(-1.25rem) scale(0.85);
    color: var(--bs-primary);
}

.floating-input:focus.is-invalid + .floating-label,
.floating-input:not(:placeholder-shown).is-invalid + .floating-label {
    color: #dc3545;
}

/* Date Input Styling */
.date-input-group {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.date-select {
    flex: 1;
    min-width: 100px;
    min-height: 44px;
    border: none;
    border-bottom: 2px solid #e9ecef;
    border-radius: 0;
    background: transparent;
    padding: 0.5rem 0;
    transition: border-color 0.3s ease;
}

.date-select:focus {
    outline: none;
    border-bottom-color: var(--bs-primary);
    box-shadow: none;
}

/* Checkbox Styling */
.checkbox-group {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    min-height: 44px; /* Touch target */
}

.custom-checkbox {
    width: 18px;
    height: 18px;
    margin: 0;
    flex-shrink: 0;
    margin-top: 0.125rem; /* Align with first line of text */
}

.checkbox-label {
    flex: 1;
    margin: 0;
    cursor: pointer;
    line-height: 1.5;
    min-height: 44px;
    display: flex;
    align-items: center;
}

/* Form Labels (for non-floating fields) */
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

/* Form Groups */
.form-group {
    margin-bottom: 1.5rem;
}

/* Error States */
.invalid-feedback {
    display: none;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

.floating-input.is-invalid ~ .invalid-feedback,
.date-select.is-invalid ~ .invalid-feedback,
.custom-checkbox.is-invalid ~ .invalid-feedback,
.invalid-feedback.d-block {
    display: block;
}

/* Mobile Optimizations */
@media (max-width: 576px) {
    .floating-input {
        font-size: 16px; /* Prevent zoom on iOS */
    }
    
    .date-input-group {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .date-select {
        min-width: 100%;
    }
}

/* Focus and Hover States */
.floating-input:hover:not(:focus) {
    border-bottom-color: #adb5bd;
}

.date-select:hover:not(:focus) {
    border-bottom-color: #adb5bd;
}

.custom-checkbox:hover {
    cursor: pointer;
}

/* Desktop-specific adjustments for better visual balance */
@media (min-width: 992px) {
    .floating-input {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 2rem 1rem 0.375rem 1rem;
        background: white !important;
        transition: all 0.2s ease;
    }
    
    .floating-input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }
    
    /* Desktop: Placeholder positioned lower in the field */
    .floating-input::placeholder {
        transform: translateY(2.5rem);
        opacity: 0.6;
        transition: all 0.3s ease;
        line-height: 1;
    }
    
    .floating-input:focus::placeholder {
        opacity: 0;
    }
    
    /* Desktop: Adjust label positioning */
    .floating-label {
        left: 1rem;
        top: 1.125rem;
    }
    
    .floating-input:focus + .floating-label,
    .floating-input:not(:placeholder-shown) + .floating-label {
        transform: translateY(-1.1rem) scale(0.85);
    }
}

/* Accessibility */
.floating-input:focus-visible,
.date-select:focus-visible,
.custom-checkbox:focus-visible {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
}

/* Animation for better UX */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.invalid-feedback {
    animation: fadeInUp 0.3s ease;
}
```

## 5. Implementation Steps

### Step 1: Identify Field Types
- Text inputs: name, email, phone, password, textarea
- Complex fields: date dropdowns, checkboxes, radio buttons, file uploads
- Keep existing: selects, radio groups, file inputs

### Step 2: Convert Text Inputs
1. Wrap in `floating-label-group` div
2. Add `floating-input` class to input
3. Add `placeholder=" "` attribute
4. Convert existing label to `floating-label` class
5. Move label after input in HTML

### Step 3: Style Complex Fields
1. Add appropriate wrapper classes
2. Ensure consistent underline styling
3. Maintain 44px touch targets
4. Keep traditional labels

### Step 4: Test and Validate
1. Test on mobile devices (iOS Safari zoom issue)
2. Verify accessibility with screen readers
3. Test form validation states
4. Check touch target sizes

## 6. Accessibility Requirements

- Maintain proper label associations (`for` and `id` attributes)
- Include `aria-describedby` for error messages
- Ensure focus indicators are visible
- Test with keyboard navigation
- Verify screen reader compatibility

## 7. Browser Support

- Modern browsers (Chrome 60+, Firefox 60+, Safari 12+)
- iOS Safari 12+ (16px font size to prevent zoom)
- Android Chrome 60+
- Graceful degradation for older browsers

## 8. Common Gotchas

1. **iOS Zoom Prevention**: Use 16px font size on mobile
2. **Placeholder Trick**: Must use `placeholder=" "` (single space)
3. **Label Position**: Label must come after input in HTML for CSS selectors
4. **Touch Targets**: Minimum 44px height for mobile usability
5. **Validation States**: Ensure error styling works with floating labels

## 9. Testing Checklist

- [ ] Text inputs float properly on focus
- [ ] Labels animate smoothly (0.3s transition)
- [ ] Form validation displays correctly
- [ ] Mobile touch targets are 44px minimum
- [ ] iOS doesn't zoom on input focus
- [ ] Screen readers announce labels correctly
- [ ] Keyboard navigation works properly
- [ ] Error states are clearly visible
- [ ] All links in labels are clickable
- [ ] Form submission works as expected

## 10. Example Usage

When presented with a PHP form file, apply these patterns:

1. **Analyze** the existing form structure
2. **Identify** text inputs vs complex fields
3. **Convert** text inputs to floating label pattern
4. **Style** complex fields with traditional labels
5. **Test** the implementation thoroughly
6. **Provide** complete CSS alongside HTML changes

Remember: The goal is minimal HTML changes with maximum CSS-based implementation for maintainability and consistency.

## 11. Global Stylesheet Implementation

For site-wide implementation, create a dedicated CSS file that can be included across all pages:

### Recommended File Structure:
```
/public/css/floating-labels.css      # Global floating label styles
/public/scss/floating-labels.scss    # Source SCSS for easier customization
```

### Integration Approach:

1. **Create Global Stylesheet** (`/public/css/floating-labels.css`):
   - Include all CSS from Section 4 above
   - Add any site-specific color variables
   - Ensure Bootstrap variable compatibility

2. **Include in Base Template**:
   ```php
   <!-- In your base template or header include -->
   <link rel="stylesheet" href="/public/css/floating-labels.css">
   ```

3. **SCSS Variables** (if using SCSS):
   ```scss
   // floating-labels.scss
   $floating-label-color: #6c757d;
   $floating-label-focus-color: var(--bs-primary);
   $floating-input-border-color: #e9ecef;
   $floating-input-focus-border-color: var(--bs-primary);
   $floating-label-transition: all 0.3s ease;
   
   // Import the main styles with variables
   ```

4. **Class Naming Convention**:
   - Prefix with `fl-` for floating label specific classes if needed
   - This avoids conflicts with existing styles
   - Example: `fl-group`, `fl-input`, `fl-label`

5. **Progressive Enhancement**:
   - Ensure forms work without JavaScript
   - CSS-only implementation for better performance
   - Graceful fallback for older browsers

### Benefits of Global Implementation:
- **Consistency**: Same behavior across all forms
- **Maintainability**: Single source of truth for styles
- **Performance**: One cached CSS file
- **Flexibility**: Easy to update site-wide
- **Customization**: SCSS variables for theming