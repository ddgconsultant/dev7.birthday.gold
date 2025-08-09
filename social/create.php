<?php
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Site-controller handles authentication and provides $current_user_data
// If not logged in, $current_user_data will be empty
if (empty($current_user_data['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $current_user_data['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $visibility = $_POST['visibility'] ?? 'public';
    $media_type = $_POST['media_type'] ?? null;
    $media_data = [];
    
    if (!empty($content)) {
        // Extract hashtags
        preg_match_all('/#\w+/', $content, $hashtags);
        $hashtags = array_map(function($tag) { return ltrim($tag, '#'); }, $hashtags[0]);
        
        // Handle file upload if present
        if (!empty($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['media'];
            $allowed_types = [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm'
            ];
            
            if (in_array($file['type'], $allowed_types)) {
                $media_type = explode('/', $file['type'])[0];
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'social_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $extension;
                
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/social/' . date('Y/m/');
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $upload_path = $upload_dir . $filename;
                $web_path = '/uploads/social/' . date('Y/m/') . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $media_data[] = [
                        'type' => $file['type'],
                        'url' => $web_path,
                        'size' => $file['size']
                    ];
                }
            }
        }
        
        // Create the post
        $post_id = $social->createPost($user_id, $content, $media_type, $media_data, $visibility, null, $hashtags);
        
        if ($post_id) {
            header('Location: /social/?post=' . $post_id);
            exit;
        }
    }
}

// Get user's recent media for library
$sql = "SELECT p.post_id, p.media_type, p.media_urls, p.created_at 
        FROM bg_social_posts p 
        WHERE p.user_id = :user_id 
        AND p.media_type IS NOT NULL 
        AND p.status = 'active'
        ORDER BY p.created_at DESC 
        LIMIT 10";
$recent_media = $database->getrows($sql, ['user_id' => $user_id]);

// Get user's recent hashtags
$sql = "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(hashtags, '$[*]')) as hashtag
        FROM bg_social_posts
        WHERE user_id = :user_id 
        AND hashtags IS NOT NULL
        AND status = 'active'
        ORDER BY created_at DESC
        LIMIT 20";
$recent_hashtags = $database->getrows($sql, ['user_id' => $user_id]);

$pagetitle = 'Create Post - Birthday Gold';
$bodycontentclass = 'social-create-page';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($_SERVER['DOCUMENT_ROOT'] . '/social/components/header-nav.inc');
?>

<style>
.create-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.library-panel { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); height: fit-content; }
.create-panel { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-bottom: 20px; }
.media-thumb { width: 100%; height: 80px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.2s; }
.media-thumb:hover { transform: scale(1.05); }
.hashtag-chip { display: inline-block; padding: 4px 12px; background: #e3f2fd; color: #1976d2; border-radius: 20px; margin: 4px; cursor: pointer; font-size: 0.9em; }
.hashtag-chip:hover { background: #bbdefb; }
.post-textarea { width: 100%; min-height: 150px; padding: 15px; border: 1px solid #ddd; border-radius: 10px; resize: vertical; font-size: 1.1em; }
.post-textarea:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0,123,255,0.1); }
.media-preview { position: relative; margin: 20px 0; }
.media-preview img, .media-preview video { max-width: 100%; border-radius: 10px; }
.remove-media { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; }
.visibility-options { display: flex; gap: 15px; margin: 20px 0; }
.visibility-option { flex: 1; padding: 15px; border: 2px solid #ddd; border-radius: 10px; cursor: pointer; text-align: center; transition: all 0.3s; }
.visibility-option:hover { border-color: #007bff; background: #f8f9fa; }
.visibility-option.selected { border-color: #007bff; background: #e3f2fd; }
.char-count { text-align: right; color: #666; font-size: 0.9em; margin-top: 5px; }
.char-count.warning { color: #ff9800; }
.char-count.danger { color: #f44336; }
</style>

<div class="create-container">
    <h1 class="mb-4">Create a Post</h1>
    
    <div class="row">
        <!-- Left Panel: User Library -->
        <div class="col-lg-4 mb-4">
            <div class="library-panel">
                <h5 class="mb-3">Your Library</h5>
                
                <?php if (!empty($recent_media)): ?>
                <!-- Recent Photos -->
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Recent Media</h6>
                    <div class="media-grid">
                        <?php foreach ($recent_media as $media): ?>
                            <?php 
                            $media_items = json_decode($media['media_urls'], true);
                            if ($media_items && is_array($media_items)):
                                foreach ($media_items as $item):
                                    if ($media['media_type'] === 'image'):
                            ?>
                                <img src="<?php echo htmlspecialchars($item['url']); ?>" 
                                     class="media-thumb" 
                                     onclick="useMedia('<?php echo htmlspecialchars($item['url']); ?>', 'image')"
                                     alt="Media">
                            <?php elseif ($media['media_type'] === 'video'): ?>
                                <video class="media-thumb" onclick="useMedia('<?php echo htmlspecialchars($item['url']); ?>', 'video')">
                                    <source src="<?php echo htmlspecialchars($item['url']); ?>" type="<?php echo htmlspecialchars($item['type']); ?>">
                                </video>
                            <?php 
                                    endif;
                                endforeach;
                            endif;
                            ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Recent Hashtags -->
                <div>
                    <h6 class="text-muted mb-2">Your Hashtags</h6>
                    <div class="hashtags-container">
                        <?php if (!empty($recent_hashtags)): ?>
                            <?php foreach ($recent_hashtags as $tag): ?>
                                <?php if (!empty($tag['hashtag'])): ?>
                                <span class="hashtag-chip" onclick="addHashtag('<?php echo htmlspecialchars($tag['hashtag']); ?>')">
                                    #<?php echo htmlspecialchars($tag['hashtag']); ?>
                                </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="hashtag-chip" onclick="addHashtag('birthday')">#birthday</span>
                            <span class="hashtag-chip" onclick="addHashtag('celebration')">#celebration</span>
                            <span class="hashtag-chip" onclick="addHashtag('birthdaygold')">#birthdaygold</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Panel: Create Post Form -->
        <div class="col-lg-8">
            <div class="create-panel">
                <form id="createPostForm" method="POST" enctype="multipart/form-data">
                    <!-- Post Content -->
                    <div class="mb-3">
                        <textarea name="content" 
                                  class="post-textarea" 
                                  placeholder="Share your birthday experience, tips, or celebration..."
                                  maxlength="1000"
                                  required></textarea>
                        <div class="char-count">
                            <span id="charCount">0</span> / 1000
                        </div>
                    </div>
                    
                    <!-- Media Preview -->
                    <div id="mediaPreview" class="media-preview" style="display: none;">
                        <button type="button" class="remove-media" onclick="removeMedia()">
                            <i class="bi bi-x"></i>
                        </button>
                        <div id="mediaContent"></div>
                    </div>
                    
                    <!-- Media Upload -->
                    <div class="mb-4">
                        <label class="btn btn-outline-secondary">
                            <i class="bi bi-image"></i> Add Photo/Video
                            <input type="file" 
                                   name="media" 
                                   id="mediaInput"
                                   accept="image/*,video/*" 
                                   style="display: none;"
                                   onchange="previewMedia(this)">
                        </label>
                    </div>
                    
                    <!-- Visibility Options -->
                    <div class="mb-4">
                        <h6 class="mb-3">Who can see this?</h6>
                        <div class="visibility-options">
                            <div class="visibility-option selected" data-value="public">
                                <i class="bi bi-globe fs-4 d-block mb-2"></i>
                                <strong>Public</strong>
                                <small class="d-block text-muted">Anyone can see</small>
                            </div>
                            <div class="visibility-option" data-value="friends">
                                <i class="bi bi-people fs-4 d-block mb-2"></i>
                                <strong>Friends</strong>
                                <small class="d-block text-muted">Only followers</small>
                            </div>
                            <div class="visibility-option" data-value="private">
                                <i class="bi bi-lock fs-4 d-block mb-2"></i>
                                <strong>Private</strong>
                                <small class="d-block text-muted">Only you</small>
                            </div>
                        </div>
                        <input type="hidden" name="visibility" value="public">
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="/social/" class="btn btn-outline-secondary">Cancel</a>
                        <div>
                            <button type="submit" name="draft" class="btn btn-outline-primary me-2">Save as Draft</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Post
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter
const textarea = document.querySelector('textarea[name="content"]');
const charCount = document.getElementById('charCount');

textarea.addEventListener('input', function() {
    const length = this.value.length;
    charCount.textContent = length;
    
    const countDiv = charCount.parentElement;
    countDiv.classList.remove('warning', 'danger');
    
    if (length > 900) {
        countDiv.classList.add('danger');
    } else if (length > 800) {
        countDiv.classList.add('warning');
    }
});

// Visibility selection
document.querySelectorAll('.visibility-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.visibility-option').forEach(opt => opt.classList.remove('selected'));
        this.classList.add('selected');
        document.querySelector('input[name="visibility"]').value = this.dataset.value;
    });
});

// Media preview
function previewMedia(input) {
    const file = input.files[0];
    if (!file) return;
    
    const preview = document.getElementById('mediaPreview');
    const content = document.getElementById('mediaContent');
    
    const reader = new FileReader();
    reader.onload = function(e) {
        content.innerHTML = '';
        
        if (file.type.startsWith('image/')) {
            content.innerHTML = `<img src="${e.target.result}" style="max-width: 100%;">`;
        } else if (file.type.startsWith('video/')) {
            content.innerHTML = `<video controls style="max-width: 100%;"><source src="${e.target.result}" type="${file.type}"></video>`;
        }
        
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function removeMedia() {
    document.getElementById('mediaInput').value = '';
    document.getElementById('mediaPreview').style.display = 'none';
    document.getElementById('mediaContent').innerHTML = '';
}

// Add hashtag to post
function addHashtag(tag) {
    const textarea = document.querySelector('textarea[name="content"]');
    const hashtag = '#' + tag + ' ';
    
    if (!textarea.value.includes('#' + tag)) {
        textarea.value += (textarea.value ? ' ' : '') + hashtag;
        textarea.focus();
        
        // Update character count
        const event = new Event('input');
        textarea.dispatchEvent(event);
    }
}

// Use media from library
function useMedia(url, type) {
    alert('This feature will allow you to reuse this media in your post. Coming soon!');
}

// Auto-resize textarea
textarea.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
?>