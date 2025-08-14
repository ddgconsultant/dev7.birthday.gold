<?php
// Populate legal policies with proper JSON tags for testing
include(dirname(__FILE__) . '/../core/site-controller.php');

// Sample legal policies with different review periods
$sample_policies = [
    [
        'name' => 'privacy_policy',
        'category' => 'Policies',
        'type' => 'legal',
        'grouping' => 'compliance',
        'display_name' => 'Privacy Policy',
        'description' => 'Birthday Gold Privacy Policy - User data protection and handling',
        'content' => '<h1>Privacy Policy</h1>
<p><strong>Last Updated: ' . date('F j, Y') . '</strong></p>
<h2>1. Information We Collect</h2>
<p>We collect information you provide directly to us, such as when you create an account, enroll in birthday programs, or contact us for support.</p>
<h3>Personal Information</h3>
<ul>
<li>Name and email address</li>
<li>Birth date (month and day only)</li>
<li>Optional: phone number, mailing address</li>
</ul>
<h2>2. How We Use Your Information</h2>
<p>We use the information we collect to:</p>
<ul>
<li>Automate birthday reward enrollments</li>
<li>Send you birthday reward notifications</li>
<li>Provide customer support</li>
<li>Improve our services</li>
</ul>
<h2>3. Information Sharing</h2>
<p>We do not sell, trade, or rent your personal information to third parties.</p>
<h2>4. Data Security</h2>
<p>We implement appropriate technical and organizational measures to protect your personal information.</p>
<h2>5. Contact Us</h2>
<p>If you have questions about this Privacy Policy, please contact us at privacy@birthday.gold</p>',
        'tags' => json_encode([
            'review_period' => 90,  // Review every 90 days
            'compliance' => 'GDPR, CCPA',
            'last_legal_review' => date('Y-m-d'),
            'owner' => 'Legal Team',
            'priority' => 'high'
        ]),
        'version' => '2.0',
        'status' => 'active'
    ],
    [
        'name' => 'terms_of_service',
        'category' => 'Legal',
        'type' => 'legal',
        'grouping' => 'compliance',
        'display_name' => 'Terms of Service',
        'description' => 'Birthday Gold Terms of Service - User agreement and platform rules',
        'content' => '<h1>Terms of Service</h1>
<p><strong>Effective Date: ' . date('F j, Y') . '</strong></p>
<h2>1. Acceptance of Terms</h2>
<p>By accessing and using Birthday Gold, you agree to be bound by these Terms of Service.</p>
<h2>2. Service Description</h2>
<p>Birthday Gold provides automated enrollment services for birthday reward programs offered by various businesses.</p>
<h2>3. User Accounts</h2>
<p>You are responsible for maintaining the confidentiality of your account credentials and for all activities under your account.</p>
<h2>4. Acceptable Use</h2>
<p>You agree not to:</p>
<ul>
<li>Use the service for any illegal purposes</li>
<li>Create multiple accounts for the same person</li>
<li>Provide false information</li>
<li>Attempt to circumvent security measures</li>
</ul>
<h2>5. Limitation of Liability</h2>
<p>Birthday Gold is not responsible for the fulfillment of birthday rewards by participating businesses.</p>
<h2>6. Modifications</h2>
<p>We reserve the right to modify these terms at any time with notice to users.</p>',
        'tags' => json_encode([
            'review_period' => 180,  // Review every 6 months
            'last_legal_review' => date('Y-m-d'),
            'owner' => 'Legal Team',
            'requires_user_acceptance' => true,
            'priority' => 'critical'
        ]),
        'version' => '3.1',
        'status' => 'active'
    ],
    [
        'name' => 'cookie_policy',
        'category' => 'Policies',
        'type' => 'legal',
        'grouping' => 'compliance',
        'display_name' => 'Cookie Policy',
        'description' => 'Information about cookies and tracking technologies used on Birthday Gold',
        'content' => '<h1>Cookie Policy</h1>
<p><strong>Last Updated: ' . date('F j, Y') . '</strong></p>
<h2>What Are Cookies</h2>
<p>Cookies are small text files stored on your device when you visit our website.</p>
<h2>How We Use Cookies</h2>
<p>We use cookies to:</p>
<ul>
<li>Keep you signed in</li>
<li>Remember your preferences</li>
<li>Analyze site usage</li>
<li>Improve performance</li>
</ul>
<h2>Types of Cookies We Use</h2>
<h3>Essential Cookies</h3>
<p>Required for the website to function properly.</p>
<h3>Analytics Cookies</h3>
<p>Help us understand how visitors use our site.</p>
<h2>Managing Cookies</h2>
<p>You can control cookies through your browser settings.</p>',
        'tags' => json_encode([
            'review_period' => 365,  // Review annually
            'compliance' => 'GDPR, ePrivacy',
            'last_legal_review' => date('Y-m-d'),
            'owner' => 'Legal Team',
            'priority' => 'medium'
        ]),
        'version' => '1.2',
        'status' => 'active'
    ],
    [
        'name' => 'dmca_policy',
        'category' => 'Legal',
        'type' => 'legal',
        'grouping' => 'compliance',
        'display_name' => 'DMCA Policy',
        'description' => 'Digital Millennium Copyright Act compliance and takedown procedures',
        'content' => '<h1>DMCA Policy</h1>
<p><strong>Effective Date: ' . date('F j, Y') . '</strong></p>
<h2>Copyright Infringement Notification</h2>
<p>Birthday Gold respects the intellectual property rights of others.</p>
<h2>Filing a DMCA Notice</h2>
<p>To file a notice of infringement, you must provide:</p>
<ul>
<li>Identification of the copyrighted work</li>
<li>Identification of the infringing material</li>
<li>Your contact information</li>
<li>A statement of good faith belief</li>
<li>A statement of accuracy under penalty of perjury</li>
<li>Your physical or electronic signature</li>
</ul>
<h2>Counter-Notice</h2>
<p>If you believe content was removed in error, you may file a counter-notice.</p>
<h2>Contact</h2>
<p>DMCA notices should be sent to: dmca@birthday.gold</p>',
        'tags' => json_encode([
            'review_period' => 365,  // Review annually
            'last_legal_review' => date('Y-m-d'),
            'owner' => 'Legal Team',
            'compliance' => 'DMCA',
            'priority' => 'low'
        ]),
        'version' => '1.0',
        'status' => 'active'
    ],
    [
        'name' => 'gdpr_compliance',
        'category' => 'Legal',
        'type' => 'legal',
        'grouping' => 'compliance',
        'display_name' => 'GDPR Compliance Statement',
        'description' => 'General Data Protection Regulation compliance for EU users',
        'content' => '<h1>GDPR Compliance Statement</h1>
<p><strong>Last Updated: ' . date('F j, Y') . '</strong></p>
<h2>Your Rights Under GDPR</h2>
<p>If you are in the European Economic Area, you have the following rights:</p>
<ul>
<li><strong>Right to Access</strong> - Request copies of your personal data</li>
<li><strong>Right to Rectification</strong> - Request correction of inaccurate data</li>
<li><strong>Right to Erasure</strong> - Request deletion of your data</li>
<li><strong>Right to Restrict Processing</strong> - Request limited use of your data</li>
<li><strong>Right to Data Portability</strong> - Receive your data in a portable format</li>
<li><strong>Right to Object</strong> - Object to certain uses of your data</li>
</ul>
<h2>Legal Basis for Processing</h2>
<p>We process personal data based on:</p>
<ul>
<li>Consent - When you sign up for our services</li>
<li>Contract - To provide our services to you</li>
<li>Legitimate interests - To improve our services</li>
</ul>
<h2>Data Protection Officer</h2>
<p>Contact our DPO at: dpo@birthday.gold</p>',
        'tags' => json_encode([
            'review_period' => 180,  // Review every 6 months (GDPR requires regular reviews)
            'compliance' => 'GDPR',
            'last_legal_review' => date('Y-m-d'),
            'owner' => 'Data Protection Officer',
            'jurisdiction' => 'EU',
            'priority' => 'critical'
        ]),
        'version' => '2.1',
        'status' => 'active'
    ],
    [
        'name' => 'refund_policy',
        'category' => 'Policies',
        'type' => 'legal',
        'grouping' => 'business',
        'display_name' => 'Refund Policy',
        'description' => 'Birthday Gold refund and cancellation policy',
        'content' => '<h1>Refund Policy</h1>
<p><strong>Effective Date: ' . date('F j, Y') . '</strong></p>
<h2>30-Day Money Back Guarantee</h2>
<p>We offer a 30-day money back guarantee on all paid subscriptions.</p>
<h2>Eligibility</h2>
<p>To be eligible for a refund:</p>
<ul>
<li>Request must be made within 30 days of purchase</li>
<li>Account must not have violated our Terms of Service</li>
</ul>
<h2>How to Request a Refund</h2>
<p>Email support@birthday.gold with your account information and reason for refund.</p>
<h2>Processing Time</h2>
<p>Refunds are typically processed within 5-7 business days.</p>',
        'tags' => json_encode([
            'review_period' => 90,  // Review quarterly
            'last_legal_review' => date('Y-m-d'),
            'owner' => 'Finance Team',
            'affects_revenue' => true,
            'priority' => 'high'
        ]),
        'version' => '1.5',
        'status' => 'active'
    ]
];

