<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# HELPER FUNCTIONS
#-------------------------------------------------------------------------------
function getReadTime($tags) {
    if (preg_match('/(\d+)\s*min\s*read/i', $tags, $matches)) {
        return $matches[1];
    }
    return 5; // default
}

function updateViewCount($database, $post_id) {
    // Check if bg_content has a views column, if not we can track in a separate table or skip

        $view_sql = "UPDATE bg_content SET views = views + 1 WHERE id = ?";
        $view_stmt = $database->prepare($view_sql);
        $view_stmt->execute([$post_id]);
        return true;

}

#-------------------------------------------------------------------------------
# GET BLOG POST FROM URL
#-------------------------------------------------------------------------------
// Get slug from URL parameter (passed by .htaccess) or extract from URL path
$slug = '';

// First try to get from URL parameter (cleaner method)
if (isset($_GET['slug'])) {
    $slug = $_GET['slug'];
} else {
    // Fallback: Extract slug from URL path
    $request_uri = $_SERVER['REQUEST_URI'];
    $path_parts = explode('/', trim($request_uri, '/'));
    
    // Find the slug (should be after 'blog' in the URL)
    $blog_index = array_search('blog', $path_parts);
    if ($blog_index !== false && isset($path_parts[$blog_index + 1])) {
        $slug = $path_parts[$blog_index + 1];
    }
}

// If no slug found, redirect to blog index
if (empty($slug)) {
    header('Location: /blog');
    exit;
}

// Get blog post from database
$post_sql = "SELECT * FROM bg_content WHERE name = ? AND category = 'blog' AND type = 'post' AND status = 'active' ";
$post_stmt = $database->prepare($post_sql);
$post_stmt->execute([$slug]);
$post = $post_stmt->fetch(PDO::FETCH_ASSOC);

// If post not found, show integrated 404 page
if (!$post) {
    header("HTTP/1.0 404 Not Found");
    
    // Get recent posts for suggestions
    $recent_sql = "SELECT * FROM bg_content 
                   WHERE category='blog' AND type='post' AND status='active' 
                      and publish_dt <= NOW()
                  AND (expire_dt IS NULL OR expire_dt > NOW())
                   ORDER BY create_dt DESC LIMIT 6";
    $recent_result = $database->query($recent_sql);
    $recent_posts = $recent_result ? $recent_result->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Show integrated 404 page
    $bodycontentclass='';
    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');

    $additionalstyles .= '
    <style>
    .error-404 {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 3rem;
        text-align: center;
        margin: 2rem 0;
    }
    .error-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.8;
    }
    .suggestion-card {
        transition: transform 0.2s ease;
        border: none;
        border-radius: 10px;
    }
    .suggestion-card:hover {
        transform: translateY(-3px);
    }
    .search-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 2rem;
        margin: 2rem 0;
    }
    </style>
    ';

    echo '
    <div class="container main-content">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          
          <!-- Breadcrumb -->
          <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="/">Home</a></li>
              <li class="breadcrumb-item"><a href="/blog">Blog</a></li>
              <li class="breadcrumb-item active">Page Not Found</li>
            </ol>
          </nav>

          <!-- 404 Error Section -->
          <div class="error-404">
            <div class="error-icon">🎂</div>
            <h1 class="display-6 mb-3">Oops! This Birthday Guide Doesn\'t Exist</h1>
            <p class="lead mb-4">The blog post you\'re looking for might have been moved, deleted, or the URL might be incorrect.</p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
              <a href="/blog" class="btn btn-light btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Back to Blog
              </a>
              <a href="/" class="btn btn-outline-light btn-lg">
                <i class="fas fa-home me-2"></i>Home Page
              </a>
            </div>
          </div>

          <!-- Search Help -->
          <div class="search-box text-center">
            <h4 class="mb-3">🔍 Looking for Something Specific?</h4>
            <p class="text-muted mb-4">Try searching our birthday deals and guides, or browse our popular categories below.</p>
            <div class="row text-center">
              <div class="col-md-3 mb-2">
                <a href="/blog?category=deals" class="btn btn-outline-success w-100">
                  <i class="fas fa-gift me-1"></i> Deals & Freebies
                </a>
              </div>
              <div class="col-md-3 mb-2">
                <a href="/blog?category=guides" class="btn btn-outline-info w-100">
                  <i class="fas fa-book me-1"></i> How-To Guides
                </a>
              </div>
              <div class="col-md-3 mb-2">
                <a href="/blog?category=seasonal" class="btn btn-outline-warning w-100">
                  <i class="fas fa-calendar me-1"></i> Seasonal
                </a>
              </div>
              <div class="col-md-3 mb-2">
                <a href="/blog?category=tips" class="btn btn-outline-secondary w-100">
                  <i class="fas fa-lightbulb me-1"></i> Tips & Tricks
                </a>
              </div>
            </div>
          </div>';

    // Show recent posts suggestions
    if (!empty($recent_posts)) {
        echo '
          <!-- Suggested Posts -->
          <div class="mt-5">
            <h3 class="mb-4 text-center">📖 Popular Birthday Guides</h3>
            <div class="row">';
        
        foreach (array_slice($recent_posts, 0, 6) as $suggestion) {
            $excerpt = strip_tags($suggestion['content']);
            $excerpt = substr($excerpt, 0, 100) . '...';
            $read_time = getReadTime($suggestion['tags']);
            
            echo '
              <div class="col-lg-6 mb-3">
                <div class="card suggestion-card h-100 shadow-sm">
                  <div class="card-body">
                    <h5 class="card-title">
                      <a href="/blog/' . htmlspecialchars($suggestion['name']) . '" class="text-decoration-none">
                        ' . htmlspecialchars($suggestion['display_name']) . '
                      </a>
                    </h5>
                    <p class="card-text text-muted small">' . htmlspecialchars($excerpt) . '</p>
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted">
                        <i class="far fa-clock me-1"></i>' . $read_time . ' min read
                      </small>
                      <a href="/blog/' . htmlspecialchars($suggestion['name']) . '" class="btn btn-sm btn-outline-primary">
                        Read More
                      </a>
                    </div>
                  </div>
                </div>
              </div>';
        }
        
        echo '
            </div>
          </div>';
    }

    echo '
          <!-- Contact Section -->
          <div class="text-center mt-5 p-4 bg-light rounded">
            <h5>Still Can\'t Find What You\'re Looking For?</h5>
            <p class="text-muted mb-3">Let us know what birthday deals or guides you\'d like to see!</p>
            <a href="/contact" class="btn btn-primary me-2">Contact Us</a>
            <a href="/signup" class="btn btn-outline-primary">Get Free Birthday Deals</a>
          </div>

        </div>
      </div>
    </div>';

    $display_footertype='';
    include($dir['core_components'] . '/bg_footer.inc');
    $app->outputpage();
    exit;
}

