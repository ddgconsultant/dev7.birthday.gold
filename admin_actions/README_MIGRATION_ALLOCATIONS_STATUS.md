# Database Migration Required: bg_user_allocations.status

## Overview
The `bg_user_allocations` table needs to be updated to change the `status` column from ENUM to VARCHAR(32) to support the new 'pending' status value used in recommend-business.php.

## Migration SQL
The migration file is located at:
`/core/dbschema/migration_user_allocations_status_varchar.sql`

## How to Apply the Migration

### Option 1: Via phpMyAdmin or MySQL Client
1. Connect to your database
2. Run the following SQL:
```sql
ALTER TABLE `bg_user_allocations` 
MODIFY COLUMN `status` VARCHAR(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active';

ALTER TABLE `bg_user_allocations` ADD INDEX `idx_status` (`status`);
```

### Option 2: Via Command Line
```bash
mysql -u [username] -p [database_name] < /path/to/core/dbschema/migration_user_allocations_status_varchar.sql
```

### Option 3: Via PHP Script
Run the migration script:
```bash
php /admin_actions/execute_migration_allocations_status.php
```

## Verification
After running the migration, verify it worked:
```sql
SHOW COLUMNS FROM bg_user_allocations WHERE Field = 'status';
```

The Type should show `varchar(32)` instead of an ENUM.

## What This Fixes
- Allows recommend-business.php to create 'pending' allocations
- Provides flexibility for future status values
- Fixes SQL errors when inserting new allocation records

## Status Values
After migration, the following status values are supported:
- active
- pending
- depleted
- expired
- revoked
- (and any future status values up to 32 characters)