-- ==================================================================================
-- PERFORMANCE FIX: Add indexes to mailserver.messages table
-- ==================================================================================
-- The mailcount() query was taking 517ms due to lack of indexes
-- Query: SELECT COUNT(*) FROM messages WHERE user_id = X AND processstatus NOT IN ('delete','expired')
--
-- Run this on ALL THREE mail servers:
-- mysql -h "march01.bday.gold" -u root -p mailserver < add_mailserver_indexes.sql
-- mysql -h "march02.bday.gold" -u root -p mailserver < add_mailserver_indexes.sql
-- mysql -h "march03.bday.gold" -u root -p mailserver < add_mailserver_indexes.sql
-- ==================================================================================

-- Add index for user_id (most selective column)
CREATE INDEX idx_user_id ON messages(user_id);

-- Add composite index for the exact query pattern
-- user_id + processstatus will make the COUNT query very fast
CREATE INDEX idx_user_processstatus ON messages(user_id, processstatus);

-- Optional: Add index for company_id if used for filtering
CREATE INDEX idx_company_id ON messages(company_id);

-- Show the indexes after creation
SHOW INDEX FROM messages;

-- Test the query performance (should be <10ms instead of 250ms+ per server)
EXPLAIN SELECT COUNT(*) as cnt FROM messages
WHERE user_id = 1
AND processstatus NOT IN ('delete' ,'expired');
