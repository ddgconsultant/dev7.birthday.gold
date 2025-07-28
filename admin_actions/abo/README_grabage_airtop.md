# ABO Grabage AIRTOP Escalation Implementation

## Overview
Implemented AIRTOP escalation for the `abo_grabage` processor to extract age requirements with higher accuracy using AI-powered browser automation.

## Changes Made

### 1. Created Global Age Defaults
- Added `$bg_age_requirements_defaults` array in `/core/site-arrays.inc`
- Defaults: min=0, max=150 (triggers escalation when using defaults)
- Additional values: legal_age=18, alcohol_age=21, rental_age=25

### 2. Created abo_grabage_airtop.php
- AI-powered age requirement extraction using AIRTOP
- Checks multiple URLs: terms, privacy, signup, rewards pages
- Returns structured age data with confidence levels
- Falls back to defaults (0-150) if no specific ages found

### 3. Updated abo_grabage.php
- Now uses global defaults from `$bg_age_requirements_defaults`
- Automatically escalates to AIRTOP when:
  - Source is 'default' (no age info found)
  - Confidence is 'low' and source is not 'website'
- Creates new task record for AIRTOP processor

### 4. Database Configuration
- Created SQL files to add processors to bg_config:
  - `abo_add_grabage_processors.sql` - Just grabage processors
  - `abo_add_missing_processors.sql` - All missing processors
- Updated main `abo_config_inserts.sql` with all processors

## How It Works

1. `abo_grabage` runs first, tries pattern matching
2. If results have low confidence, it escalates to AIRTOP
3. `abo_grabage_airtop` uses AI to analyze pages thoroughly
4. Returns age requirements with higher confidence

## To Deploy

1. Execute the SQL to add processors:
   ```sql
   -- Run one of these:
   -- Option 1: Just grabage processors
   SOURCE /admin_actions/abo/abo_add_grabage_processors.sql;
   
   -- Option 2: All missing processors
   SOURCE /admin_actions/abo/abo_add_missing_processors.sql;
   ```

2. The processors will automatically run based on their schedules:
   - `abo_grabage`: Every 15 minutes
   - `abo_grabage_airtop`: Every 30 minutes (for escalated tasks)

## Testing

To test a specific company:
```
/admin_actions/abo/abo_grabage.php?rawid=COMPANY_ID
/admin_actions/abo/abo_grabage_airtop.php?rawid=COMPANY_ID&retrigger=1
```