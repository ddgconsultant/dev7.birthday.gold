<!-- Share Options Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="shareModalLabel">Share Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Share to Social Platforms -->
                <div class="share-platforms mb-4">
                    <div class="row g-3">
                        <!-- Copy Link -->
                        <div class="col-3 text-center">
                            <button class="share-option-btn" onclick="copyPostLink()" data-bs-toggle="tooltip" title="Copy Link">
                                <div class="share-icon-wrapper">
                                    <i class="bi bi-link-45deg"></i>
                                </div>
                                <small class="d-block mt-1">Copy Link</small>
                            </button>
                        </div>
                        
                        <!-- Facebook -->
                        <div class="col-3 text-center">
                            <button class="share-option-btn" onclick="shareToFacebook()" data-bs-toggle="tooltip" title="Share to Facebook">
                                <div class="share-icon-wrapper facebook">
                                    <i class="bi bi-facebook"></i>
                                </div>
                                <small class="d-block mt-1">Facebook</small>
                            </button>
                        </div>
                        
                        <!-- Twitter/X -->
                        <div class="col-3 text-center">
                            <button class="share-option-btn" onclick="shareToTwitter()" data-bs-toggle="tooltip" title="Share to X">
                                <div class="share-icon-wrapper twitter">
                                    <i class="bi bi-twitter-x"></i>
                                </div>
                                <small class="d-block mt-1">X</small>
                            </button>
                        </div>
                        
                        <!-- WhatsApp -->
                        <div class="col-3 text-center">
                            <button class="share-option-btn" onclick="shareToWhatsApp()" data-bs-toggle="tooltip" title="Share to WhatsApp">
                                <div class="share-icon-wrapper whatsapp">
                                    <i class="bi bi-whatsapp"></i>
                                </div>
                                <small class="d-block mt-1">WhatsApp</small>
                            </button>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-3 text-center">
                            <button class="share-option-btn" onclick="shareViaEmail()" data-bs-toggle="tooltip" title="Share via Email">
                                <div class="share-icon-wrapper email">
                                    <i class="bi bi-envelope"></i>
                                </div>
                                <small class="d-block mt-1">Email</small>
                            </button>
                        </div>
                        
                        <!-- SMS -->
                        <div class="col-3 text-center d-block d-md-none">
                            <button class="share-option-btn" onclick="shareViaSMS()" data-bs-toggle="tooltip" title="Share via SMS">
                                <div class="share-icon-wrapper sms">
                                    <i class="bi bi-chat-text"></i>
                                </div>
                                <small class="d-block mt-1">SMS</small>
                            </button>
                        </div>
                        
                        <!-- Pinterest -->
                        <div class="col-3 text-center">
                            <button class="share-option-btn" onclick="shareToPinterest()" data-bs-toggle="tooltip" title="Share to Pinterest">
                                <div class="share-icon-wrapper pinterest">
                                    <i class="bi bi-pinterest"></i>
                                </div>
                                <small class="d-block mt-1">Pinterest</small>
                            </button>
                        </div>
                        
                        <!-- Reddit -->
                        <div class="col-3 text-center">
                            <button class="share-option-btn" onclick="shareToReddit()" data-bs-toggle="tooltip" title="Share to Reddit">
                                <div class="share-icon-wrapper reddit">
                                    <i class="bi bi-reddit"></i>
                                </div>
                                <small class="d-block mt-1">Reddit</small>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Share within Birthday Gold -->
                <div class="internal-share-section">
                    <h6 class="text-muted mb-3">Share within Birthday Gold</h6>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="shareToMyProfile()">
                            <i class="bi bi-person-circle me-2"></i>Share to My Profile
                        </button>
                        <button class="btn btn-outline-primary" onclick="sendDirectMessage()">
                            <i class="bi bi-send me-2"></i>Send as Direct Message
                        </button>
                    </div>
                </div>
                
                <!-- Copy Link Section -->
                <div class="copy-link-section mt-4">
                    <div class="input-group">
                        <input type="text" class="form-control" id="postLinkInput" readonly value="">
                        <button class="btn btn-primary" type="button" onclick="copyPostLink()">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <div class="copy-success-message text-success small mt-2" style="display: none;">
                        <i class="bi bi-check-circle"></i> Link copied to clipboard!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Share Modal Styles */
.share-option-btn {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    transition: transform 0.2s;
}

.share-option-btn:hover {
    transform: scale(1.1);
}

