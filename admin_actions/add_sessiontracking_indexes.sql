-- ==================================================================================
-- PERFORMANCE FIX: Add missing indexes to bg_sessiontracking table
-- ==================================================================================
-- This table has 1.7M+ records but NO INDEX on user_id, causing slow queries
-- The recent_logins query was taking 5700ms (6 seconds!) without these indexes
--
-- Run this with appropriate database permissions:
-- mysql -h "71.33.250.235" -u root -p birthday_gold_www < add_sessiontracking_indexes.sql
-- ==================================================================================

-- Add composite index for user-based queries with date ordering
-- This will speed up queries like: WHERE user_id = X ORDER BY create_dt DESC
-- Note: MySQL doesn't support DESC in index definition, but it will still optimize the query
CREATE INDEX idx_user_create ON bg_sessiontracking(user_id, create_dt);

-- Add index for name column (used in login queries)
-- This helps with: WHERE name IN ('LOGIN-success_user', 'LOGIN-success_admin', ...)
CREATE INDEX idx_name ON bg_sessiontracking(name);

-- Add composite index for user + name queries
-- Optimal for: WHERE user_id = X AND name IN (...)
CREATE INDEX idx_user_name_create ON bg_sessiontracking(user_id, name, create_dt);

-- Show the indexes after creation
SHOW INDEX FROM bg_sessiontracking;

-- Test query performance (should be <10ms instead of 5700ms)
EXPLAIN SELECT * FROM bg_sessiontracking
WHERE user_id = 1
AND name IN ('LOGIN-success_user', 'LOGIN-success_admin', 'bg_rememberme_loginsuccess', 'login_success')
ORDER BY create_dt DESC
LIMIT 5;
