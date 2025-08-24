<?php
/**
 * Social Media Share Verification Page
 * Allows users to submit social media posts with #birthdaygold for verification
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get user data - authentication already handled by site-controller
$user_id = $current_user_data['user_id'];

// Check existing social share submissions in bg_user_allocations
$existing = $database->getrows(
    "SELECT * FROM bg_user_allocations 
     WHERE user_id = :user_id 
     AND allocation_type = 'social_share'
     ORDER BY created_at DESC 
     LIMIT 10",
    ['user_id' => $user_id]
);

// Check if user has verified post today
$today_verified = $database->getrow(
    "SELECT * FROM bg_user_allocations 
     WHERE user_id = :user_id 
     AND allocation_type = 'social_share'
     AND DATE(created_at) = CURDATE() 
     AND status = 'active'",
    ['user_id' => $user_id]
);

// Check pending submissions - use getrow and extract count
$pending_result = $database->getrow(
    "SELECT COUNT(*) as count FROM bg_user_allocations 
     WHERE user_id = :user_id 
     AND allocation_type = 'social_share'
     AND status = 'pending'",
    ['user_id' => $user_id]
);
$pending_count = $pending_result['count'] ?? 0;

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$today_verified) {
    $post_url = trim($_POST['post_url'] ?? '');
    
    // Validate URL
    if (!filter_var($post_url, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid URL";
    } else {
        // Check for duplicate URL in allocation_comment field
        $duplicate = $database->getrow(
            "SELECT * FROM bg_user_allocations 
             WHERE allocation_type = 'social_share' 
             AND allocation_comment LIKE :url",
            ['url' => '%' . $post_url . '%']
        );
        
        if ($duplicate) {
            if ($duplicate['user_id'] == $user_id) {
                $error = "You have already submitted this post";
            } else {
                $error = "This post has already been submitted by another user";
            }
        } else {
            // Detect platform
            $platform = 'unknown';
            if (preg_match('/(twitter\.com|x\.com)/i', $post_url)) {
                $platform = 'twitter';
            } elseif (preg_match('/facebook\.com/i', $post_url)) {
                $platform = 'facebook';
            } elseif (preg_match('/instagram\.com/i', $post_url)) {
                $platform = 'instagram';
            } elseif (preg_match('/tiktok\.com/i', $post_url)) {
                $platform = 'tiktok';
            }
            
            if ($platform === 'unknown') {
                $error = "Please provide a URL from Twitter/X, Facebook, Instagram, or TikTok";
            } else {
                // Insert into bg_user_allocations with status pending for verification
                try {
                    // Create JSON data for the allocation comment
                    $share_data = json_encode([
                        'platform' => $platform,
                        'url' => $post_url,
                        'hashtag_verified' => false,
                        'submitted' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Insert as pending allocation (will become active after verification)
                    $database->query(
                        "INSERT INTO bg_user_allocations 
                         (user_id, allocation_type, allocation_year, amount, amount_used, 
                          allocation_comment, reference_type, status, created_at) 
                         VALUES (:user_id, 'social_share', YEAR(NOW()), 0, 0, 
                                :comment, :platform, 'pending', NOW())",
                        [
                            'user_id' => $user_id,
                            'comment' => $share_data,
                            'platform' => $platform
                        ]
                    );
                    
                    $success = "Your post has been submitted for verification! We'll check for the #birthdaygold hashtag and award your allocation within a few minutes.";
                } catch (Exception $e) {
                    $error = "An error occurred while submitting your post. Please try again.";
                }
            }
        }
    }
}

// Page setup
$pagetitle = 'Share on Social Media';
$additionalstyles = '
<style>
.platform-icon {
    font-size: 1.5rem;
    margin-right: 0.5rem;
}

.submission-table td {
    vertical-align: middle;
}

.example-post {
    background: #f8f9fa;
    border-left: 3px solid #0d6efd;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 0.25rem;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
}

.example-post:hover {
    background: #e9ecef;
    transform: translateX(2px);
}

.example-post::after {
    content: "Click to copy";
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    font-size: 0.75rem;
    background: rgba(13, 110, 253, 0.9);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
}

.example-post:hover::after {
    opacity: 1;
}

.example-post.copied::after {
    content: "Copied!";
    background: rgba(40, 167, 69, 0.9);
    opacity: 1;
}

.status-badge {
    font-size: 0.875rem;
}

/* Enhanced Form Card Styling */
.submit-card {
    background: white;
    border: 2px solid #28a745;
    box-shadow: 0 10px 30px rgba(40, 167, 69, 0.15);
    position: relative;
    overflow: hidden;
}