echo "Starting legal policy population...\n\n";

$success_count = 0;
$error_count = 0;

foreach ($sample_policies as $policy) {
    try {
        // Check if policy already exists
        $check_sql = "SELECT id FROM bg_content WHERE name = :name AND status = 'active'";
        $existing = $database->query($check_sql, ['name' => $policy['name']])->fetch();
        
        if ($existing) {
            echo "Policy '{$policy['display_name']}' already exists (ID: {$existing['id']}). Updating tags...\n";
            
            // Update existing policy with new tags
            $update_sql = "UPDATE bg_content SET 
                          tags = :tags,
                          modify_dt = NOW()
                          WHERE id = :id";
            
            $database->query($update_sql, [
                'tags' => $policy['tags'],
                'id' => $existing['id']
            ]);
            
            echo "  ✓ Updated tags with review_period\n";
            $success_count++;
        } else {
            // Insert new policy
            $insert_sql = "INSERT INTO bg_content 
                          (name, category, type, grouping, display_name, description, 
                           content, tags, version, status, create_dt, modify_dt, publish_dt)
                          VALUES 
                          (:name, :category, :type, :grouping, :display_name, :description,
                           :content, :tags, :version, :status, NOW(), NOW(), NOW())";
            
            $database->query($insert_sql, [
                'name' => $policy['name'],
                'category' => $policy['category'],
                'type' => $policy['type'],
                'grouping' => $policy['grouping'],
                'display_name' => $policy['display_name'],
                'description' => $policy['description'],
                'content' => $policy['content'],
                'tags' => $policy['tags'],
                'version' => $policy['version'],
                'status' => $policy['status']
            ]);
            
            $new_id = $database->lastInsertId();
            echo "✓ Created '{$policy['display_name']}' (ID: {$new_id})\n";
            
            // Parse and display the review period
            $tags = json_decode($policy['tags'], true);
            echo "  - Review period: {$tags['review_period']} days\n";
            echo "  - Priority: {$tags['priority']}\n";
            
            $success_count++;
        }
        
    } catch (Exception $e) {
        echo "✗ Error with '{$policy['display_name']}': " . $e->getMessage() . "\n";
        $error_count++;
    }
    
    echo "\n";
}

