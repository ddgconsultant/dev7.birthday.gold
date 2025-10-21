-- Seed enrollment summary attributes for all users
-- Run this SQL query BEFORE activating the enrollment summary notification scheduler
-- This prevents mass notification spam by marking all users as having received a summary "now"

INSERT INTO bg_user_attributes
    (user_id, type, name, description, string_value, status, create_dt, modify_dt)
SELECT
    u.user_id,
    'enrollment-summary' as type,
    'last-sent-datetime' as name,
    'Last enrollment summary notification sent' as description,
    NOW() as string_value,
    'active' as status,
    NOW() as create_dt,
    NOW() as modify_dt
FROM bg_users u
LEFT JOIN bg_user_attributes ua ON u.user_id = ua.user_id
    AND ua.type = 'enrollment-summary'
    AND ua.name = 'last-sent-datetime'
    AND ua.status = 'active'
WHERE u.status = 'active'
    AND u.type = 'real'
    AND ua.attribute_id IS NULL;

-- Check how many records were inserted
SELECT COUNT(*) as records_inserted FROM bg_user_attributes
WHERE type = 'enrollment-summary'
AND name = 'last-sent-datetime'
AND create_dt > DATE_SUB(NOW(), INTERVAL 1 MINUTE);