// Get related posts (same grouping or recent posts, excluding current) - simplified
$related_sql = "SELECT * FROM bg_content 
                WHERE category='blog' AND type='post' AND status='active' AND id != ? 
                ORDER BY create_dt DESC 
                LIMIT 6";
$related_stmt = $database->prepare($related_sql);
$related_stmt->execute([$post['id']]);
$all_related = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prioritize posts from same grouping in PHP
$related_posts = [];
$same_grouping = [];
$other_posts = [];

foreach ($all_related as $related_post) {
    if ($related_post['grouping'] == $post['grouping']) {
        $same_grouping[] = $related_post;
    } else {
        $other_posts[] = $related_post;
    }
}

// Combine: same grouping first, then others, limit to 3 total
$related_posts = array_merge($same_grouping, $other_posts);
$related_posts = array_slice($related_posts, 0, 3);

# Try to update view count
$has_views = updateViewCount($database, $post['id']);

#-------------------------------------------------------------------------------
# SEO META DATA
#-------------------------------------------------------------------------------
$page_title = $post['display_name'];
$meta_description = $post['description'] ?: substr(strip_tags($post['content']), 0, 160);
$meta_keywords = $post['tags'];

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
.blog-post-content {
    font-size: 1.1rem;
    line-height: 1.7;
}
.blog-post-content h3 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #333;
}
.blog-post-content p {
    margin-bottom: 1.2rem;
}
.blog-post-content ul, .blog-post-content ol {
    margin-bottom: 1.2rem;
    padding-left: 2rem;
}
.blog-post-content li {
    margin-bottom: 0.5rem;
}
.blog-meta {
    font-size: 0.9rem;
    color: #6c757d;
}
.related-post {
    transition: transform 0.2s;
}
.related-post:hover {
    transform: translateY(-2px);
}
.share-buttons {
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    padding: 1rem 0;
    margin: 2rem 0;
}
</style>
';

echo '    
<div class="container main-content">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="/">Home</a></li>
          <li class="breadcrumb-item"><a href="/blog">Blog</a></li>';