echo "===========================================\n";
echo "Population complete!\n";
echo "Success: $success_count policies\n";
echo "Errors: $error_count\n\n";

// Show summary of policies and their review status
echo "Current Policy Review Status:\n";
echo "-------------------------------------------\n";

$status_sql = "SELECT 
    id,
    display_name,
    category,
    tags,
    modify_dt,
    DATEDIFF(NOW(), modify_dt) as days_since_review
FROM bg_content 
WHERE category IN ('Policies', 'Legal', 'legal') 
AND status = 'active'
ORDER BY display_name";

$policies = $database->query($status_sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($policies as $policy) {
    $tags = json_decode($policy['tags'], true);
    $review_period = $tags['review_period'] ?? 180;
    $days_remaining = $review_period - $policy['days_since_review'];
    
    $status = '';
    if ($days_remaining < 0) {
        $status = '🔴 OVERDUE by ' . abs($days_remaining) . ' days';
    } elseif ($days_remaining <= 7) {
        $status = '🟡 Due in ' . $days_remaining . ' days';
    } else {
        $status = '🟢 OK (' . $days_remaining . ' days remaining)';
    }
    
    echo "• {$policy['display_name']} (ID: {$policy['id']})\n";
    echo "  Review Period: {$review_period} days | Status: $status\n";
}

echo "\n-------------------------------------------\n";
echo "Run the scheduler to test notifications:\n";
echo "curl 'https://dev7.birthday.gold/admin_actions/scheduler--legalhubreview_reminder.php?debug=1'\n";

?>