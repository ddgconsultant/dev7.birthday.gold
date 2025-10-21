-- Add enrollment-related FAQs to bg_content
-- These link to the help pages for enrollment failures

INSERT INTO bg_content (name, category, type, grouping, display_name, description, content, rank, status, create_dt, modify_dt) VALUES

-- Account Already Exists FAQ
('enrollment_account_exists', 'enrollments', 'faq', 'Enrollment Problems',
 'What does it mean when enrollment fails because "Account Already Exists"?',
 '',
 '<p>This means you already have an existing account with this business''s rewards program. Your email address or phone number was already registered with them.</p>
<p><strong>Good news!</strong> This is actually not a problem. Since you already have an account with this business, you''re already enrolled in their rewards program. You don''t need us to sign you up again.</p>
<p><a href="/help_enrollment_failed-account-exists" class="btn btn-primary">Learn more about this issue →</a></p>',
 10, 'active', NOW(), NOW()),

-- Password Validation Failed FAQ
('enrollment_password_validation', 'enrollments', 'faq', 'Enrollment Problems',
 'Why did my enrollment fail due to password validation?',
 '',
 '<p>Your current Birthday Gold password doesn''t meet the business''s security requirements. Different businesses require specific password complexity (uppercase, lowercase, numbers, special characters, minimum length).</p>
<p><strong>How to fix:</strong> Update your Birthday Gold password to include all required elements, then contact support to retry the enrollment.</p>
<p><a href="/help_enrollment_failed-password-validation" class="btn btn-primary">Learn how to fix this →</a></p>',
 20, 'active', NOW(), NOW()),

-- Missing Required Data FAQ
('enrollment_missing_data', 'enrollments', 'faq', 'Enrollment Problems',
 'What should I do if enrollment fails due to missing required data?',
 '',
 '<p>The business''s signup form requires information that isn''t currently in your Birthday Gold profile. This could be your phone number, complete mailing address, or other contact information.</p>
<p><strong>Solution:</strong> Complete your Birthday Gold profile with all required information, then contact support to retry the enrollment.</p>
<p><a href="/help_enrollment_failed-missing-data" class="btn btn-primary">Complete your profile →</a></p>',
 30, 'active', NOW(), NOW()),

-- Form Failure FAQ
('enrollment_form_failure', 'enrollments', 'faq', 'Enrollment Problems',
 'Why did my enrollment fail with a "Form Failure" error?',
 '',
 '<p>We encountered a technical problem while trying to submit your enrollment to the business''s website. This is typically a temporary issue with their signup form or website.</p>
<p><strong>What we''re doing:</strong> Our technical team investigates form failures and updates our enrollment methods. We''ll automatically retry when the issue is resolved.</p>
<p><a href="/help_enrollment_failed-form-failure" class="btn btn-primary">Learn more →</a></p>',
 40, 'active', NOW(), NOW()),

-- General Enrollment Problems FAQ
('enrollment_problems_overview', 'enrollments', 'faq', 'Enrollment Problems',
 'What are common enrollment problems and how do I fix them?',
 '',
 '<p>Enrollment failures can happen for several reasons including existing accounts, password requirements, missing profile information, or technical issues.</p>
<p><strong>Common issues:</strong></p>
<ul>
<li>Account already exists with the business</li>
<li>Password doesn''t meet security requirements</li>
<li>Missing required profile information</li>
<li>Technical/form submission problems</li>
</ul>
<p><a href="/help_enrollment_failed-general" class="btn btn-primary">View all enrollment help topics →</a></p>',
 5, 'active', NOW(), NOW()),

-- How Enrollment Summaries Work FAQ
('enrollment_summary_notifications', 'enrollments', 'faq', 'Enrollment Process',
 'How do enrollment summary notifications work?',
 '',
 '<p>You''ll receive periodic email updates about your enrollment progress. These summaries include:</p>
<ul>
<li>✅ <strong>Successful enrollments</strong> - Companies you''re now enrolled with</li>
<li>❌ <strong>Unsuccessful enrollments</strong> - Issues that need attention, with links to help pages</li>
<li>⏳ <strong>Still processing</strong> - Enrollments we''re still working on</li>
</ul>
<p>These updates are sent as enrollments are completed, grouped into convenient summaries rather than individual emails for each enrollment.</p>',
 50, 'active', NOW(), NOW());

-- Verify the inserts
SELECT id, category, display_name, status FROM bg_content
WHERE category = 'enrollments' AND type = 'faq'
ORDER BY rank;
