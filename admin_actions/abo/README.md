# Automation Business Onboarding (ABO)

This directory contains all the automation schedulers for processing user-submitted business recommendations through the onboarding pipeline.

## Directory Structure

```
/admin_actions/abo/
├── README.md                    # This file
├── abo_initialize.php          # One-time setup to configure automation steps in bg_config
├── abo_processsubmission.php   # Process new submissions (categorize, check duplicates)
├── abo_grabgoogleapp.php       # Extract Google Play app information
├── abo_grabiosapp.php          # Extract iOS App Store information
├── abo_grabsocialmedia.php     # Extract social media links and handles
├── abo_grabmetadata.php        # Extract meta tags and business information
├── abo_grabimages.php          # Extract logos and business images
├── abo_grablocations.php       # Extract store locations and addresses
└── abo_grabbirthday.php        # Extract birthday program details
```

## Setup Instructions

1. Run the initialization script once to populate bg_config:
   ```bash
   curl "https://dev7.birthday.gold/admin_actions/abo/abo_initialize.php?key=YOUR_SCHEDULER_KEY"
   ```

2. Set up cron jobs for each scheduler:
   ```bash
   # Process new submissions every 5 minutes
   */5 * * * * curl "https://birthday.gold/admin_actions/abo/abo_processsubmission.php?key=YOUR_KEY"
   
   # Run data collectors every 10 minutes
   */10 * * * * curl "https://birthday.gold/admin_actions/abo/abo_grabgoogleapp.php?key=YOUR_KEY"
   */10 * * * * curl "https://birthday.gold/admin_actions/abo/abo_grabiosapp.php?key=YOUR_KEY"
   */10 * * * * curl "https://birthday.gold/admin_actions/abo/abo_grabsocialmedia.php?key=YOUR_KEY"
   */10 * * * * curl "https://birthday.gold/admin_actions/abo/abo_grabmetadata.php?key=YOUR_KEY"
   
   # Run image and location collectors every 15 minutes
   */15 * * * * curl "https://birthday.gold/admin_actions/abo/abo_grabimages.php?key=YOUR_KEY"
   */15 * * * * curl "https://birthday.gold/admin_actions/abo/abo_grablocations.php?key=YOUR_KEY"
   */15 * * * * curl "https://birthday.gold/admin_actions/abo/abo_grabbirthday.php?key=YOUR_KEY"
   ```

## How It Works

1. **User Submission**: Users submit businesses via `/recommend-business.php`
2. **Admin Review**: Admins review submissions at `/admin/business-submissions.php`
3. **Approval**: When approved, the system:
   - Updates company status to `approved_pending_data`
   - Copies automation steps from `bg_config` to `bg_company_attributes`
   - Each step starts with status `pending`
4. **Automation**: Each scheduler:
   - Finds companies with its corresponding `pending` task
   - Processes the task
   - Updates status to `completed`, `error`, or `skipped`
   - Logs results in `bg_company_attributes`

## Database Tables

### bg_config
Stores the master list of automation processors with configuration:
- `config_type`: 'automation_processor'
- `config_key`: Processor name (e.g., 'abo_processsubmission')
- `config_data`: JSON with scheduler details, frequency, etc.

### bg_company_attributes
Tracks progress for each company:
- `type`: 'onboarding_progress'
- `name`: Processor name
- `description`: Status (pending/in_progress/completed/error/skipped)

## Monitoring

View automation progress:
```sql
SELECT * FROM bg_automation_progress 
WHERE company_id = ? 
ORDER BY processor_name;
```

## Adding New Processors

1. Add entry to bg_config via abo_initialize.php
2. Create new scheduler file: `abo_newprocessor.php`
3. Add to cron schedule
4. Update this README