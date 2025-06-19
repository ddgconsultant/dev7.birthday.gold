# Bootstrap Nav-Tabs Comparison: v3 vs v7

## Key Differences Found

### 1. Body Styles (Critical Issue)

**v7 bg_header.css (lines 2-11):**
```css
body {
  padding-top: 56px;
  overflow-x: hidden; /* ⚠️ THIS IS THE ISSUE */
  overflow-y: auto;
  min-height: 100vh;
  transition: top 0.5s ease, left 0.5s ease, width 0.5s ease, height 0.5s ease;
  font-family: 'DM Sans', sans-serif;
}
```

**v3:** No body styles with overflow-x: hidden

### 2. Additional CSS Files

**v7 loads:**
- `/public/css/v7/bg_header.css` (contains problematic body styles)

**v3 does NOT load:**
- No bg_header.css file

### 3. Page Structure

**v7 (lines 232-237):**
```css
body { 
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "DM Sans", sans-serif;
    margin: 0;
    padding: 0;
    background: #f8f9fa;
    padding-bottom: 90px; /* Space for mobile bottom nav */
}
```

**v7 closes head and opens body tag (lines 382-383):**
```html
</head>
<body class="iframe-mode">
```

**v3:** Simply closes PHP block without adding body styles

### 4. Main Content Wrapper

**v7 (lines 462-464):**
```html
<!-- Main content wrapper -->
<main id="main-content" role="main">
```

**v3:** No main wrapper added

## Root Cause

The `overflow-x: hidden` on the body element in v7's bg_header.css is causing the Bootstrap nav-tabs to display vertically instead of horizontally. This CSS property can interfere with flexbox layouts, especially when combined with Bootstrap's flex-based navigation components.

## Recommended Fix

Remove or override the `overflow-x: hidden` property from the body element in v7's bg_header.css, or add a more specific rule that excludes pages using nav-tabs:

```css
/* Option 1: Remove overflow-x: hidden entirely */
body {
  padding-top: 56px;
  /* overflow-x: hidden; -- REMOVED */
  overflow-y: auto;
  min-height: 100vh;
  transition: top 0.5s ease, left 0.5s ease, width 0.5s ease, height 0.5s ease;
  font-family: 'DM Sans', sans-serif;
}

/* Option 2: Add exception for nav-tabs */
body:has(.nav-tabs) {
  overflow-x: visible !important;
}

/* Option 3: Target nav-tabs specifically */
.nav-tabs {
  overflow-x: visible !important;
  display: flex !important;
  flex-wrap: nowrap !important;
}
```

## Additional Notes

1. The v7 pagestart includes many enhancements for mobile, PWA, and performance optimization
2. The additional CSS file bg_header.css in v7 adds significant styling that doesn't exist in v3
3. The body padding-bottom: 90px in v7 suggests a mobile bottom navigation that might also be affecting layout