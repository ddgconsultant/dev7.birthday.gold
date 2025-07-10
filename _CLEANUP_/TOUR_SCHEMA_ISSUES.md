# Tour Page Database Schema Issues

## Overview
The original `tour.php` (and initial refactor) were written expecting database columns that don't exist in the base schema. This suggests either:
1. Production database has migrations not reflected in schema files
2. The code was written anticipating future schema changes
3. Different environments have different schemas

## Missing Columns Found

### 1. `bg_company_locations` table
**Expected but missing:**
- `latitude` DECIMAL(10, 8)
- `longitude` DECIMAL(11, 8)  
- `phone` VARCHAR(20)
- `name` VARCHAR(255)
- `is_primary` TINYINT(1)

**Actual columns:**
- location_id, company_id, address, city, state, zip_code, country, is_verified, create_dt, modify_dt, status

### 2. `bg_companies` table
**Expected but missing:**
- `company_logo` (actually stored in bg_company_attributes)
- `latitude` 
- `longitude`
- `address`
- `city`
- `state`
- `zip_code`

**Note:** Company location data should be in `bg_company_locations`, not `bg_companies`

## How Data Actually Works

### Company Logo
- Stored in `bg_company_attributes` table
- Retrieved via `$app->getcompany()` which does the join:
```sql
LEFT JOIN bg_company_attributes AS a 
ON c.company_id = a.company_id 
AND a.category = "company_logos" 
AND a.grouping = "primary_logo"
```

### Company Locations
- Primary company address stored in `bg_company_locations`
- Multiple locations per company supported
- But NO coordinates in base schema

### Coordinates Handling
Based on code analysis:
- `tour-v2.php` uses client-side Google Maps geocoding
- Coordinates might be stored in attributes table
- Or added via migrations not in schema files

## Fixes Applied

### 1. Removed non-existent columns from SQL:
```sql
-- Removed: cl.latitude, cl.longitude, cl.phone
-- Removed: c.company_logo, c.latitude, c.longitude, c.address, etc.
SELECT t.*, 
    cl.location_id as cl_location_id,
    cl.address as cl_address,
    cl.city as cl_city,
    cl.state as cl_state,
    cl.zip_code as cl_zip_code,
    c.company_id,
    c.company_name
FROM bg_user_tours t 
LEFT JOIN bg_companies c ON t.company_id = c.company_id
LEFT JOIN bg_company_locations cl ON t.location_id = cl.location_id
```

### 2. Updated PHP logic:
- Removed references to non-existent columns
- Let `$app->getcompany()` handle logo retrieval
- Fall back to client-side geocoding for coordinates

## Recommended Schema Updates

To make the code work as originally intended:

```sql
-- Add location columns to bg_company_locations
ALTER TABLE bg_company_locations 
ADD COLUMN name VARCHAR(255) NULL AFTER company_id,
ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER country,
ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude,
ADD COLUMN phone VARCHAR(20) NULL AFTER longitude,
ADD COLUMN is_primary TINYINT(1) DEFAULT 0 AFTER phone,
ADD INDEX idx_lat_lng (latitude, longitude);

-- Or use attributes table for flexibility
INSERT INTO bg_company_locations_attributes (location_id, name, value)
VALUES 
  (1, 'latitude', '39.7392'),
  (1, 'longitude', '-104.9903');
```

## Impact on Functionality

Without these columns:
1. **Location picker** - Can't calculate distances or show on map
2. **Route optimization** - No coordinates for directions
3. **Distance filtering** - Can't filter "within X miles"
4. **Map display** - Falls back to geocoding addresses

## Testing Results

After removing non-existent columns:
- ✅ No more SQL errors (SQLSTATE[42S22])
- ✅ Page loads without fatal errors
- ⚠️ Limited functionality without coordinates
- ⚠️ Requires client-side geocoding fallback

## Next Steps

1. **Check production database** - See if columns exist there
2. **Run migrations** - Add missing columns if needed
3. **Update schema files** - Keep documentation current
4. **Consider alternatives** - Use attributes table or client-side geocoding