if ($post['grouping'] && $post['grouping'] != 'general') {
    echo '<li class="breadcrumb-item">' . ucfirst($post['grouping']) . '</li>';
}

echo '
          <li class="breadcrumb-item active">' . htmlspecialchars($post['display_name']) . '</li>
        </ol>
      </nav>

      <!-- Post Header -->
      <div class="mb-4">
        <h1 class="display-6 fw-bold mb-3">' . htmlspecialchars($post['display_name']) . '</h1>
        <div class="blog-meta mb-4">
          <span class="me-4">
            <i class="far fa-calendar me-1"></i> 
            Published ' . date('F j, Y', strtotime($post['create_dt'])) . '
          </span>
          <span class="me-4">
            <i class="far fa-clock me-1"></i> 
            ' . getReadTime($post['tags']) . ' min read
          </span>';

if ($has_views && isset($post['views'])) {
    echo '
          <span class="me-4">
            <i class="far fa-eye me-1"></i> 
            ' . number_format($post['views']) . ' views
          </span>';
}

if ($post['grouping'] && $post['grouping'] != 'general') {
    echo '
          <span>
            <span class="badge bg-secondary">' . ucfirst($post['grouping']) . '</span>
          </span>';
}

echo '
        </div>
      </div>

      <!-- Post Content -->
      <div class="blog-post-content">
        ' . $post['content'] . '
      </div>

      <!-- Share Buttons -->
      <div class="share-buttons text-center">
        <h5 class="mb-3">Share this article</h5>
        <div class="d-flex justify-content-center gap-2">
          <a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode('https://' . $_SERVER['HTTP_HOST'] . '/blog/' . $post['name']) . '" 
             target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="fab fa-facebook-f me-1"></i> Facebook
          </a>
          <a href="https://x.com/intent/tweet?url=' . urlencode('https://' . $_SERVER['HTTP_HOST'] . '/blog/' . $post['name']) . '&text=' . urlencode($post['display_name']) . '" 
             target="_blank" class="btn btn-outline-info btn-sm">
            <i class="fab fa-twitter me-1"></i> Twitter
          </a>
          <a href="mailto:?subject=' . urlencode($post['display_name']) . '&body=' . urlencode('Check out this article: https://' . $_SERVER['HTTP_HOST'] . '/blog/' . $post['name']) . '" 
             class="btn btn-outline-secondary btn-sm">
            <i class="far fa-envelope me-1"></i> Email
          </a>
        </div>
      </div>

      <!-- Newsletter CTA -->
      <div class="card bg-light border-0 my-5">
        <div class="card-body text-center py-4">
          <h4 class="mb-3">Get More Birthday Deal Tips!</h4>
          <p class="mb-4">Join thousands of birthday celebrants getting exclusive deals and freebies delivered to their inbox.</p>
          <a href="/signup" class="btn btn-primary">Sign Up for Free</a>
        </div>
      </div>

    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
      <div class="sticky-top" style="top: 2rem;">
        
        <!-- Related Posts -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">Related Articles</h5>
          </div>
          <div class="card-body">';

if ($related_posts) {
    foreach ($related_posts as $related) {
        $related_excerpt = $related['description'] ?: substr(strip_tags($related['content']), 0, 80) . '...';
        echo '
            <div class="related-post mb-3 pb-3 border-bottom">
              <h6 class="mb-2">
                <a href="/blog/' . htmlspecialchars($related['name']) . '" class="text-decoration-none">
                  ' . htmlspecialchars($related['display_name']) . '
                </a>
              </h6>
              <small class="text-muted">
                ' . date('M j, Y', strtotime($related['create_dt'])) . ' • ' . getReadTime($related['tags']) . ' min read
              </small>';
        
        if ($related['grouping'] && $related['grouping'] != 'general') {
            echo '<br><span class="badge bg-light text-dark mt-1">' . ucfirst($related['grouping']) . '</span>';
        }
        
        echo '
            </div>';
    }
} else {
    echo '<p class="text-muted">No related articles yet.</p>';
}

echo '
          </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Popular Resources</h5>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              <a href="/signup" class="list-group-item list-group-item-action border-0 px-0">
                🎁 Get Free Birthday Deals
              </a>
              <a href="/blog" class="list-group-item list-group-item-action border-0 px-0">
                📖 All Birthday Guides
              </a>
              <a href="/about" class="list-group-item list-group-item-action border-0 px-0">
                ℹ️ How Birthday Gold Works
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>