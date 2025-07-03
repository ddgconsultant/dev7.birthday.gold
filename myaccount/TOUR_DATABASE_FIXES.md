# Tour Database Schema Fixes

## Issue Found
The original `tour.php` (and my initial refactor) were trying to access columns that don't exist in the `bg_company_locations` table:
- `cl.latitude` 
- `cl.longitude`
- `cl.phone`
- `cl.name`

## Current Schema
The `bg_company_locations` table only has these columns:
- location_id
- company_id
- address
- city  
- state
- zip_code
- country
- is_verified
- create_dt
- modify_dt
- status

## Fixes Applied

### 1. Updated SQL Query
Removed the non-existent columns from the SELECT statement:
```sql
-- Removed: cl.latitude, cl.longitude, cl.phone
SELECT t.*, 
    cl.location_id as cl_location_id,
    cl.address as cl_address,
    cl.city as cl_city,
    cl.state as cl_state,
    cl.zip_code as cl_zip_code,
    c.company_name,
    c.company_logo,
    c.latitude as c_latitude,
    c.longitude as c_longitude,
    ...
```

### 2. Updated Location Logic
Since company locations don't have coordinates, the code now falls back to using the company's default coordinates even when a specific location is selected:
```php
if (!empty($item_company['cl_location_id'])) {
    // Note: Using company's default coordinates since locations don't have lat/lng yet
    $company_data['latitude'] = $item_company['c_latitude'] ?? $company_data['latitude'] ?? null;
    $company_data['longitude'] = $item_company['c_longitude'] ?? $company_data['longitude'] ?? null;
    // But still use the location's address
    $company_data['address'] = $item_company['cl_address'];
    ...
}
```

### 3. Fixed AJAX Search Handler
Updated `/myaccount/ajax/tour-search-locations.php` to return NULL for latitude/longitude:
```sql
SELECT location_id, '' as name, address, city, state, zip_code, 
       NULL as latitude, NULL as longitude 
FROM bg_company_locations
```

## Recommended Database Fix

To fully support the location picker feature, add these columns to `bg_company_locations`:

```sql
ALTER TABLE bg_company_locations 
ADD COLUMN name VARCHAR(255) NULL AFTER company_id,
ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER country,
ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude,
ADD COLUMN phone VARCHAR(20) NULL AFTER longitude,
ADD COLUMN is_primary TINYINT(1) DEFAULT 0 AFTER phone;

-- Add indexes for performance
ALTER TABLE bg_company_locations
ADD INDEX idx_lat_lng (latitude, longitude);
```

## Impact
Without latitude/longitude on locations:
- The location picker will show locations but can't calculate distances
- Map markers will use the company's default coordinates even when a specific location is selected
- The "within X miles" radius search won't work properly

The page now loads without errors, but the location picker feature is limited until the database schema is updated.