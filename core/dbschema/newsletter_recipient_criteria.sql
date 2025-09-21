-- Add recipient_criteria column to newsletter campaigns table
ALTER TABLE bg_newsletter_campaigns 
ADD COLUMN recipient_criteria JSON NULL AFTER cta_category,
ADD INDEX idx_recipient_criteria ((CAST(recipient_criteria AS CHAR(100))));