.share-icon-wrapper {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    background: #f8f9fa;
    color: #495057;
    transition: all 0.2s;
    margin: 0 auto;
}

.share-icon-wrapper:hover {
    background: #e9ecef;
}

/* Platform-specific colors */
.share-icon-wrapper.facebook {
    background: #1877f2;
    color: white;
}

.share-icon-wrapper.twitter {
    background: #000;
    color: white;
}

.share-icon-wrapper.whatsapp {
    background: #25d366;
    color: white;
}

.share-icon-wrapper.email {
    background: #ea4335;
    color: white;
}

.share-icon-wrapper.sms {
    background: #34b7f1;
    color: white;
}

.share-icon-wrapper.pinterest {
    background: #bd081c;
    color: white;
}

.share-icon-wrapper.reddit {
    background: #ff4500;
    color: white;
}

.share-option-btn small {
    font-size: 0.75rem;
    color: #495057;
}

.internal-share-section {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
    margin-top: 1rem;
}

.copy-link-section {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
}

#postLinkInput {
    font-size: 0.9rem;
    background: #f8f9fa;
}

.copy-success-message {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Mobile adjustments */
@media (max-width: 575.98px) {
    .share-icon-wrapper {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
    
    .share-option-btn small {
        font-size: 0.7rem;
    }
}
</style>

<script>
// Get current post data (this should be populated from PHP)
const currentPostId = '<?php echo $postId ?? "1"; ?>';
const currentPostUrl = window.location.origin + '/social/?post=' + currentPostId;
const postTitle = 'Check out this amazing birthday deal!';
const postDescription = 'Found an awesome birthday freebie on Birthday Gold!';

// Initialize the share link input
document.addEventListener('DOMContentLoaded', function() {
    const linkInput = document.getElementById('postLinkInput');
    if (linkInput) {
        linkInput.value = currentPostUrl;
    }
});

// Copy link to clipboard
function copyPostLink() {
    const linkInput = document.getElementById('postLinkInput');
    const successMessage = document.querySelector('.copy-success-message');
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(currentPostUrl).then(() => {
            showCopySuccess();
        });
    } else {
        // Fallback for older browsers
        linkInput.select();
        document.execCommand('copy');
        showCopySuccess();
    }
    
    function showCopySuccess() {
        successMessage.style.display = 'block';
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 3000);
    }
}

// Share to Facebook
function shareToFacebook() {
    const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentPostUrl)}`;
    window.open(url, '_blank', 'width=600,height=400');
}

// Share to Twitter/X
function shareToTwitter() {
    const text = encodeURIComponent(postTitle);
    const url = `https://twitter.com/intent/tweet?text=${text}&url=${encodeURIComponent(currentPostUrl)}`;
    window.open(url, '_blank', 'width=600,height=400');
}

// Share to WhatsApp
function shareToWhatsApp() {
    const text = encodeURIComponent(`${postTitle} ${currentPostUrl}`);
    const url = `https://wa.me/?text=${text}`;
    window.open(url, '_blank');
}

// Share via Email
function shareViaEmail() {
    const subject = encodeURIComponent(postTitle);
    const body = encodeURIComponent(`${postDescription}\n\n${currentPostUrl}`);
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}

// Share via SMS (mobile only)
function shareViaSMS() {
    const text = encodeURIComponent(`${postTitle} ${currentPostUrl}`);
    window.location.href = `sms:?body=${text}`;
}

// Share to Pinterest
function shareToPinterest() {
    const media = ''; // Add image URL if available
    const description = encodeURIComponent(postDescription);
    const url = `https://pinterest.com/pin/create/button/?url=${encodeURIComponent(currentPostUrl)}&description=${description}`;
    window.open(url, '_blank', 'width=600,height=400');
}

// Share to Reddit
function shareToReddit() {
    const title = encodeURIComponent(postTitle);
    const url = `https://reddit.com/submit?url=${encodeURIComponent(currentPostUrl)}&title=${title}`;
    window.open(url, '_blank', 'width=600,height=400');
}

// Internal Birthday Gold sharing
function shareToMyProfile() {
    // This would post to user's Birthday Gold profile
    alert('This feature will share the post to your Birthday Gold profile (coming soon!)');
    // In production, this would make an AJAX call to create a share post
}

function sendDirectMessage() {
    // This would open a DM selector
    alert('This feature will let you send the post as a direct message (coming soon!)');
    // In production, this would open a user selector modal
}

// Initialize Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>