.submit-card .card-title {
    color: #212529;
    font-weight: 600;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.submit-card .card-subtitle {
    color: #6c757d;
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
}

.submit-card .form-label {
    color: #495057;
    font-weight: 500;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.submit-card .form-control {
    border: 2px solid #dee2e6;
    padding: 0.75rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.submit-card .form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    outline: none;
}

.submit-card .form-text {
    color: #6c757d;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

.submit-card .earn-badge {
    display: inline-block;
    background: #28a745;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 1.2rem;
    box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
    animation: pulse 2s infinite;
    white-space: nowrap;
}

@media (max-width: 576px) {
    .submit-card .earn-badge {
        font-size: 1rem;
        padding: 0.4rem 0.7rem;
    }
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Make instruction cards more subtle */
.instruction-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    box-shadow: none;
}

.instruction-card .card-title {
    font-size: 1.1rem;
    color: #6c757d;
}

/* Platform icons in form */
.platform-badges {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
    flex-wrap: wrap;
}

.platform-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: #f8f9fa;
    color: #495057;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    border: 1px solid #dee2e6;
    font-size: 0.9rem;
}

.platform-badge i {
    font-size: 1rem;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Start output
echo '<div class="content-header-dark">';
echo '    <div class="container">';
echo '        <div class="row align-items-center">';
echo '            <div class="col-12 text-center">';
echo '                <h1 class="mb-3"><i class="bi bi-share me-3"></i>Share on Social Media</h1>';
echo '                <p class="lead mb-0">Post about Birthday Gold with #birthdaygold and earn allocations</p>';
echo '            </div>';
echo '        </div>';
echo '    </div>';
echo '</div>';

echo '<div class="container my-5">';
echo '    <div class="row">';

// Left Column: Form and Submission Table
echo '        <div class="col-lg-6">';

if ($today_verified) {
    echo '<div class="alert alert-success mb-4">
        <h5 class="alert-heading"><i class="bi bi-check-circle me-2"></i>Already Earned Today!</h5>
        <p class="mb-0">You have already earned your social media allocation for today. Come back tomorrow to share another post!</p>
    </div>';
} elseif ($pending_count > 0) {
    echo '<div class="alert alert-info mb-4">
        <h5 class="alert-heading"><i class="bi bi-clock-history me-2"></i>Verification in Progress</h5>
        <p class="mb-0">You have ' . $pending_count . ' post(s) being verified. Check back in a few minutes!</p>
    </div>';
}

if (!$today_verified) {
    echo '<div class="card submit-card mb-4">
        <div class="card-body p-5">
            <div class="row align-items-center mb-3">
                <div class="col-9 col-md-10">
                    <h3 class="card-title mb-2"><i class="bi bi-rocket-takeoff me-2"></i>Submit Your Post</h3>
                    <p class="card-subtitle mb-0">Share your Birthday Gold experience and earn an instant allocation!</p>
                </div>
                <div class="col-3 col-md-2 text-end">
                    <div class="earn-badge position-relative">+1 🎁</div>
                </div>
            </div>';
    
    if ($error) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
    }
    
    if ($success) {
        echo '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>';
    }
    
    echo '<form method="POST">
            <div class="mb-4 mt-4">
                <label for="post_url" class="form-label">Your Social Media Post URL</label>
                <input type="url" class="form-control form-control-lg" id="post_url" name="post_url" placeholder="https://twitter.com/username/status/123..." required>
                <div class="form-text">Paste the full URL to your social media post containing <strong>#birthdaygold</strong></div>
            </div>
            <button type="submit" class="btn btn-success btn-lg w-100 mt-5"><i class="bi bi-send-fill me-2"></i>Submit for Verification</button>
        </form>
    </div>
</div>';
}

if (!empty($existing)) {
    echo '<div class="card">
        <div class="card-body">
            <h3 class="card-title">Your Recent Submissions</h3>
            <div class="table-responsive">
                <table class="table submission-table">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Verified</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($existing as $submission) {
        // Parse JSON data from allocation_comment field
        $data = json_decode($submission['allocation_comment'], true);
        $platform = $submission['reference_type'] ?? $data['platform'] ?? 'unknown';
        
        $icon = 'link-45deg';
        switch($platform) {
            case 'twitter': $icon = 'twitter-x'; break;
            case 'facebook': $icon = 'facebook'; break;
            case 'instagram': $icon = 'instagram'; break;
            case 'tiktok': $icon = 'tiktok'; break;
        }
        
        echo '<tr>';
        echo '    <td><i class="bi bi-' . $icon . ' me-2"></i>' . ucfirst($platform) . '</td>';
        echo '    <td>';
        
        if ($submission['status'] == 'pending') {
            echo '<span class="badge bg-warning status-badge"><i class="bi bi-clock me-1"></i>Pending</span>';
        } elseif ($submission['status'] == 'active') {
            echo '<span class="badge bg-success status-badge"><i class="bi bi-check-circle me-1"></i>Verified</span>';
        } else {
            echo '<span class="badge bg-danger status-badge"><i class="bi bi-x-circle me-1"></i>Failed</span>';
        }
        
        echo '    </td>';
        echo '    <td>' . date('M j, g:i a', strtotime($submission['created_at'])) . '</td>';
        echo '    <td>';
        
        if ($submission['first_used_at'] && $submission['status'] == 'active') {
            echo date('M j, g:i a', strtotime($submission['first_used_at']));
        } elseif ($submission['status'] == 'failed') {
            echo 'Failed';
        } else {
            echo '-';
        }
        
        echo '    </td>';
        echo '</tr>';
    }
    
    echo '                </tbody>
            </table>
        </div>
    </div>
</div>';
}

echo '        </div>'; // End left column

// Right Column: Instructions and Examples
echo '        <div class="col-lg-6">';

echo '<div class="card instruction-card mb-4">';
echo '    <div class="card-body">';
echo '        <h3 class="card-title">How It Works</h3>';
echo '        <ol>';
echo '            <li>Create a post on your favorite social media platform</li>';
echo '            <li>Include <strong>#birthdaygold</strong> in your post</li>';
echo '            <li>Make sure your post is public (not private or friends-only)</li>';
echo '            <li>Submit the post URL for verification</li>';
echo '            <li>Earn 1 allocation once verified (limit: 1 per day)</li>';
echo '        </ol>';
echo '        <div class="mb-3">';
echo '            <strong>Supported Platforms:</strong>';
echo '            <div class="platform-badges mt-2">';
echo '                <div class="platform-badge"><i class="bi bi-twitter-x"></i><span>Twitter/X</span></div>';
echo '                <div class="platform-badge"><i class="bi bi-facebook"></i><span>Facebook</span></div>';
echo '                <div class="platform-badge"><i class="bi bi-instagram"></i><span>Instagram</span></div>';
echo '                <div class="platform-badge"><i class="bi bi-tiktok"></i><span>TikTok</span></div>';
echo '            </div>';
echo '        </div>';
echo '        <div class="alert alert-warning">';
echo '            <strong>Important:</strong> Your post must be publicly visible and contain the hashtag <strong>#birthdaygold</strong> to be verified.';
echo '        </div>';
echo '    </div>';
echo '</div>';

echo '<div class="card instruction-card">';
echo '    <div class="card-body">';
echo '        <h3 class="card-title">Example Posts</h3>';
echo '        <p class="text-muted">Click any example below to copy it to your clipboard, then customize with your own experience!</p>';

$examples = [
    ['platform' => 'twitter-x', 'name' => 'Twitter/X', 'text' => 'Just discovered @birthdaygold - getting free birthday rewards from my favorite brands all year long! 🎂🎉 #birthdaygold'],
    ['platform' => 'facebook', 'name' => 'Facebook', 'text' => 'Birthday month is even better with Birthday Gold! So many free rewards and special offers 🎁 Check it out at birthday.gold #birthdaygold'],
    ['platform' => 'instagram', 'name' => 'Instagram', 'text' => 'Birthday rewards all year? Yes please! 🎈 Just signed up for Birthday Gold and already got my first rewards! #birthdaygold #birthdayrewards'],
    ['platform' => 'tiktok', 'name' => 'TikTok', 'text' => 'POV: You found Birthday Gold and now get free stuff from restaurants and stores every month 🎂✨ #birthdaygold #birthdayfreebies #rewards']
];

foreach ($examples as $example) {
    echo '<div class="example-post" onclick="copyExamplePost(this, \'' . htmlspecialchars($example['text'], ENT_QUOTES) . '\')">';
    echo '    <div class="d-flex align-items-start">';
    echo '        <i class="bi bi-' . $example['platform'] . ' platform-icon"></i>';
    echo '        <div>';
    echo '            <strong>' . $example['name'] . '</strong>';
    echo '            <p class="mb-0 example-text">' . htmlspecialchars($example['text']) . '</p>';
    echo '        </div>';
    echo '    </div>';
    echo '</div>';
}

echo '    </div>';
echo '</div>';

echo '        </div>'; // End right column
echo '    </div>'; // End row
echo '</div>'; // End container

// JavaScript
echo '<script>
// Validate URL is from supported platform
function validatePlatformURL() {
    const urlInput = document.getElementById("post_url");
    const url = urlInput.value.toLowerCase();
    
    // Check if URL is from supported platforms
    const supportedPlatforms = [
        "twitter.com",
        "x.com",
        "facebook.com",
        "instagram.com",
        "tiktok.com"
    ];
    
    let isValid = false;
    for (const platform of supportedPlatforms) {
        if (url.includes(platform)) {
            isValid = true;
            break;
        }
    }
    
    if (!isValid && url.length > 0) {
        urlInput.setCustomValidity("Please provide a URL from Twitter/X, Facebook, Instagram, or TikTok");
    } else {
        urlInput.setCustomValidity("");
    }
    
    return isValid;
}

// Add event listeners when page loads
document.addEventListener("DOMContentLoaded", function() {
    const urlInput = document.getElementById("post_url");
    if (urlInput) {
        urlInput.addEventListener("input", validatePlatformURL);
        urlInput.addEventListener("blur", validatePlatformURL);
        
        // Also validate on form submit
        const form = urlInput.closest("form");
        if (form) {
            form.addEventListener("submit", function(e) {
                if (!validatePlatformURL()) {
                    e.preventDefault();
                    urlInput.reportValidity();
                }
            });
        }
    }
    
    // Set title for example posts
    const examples = document.querySelectorAll(".example-post");
    examples.forEach(function(example) {
        example.title = "Click to copy this example post";
    });
});

function copyExamplePost(element, text) {
    // Copy text to clipboard
    if (navigator.clipboard && navigator.clipboard.writeText) {
        // Modern approach
        navigator.clipboard.writeText(text).then(function() {
            showCopiedFeedback(element);
        }).catch(function(err) {
            // Fallback to older method
            fallbackCopyTextToClipboard(text, element);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyTextToClipboard(text, element);
    }
}

function fallbackCopyTextToClipboard(text, element) {
    // Create a temporary textarea element
    const textArea = document.createElement("textarea");
    textArea.value = text;
    
    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand("copy");
        if (successful) {
            showCopiedFeedback(element);
        }
    } catch (err) {
        console.error("Unable to copy:", err);
    }
    
    document.body.removeChild(textArea);
}

function showCopiedFeedback(element) {
    // Add copied class for visual feedback
    element.classList.add("copied");
    
    // Show success toast/alert (optional)
    const toast = document.createElement("div");
    toast.className = "position-fixed top-50 start-50 translate-middle";
    toast.style.zIndex = "9999";
    toast.innerHTML = \'<div class="alert alert-success d-flex align-items-center" role="alert" style="min-width: 250px;"><i class="bi bi-check-circle-fill me-2"></i><div>Copied to clipboard!</div></div>\';
    document.body.appendChild(toast);
    
    // Remove the copied class and toast after 2 seconds
    setTimeout(function() {
        element.classList.remove("copied");
        if (toast.parentNode) {
            toast.remove();
        }
    }, 2000);
}
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>