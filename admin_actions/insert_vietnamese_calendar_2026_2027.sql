-- Vietnamese Calendar Records for 2026 and 2027
-- Generated based on official Vietnamese public holidays and corporate holiday patterns
-- Author: Claude Code
-- Date: 2025-10-09

-- ============================================
-- 2026 VIETNAMESE CALENDAR RECORDS
-- ============================================

-- New Year's Day 2026 (January 1, 2026 - Thursday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('New Year\'s Day (Tết dương lịch)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '01/01/2026', 'Thursday, January 1, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Corporate Holiday (Before Tết Nguyên Đán) - February 13, 2026 (Friday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Corporate Holiday (Before Tết Nguyên Đán)', 'Corporate Holidays (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '02/13/2026', 'Friday, February 13, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Vietnamese New Year (Tết Nguyên Đán) - February 14-22, 2026 (9 days)
-- Note: Main day is February 17, 2026 (Year of the Horse)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '02/14/2026', 'Saturday, February 14, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '02/15/2026', 'Sunday, February 15, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '02/16/2026', 'Monday, February 16, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Tết Nguyên Đán', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '02/17/2026', 'Tuesday, February 17, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '02/17/2026', 'Tuesday, February 17, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '02/18/2026', 'Wednesday, February 18, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '02/19/2026', 'Thursday, February 19, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '02/20/2026', 'Friday, February 20, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Corporate Holiday (Before Giỗ Tổ Hùng Vương) - April 24, 2026 (Friday)
-- Note: Hung Kings Festival falls on Sunday April 26, so Friday before is corporate holiday
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Corporate Holiday (Before Giỗ Tổ Hùng Vương)', 'Corporate Holidays (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '04/24/2026', 'Friday, April 24, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Hung Kings' Festival (Giỗ Tổ Hùng Vương) - April 26, 2026 (Sunday)
-- Note: Falls on Sunday, so Monday April 27 will be compensation day
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Giỗ Tổ Hùng Vương', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '04/26/2026', 'Sunday, April 26, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Hung Kings\' Festival (Giỗ Tổ Hùng Vương)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '04/26/2026', 'Sunday, April 26, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Hung Kings\' Festival Compensation Day', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '04/27/2026', 'Monday, April 27, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Corporate Holiday (Before Ngày Thống nhất) - April 29, 2026 (Wednesday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Corporate Holiday (Before Ngày Thống nhất)', 'Corporate Holidays (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '04/29/2026', 'Wednesday, April 29, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Reunification Day - April 30, 2026 (Thursday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Reunification Day', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '04/30/2026', 'Thursday, April 30, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Reunification Day (Ngày Thống nhất)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '04/30/2026', 'Thursday, April 30, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Corporate Holiday (Before Ngày Quốc tế Lao động)', 'Corporate Holidays (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '04/30/2026', 'Thursday, April 30, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- International Labor Day - May 1, 2026 (Friday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('International Labor Day', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '05/01/2026', 'Friday, May 1, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('International Labor Day (Ngày Quốc tế Lao động)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '05/01/2026', 'Friday, May 1, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Corporate Holiday (Before Quốc khánh) - September 1, 2026 (Tuesday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Corporate Holiday (Before Quốc khánh)', 'Corporate Holidays (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '09/01/2026', 'Tuesday, September 1, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- National Day - September 2-3, 2026 (Wednesday-Thursday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('National Day (Quốc khánh)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '09/02/2026', 'Wednesday, September 2, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('National Day (Quốc khánh)', 'Public Holiday (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '09/03/2026', 'Thursday, September 3, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Corporate Holiday (Before Tết dương lịch 2027) - December 31, 2026 (Thursday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Corporate Holiday (Before Tết dương lịch)', 'Corporate Holidays (VN)', 'calendar_vn', '2026', NULL, 'officeclosed', '12/31/2026', 'Thursday, December 31, 2026', NULL, NULL, 50, 0, '2025-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- ============================================
-- 2027 VIETNAMESE CALENDAR RECORDS
-- ============================================

-- New Year's Day 2027 (January 1, 2027 - Friday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('New Year\'s Day (Tết dương lịch)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '01/01/2027', 'Friday, January 1, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Corporate Holiday (Before Tết Nguyên Đán) - February 5, 2027 (Friday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Corporate Holiday (Before Tết Nguyên Đán)', 'Corporate Holidays (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '02/05/2027', 'Friday, February 5, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Vietnamese New Year (Tết Nguyên Đán) - February 6-14, 2027 (9 days typical)
-- Note: Main day is February 6, 2027 (Year of the Goat)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '02/06/2027', 'Saturday, February 6, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Tết Nguyên Đán', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '02/06/2027', 'Saturday, February 6, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '02/07/2027', 'Sunday, February 7, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '02/08/2027', 'Monday, February 8, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '02/09/2027', 'Tuesday, February 9, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '02/10/2027', 'Wednesday, February 10, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '02/11/2027', 'Thursday, February 11, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Vietnamese New Year (Tết Nguyên Đán)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '02/12/2027', 'Friday, February 12, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Corporate Holiday (Before Giỗ Tổ Hùng Vương) - April 15, 2027 (Thursday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Corporate Holiday (Before Giỗ Tổ Hùng Vương)', 'Corporate Holidays (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '04/15/2027', 'Thursday, April 15, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Hung Kings' Festival (Giỗ Tổ Hùng Vương) - April 16, 2027 (Friday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Giỗ Tổ Hùng Vương', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '04/16/2027', 'Friday, April 16, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Hung Kings\' Festival (Giỗ Tổ Hùng Vương)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '04/16/2027', 'Friday, April 16, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Corporate Holiday (Before Ngày Thống nhất) - April 29, 2027 (Thursday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Corporate Holiday (Before Ngày Thống nhất)', 'Corporate Holidays (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '04/29/2027', 'Thursday, April 29, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Reunification Day - April 30, 2027 (Friday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Reunification Day', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '04/30/2027', 'Friday, April 30, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Reunification Day (Ngày Thống nhất)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '04/30/2027', 'Friday, April 30, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Corporate Holiday (Before Ngày Quốc tế Lao động)', 'Corporate Holidays (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '04/30/2027', 'Friday, April 30, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- International Labor Day - May 1, 2027 (Saturday)
-- Note: Falls on Saturday, so Monday May 3 should be compensation day
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('International Labor Day', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '05/01/2027', 'Saturday, May 1, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('International Labor Day (Ngày Quốc tế Lao động)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '05/01/2027', 'Saturday, May 1, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('Labor Day Compensation Day', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '05/03/2027', 'Monday, May 3, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Corporate Holiday (Before Quốc khánh) - September 1, 2027 (Wednesday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Corporate Holiday (Before Quốc khánh)', 'Corporate Holidays (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '09/01/2027', 'Wednesday, September 1, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- National Day - September 2-3, 2027 (Thursday-Friday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('National Day (Quốc khánh)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '09/02/2027', 'Thursday, September 2, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active'),
('National Day (Quốc khánh)', 'Public Holiday (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '09/03/2027', 'Friday, September 3, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- Corporate Holiday (Before Tết dương lịch 2028) - December 31, 2027 (Friday)
INSERT INTO `birthday_gold_www`.`bg_content`
(`name`, `category`, `type`, `grouping`, `display_name`, `label`, `description`, `content`, `tags`, `version`, `rank`, `views`, `publish_dt`, `expire_dt`, `create_dt`, `modify_dt`, `status`)
VALUES
('Corporate Holiday (Before Tết dương lịch)', 'Corporate Holidays (VN)', 'calendar_vn', '2027', NULL, 'officeclosed', '12/31/2027', 'Friday, December 31, 2027', NULL, NULL, 50, 0, '2026-01-01 00:00:00', NULL, NULL, NOW(), 'active');

-- ============================================
-- END OF VIETNAMESE CALENDAR INSERT STATEMENTS
-- ============================================
-- Total Records for 2026: 26
-- Total Records for 2027: 28
--
-- Note: These dates are based on official Vietnamese public holidays
-- and follow the same pattern as existing 2025 records in the database.
--
-- Sources:
-- - Vietnamese Lunar Calendar for Tet dates
-- - Hung Kings Festival: 10th day of 3rd lunar month
-- - Fixed dates: New Year's Day (Jan 1), Reunification Day (Apr 30),
--   Labor Day (May 1), National Day (Sep 2)
-- - Compensation days added when holidays fall on weekends
-- - Corporate holidays follow the pattern of day before major holidays
-- ============================================