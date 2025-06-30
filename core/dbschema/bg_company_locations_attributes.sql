CREATE TABLE `bg_company_locations_attributes` (
  `attribute_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint unsigned NOT NULL,
  `attribute_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `attribute_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `attribute_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'string' COMMENT 'string, json, number, boolean, datetime',
  `source` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'google_places, manual, import, etc',
  `create_dt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modify_dt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`attribute_id`) USING BTREE,
  KEY `location_id` (`location_id`) USING BTREE,
  KEY `attribute_key` (`attribute_key`) USING BTREE,
  KEY `location_key` (`location_id`,`attribute_key`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  CONSTRAINT `bg_company_locations_attributes_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `bg_company_locations` (`location_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Example attributes that could be stored:
-- google_place_id: The Google Places ID
-- google_place_types: JSON array of place types
-- google_rating: Numeric rating from Google
-- google_user_ratings_total: Number of Google reviews
-- google_price_level: Price level indicator (1-4)
-- google_website: Website URL from Google
-- google_formatted_phone: Formatted phone number
-- google_opening_hours: JSON object with hours
-- google_photos: JSON array of photo references
-- google_reviews: JSON array of reviews
-- business_hours_structured: JSON object with detailed hours
-- amenities: JSON array of amenities
-- payment_methods: JSON array of accepted payments
-- last_google_sync: Datetime of last API update