<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# GET BLOG POSTS FROM DATABASE
#-------------------------------------------------------------------------------
// Get all active blog posts with simple query
$all_posts_sql = "SELECT * FROM bg_content 
                  WHERE category='blog' AND type='post' AND status='active' 
                  and publish_dt <= NOW()
                  AND (expire_dt IS NULL OR expire_dt > NOW())
                  ORDER BY create_dt DESC";
$all_posts_result = $database->query($all_posts_sql);
$all_posts = $all_posts_result ? $all_posts_result->fetchAll(PDO::FETCH_ASSOC) : [];

// Find featured post (rank 10 or less) in PHP
$featured_post = false;
$regular_posts = [];

foreach ($all_posts as $post) {
    if (!$featured_post && $post['rank'] <= 10) {
        $featured_post = $post;
    } else {
        $regular_posts[] = $post;
    }
}

// Pagination for regular posts
$posts_per_page = 8;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $posts_per_page;

$latest_posts = array_slice($regular_posts, $offset, $posts_per_page);
$total_posts = count($regular_posts);
$total_pages = ceil($total_posts / $posts_per_page);

// Ensure we have valid data
if (!$latest_posts) {
    $latest_posts = [];
}

#-------------------------------------------------------------------------------
# HELPER FUNCTIONS
#-------------------------------------------------------------------------------
function getReadTime($tags) {
    if (preg_match('/(\d+)\s*min\s*read/i', $tags, $matches)) {
        return $matches[1];
    }
    return 5; // default
}

function createExcerpt($content, $description = '', $length = 150) {
    // Use description if available, otherwise create from content
    if (!empty($description)) {
        return $description;
    }
    
    $text = strip_tags($content);
    $text = preg_replace('/\s+/', ' ', trim($text));
    
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}

function getCategoryBadge($grouping) {
    $categories = [
        'deals' => ['Deals & Freebies', 'bg-success'],
        'guides' => ['How-To Guides', 'bg-info'], 
        'seasonal' => ['Seasonal', 'bg-warning'],
        'tips' => ['Tips & Tricks', 'bg-secondary'],
        'general' => ['General', 'bg-light text-dark']
    ];
    
    $cat = $categories[$grouping] ?? $categories['general'];
    return '<span class="badge ' . $cat[1] . ' me-1">' . $cat[0] . '</span>';
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
.blog-preview {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.blog-preview:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.blog-meta {
    font-size: 0.9rem;
    color: #6c757d;
}
.read-time {
    color: #6c757d;
}
.blog-excerpt {
    color: #555;
    line-height: 1.6;
}
.category-badge {
    font-size: 0.75rem;
}
.featured-ribbon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 1;
}
.blog-card {
    position: relative;
    height: 100%;
}
.pagination-wrapper {
    margin-top: 3rem;
}
.blog-stats {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 2rem;
    margin-bottom: 2rem;
}
</style>
';

echo '    
<div class="container main-content">
  <!-- Blog Header -->
  <div class="row justify-content-center mb-4">
    <div class="col-lg-8 text-center">
      <h1 class="display-5 mb-3 fw-bold">Birthday Gold Blog</h1>
      <p class="lead text-muted">Discover the best birthday rewards, freebies, and celebration tips to make your special day unforgettable</p>
    </div>
  </div>';

// Blog stats section - calculate in PHP to avoid SQL issues
$total_blog_posts = count($all_posts);
$featured_count = 0;
$deals_count = 0;

foreach ($all_posts as $post) {
    if ($post['rank'] <= 10) {
        $featured_count++;
    }
    if ($post['grouping'] == 'deals') {
        $deals_count++;
    }
}

$stats = [
    'total_posts' => $total_blog_posts,
    'featured_posts' => $featured_count,
    'deal_posts' => $deals_count
];

echo '
  <!-- Blog Stats -->
  <div class="row justify-content-center mb-5">
    <div class="col-lg-10">
      <div class="blog-stats text-center">
        <div class="row">
          <div class="col-md-4">
            <h3 class="mb-1">' . $stats['total_posts'] . '</h3>
            <p class="mb-0">Birthday Guides</p>
          </div>
          <div class="col-md-4">
            <h3 class="mb-1">' . $stats['deal_posts'] . '</h3>
            <p class="mb-0">Deal Collections</p>
          </div>
          <div class="col-md-4">
            <h3 class="mb-1">100%</h3>
            <p class="mb-0">Free Resources</p>
          </div>
        </div>
      </div>
    </div>
  </div>';

// Featured Post
if ($featured_post) {
    echo '
    <!-- Featured Post -->
    <div class="row justify-content-center mb-5">
      <div class="col-lg-10">
        <div class="card blog-preview shadow-lg border-0 blog-card">
          <div class="featured-ribbon">
            <span class="badge bg-warning text-dark px-3 py-2">✨ Featured</span>
          </div>
          <div class="card-body p-4">
            <div class="row align-items-center">
              <div class="col-lg-8">
                ' . getCategoryBadge($featured_post['grouping']) . '
                <h2 class="h3 mb-3">
                  <a href="/blog/' . htmlspecialchars($featured_post['name']) . '" class="text-decoration-none text-dark">
                    ' . htmlspecialchars($featured_post['display_name']) . '
                  </a>
                </h2>
                <div class="blog-meta mb-3">
                  <span class="me-3">
                    <i class="far fa-calendar me-1"></i> 
                    ' . date('F j, Y', strtotime($featured_post['create_dt'])) . '
                  </span>
                  <span class="read-time">
                    <i class="far fa-clock me-1"></i> 
                    ' . getReadTime($featured_post['tags']) . ' min read
                  </span>
                </div>
                <p class="blog-excerpt mb-4">' . createExcerpt($featured_post['content'], $featured_post['description'], 200) . '</p>
                <a href="/blog/' . htmlspecialchars($featured_post['name']) . '" class="btn btn-primary">
                  Read Full Guide <i class="fas fa-arrow-right ms-1"></i>
                </a>
              </div>
              <div class="col-lg-4 text-center">
                <div class="bg-light rounded p-4">
                  <i class="fas fa-gift fa-3x text-primary mb-3"></i>
                  <h5>Featured Guide</h5>
                  <p class="text-muted small">Our most popular birthday deals resource</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>';
}

// Latest Posts Grid
echo '
  <!-- Latest Posts -->
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Latest Birthday Guides</h3>
        <div class="text-muted">
          Page ' . $page . ' of ' . $total_pages . ' • ' . $total_posts . ' articles
        </div>
      </div>
      
      <div class="row">';

foreach ($latest_posts as $index => $post) {
    $is_new = (strtotime($post['create_dt']) > strtotime('-7 days'));
    $excerpt = createExcerpt($post['content'], $post['description'], 120);
    
    echo '
        <div class="col-lg-6 mb-4">
          <div class="card blog-preview h-100 border-0 shadow-sm blog-card">
            <div class="card-body p-4">
              <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                  ' . getCategoryBadge($post['grouping']);
    
    if ($is_new) {
        echo '<span class="badge bg-success ms-1">New</span>';
    }
    
    echo '
                </div>
                <div class="blog-meta text-end">
                  <small>' . date('M j', strtotime($post['create_dt'])) . '</small>
                </div>
              </div>
              
              <h4 class="card-title mb-3">
                <a href="/blog/' . htmlspecialchars($post['name']) . '" class="text-decoration-none text-dark">
                  ' . htmlspecialchars($post['display_name']) . '
                </a>
              </h4>
              
              <p class="blog-excerpt text-muted mb-3">' . $excerpt . '</p>
              
              <div class="d-flex justify-content-between align-items-center">
                <div class="blog-meta">
                  <i class="far fa-clock me-1"></i> 
                  ' . getReadTime($post['tags']) . ' min read
                </div>
                <a href="/blog/' . htmlspecialchars($post['name']) . '" class="btn btn-outline-primary btn-sm">
                  Read More
                </a>
              </div>
            </div>
          </div>
        </div>';
}

// If no posts, show message
if (empty($latest_posts) && !$featured_post) {
    echo '
        <div class="col-12">
          <div class="text-center py-5">
            <i class="fas fa-birthday-cake fa-3x text-muted mb-3"></i>
            <h4>No blog posts yet!</h4>
            <p class="text-muted">Check back soon for amazing birthday deal guides and celebration tips.</p>
          </div>
        </div>';
}

echo '
      </div>'; // End posts row

// Pagination
if ($total_pages > 1) {
    echo '
      <div class="pagination-wrapper">
        <nav aria-label="Blog pagination">
          <ul class="pagination justify-content-center">';
    
    // Previous page
    if ($page > 1) {
        echo '<li class="page-item">
                <a class="page-link" href="/blog/' . ($page > 2 ? '?page=' . ($page - 1) : '') . '">
                  <i class="fas fa-chevron-left me-1"></i> Previous
                </a>
              </li>';
    }
    
    // Page numbers
    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = ($i == $page) ? ' active' : '';
        $url = ($i == 1) ? '/blog/' : '/blog/?page=' . $i;
        echo '<li class="page-item' . $active . '">
                <a class="page-link" href="' . $url . '">' . $i . '</a>
              </li>';
    }
    
    // Next page
    if ($page < $total_pages) {
        echo '<li class="page-item">
                <a class="page-link" href="/blog/?page=' . ($page + 1) . '">
                  Next <i class="fas fa-chevron-right ms-1"></i>
                </a>
              </li>';
    }
    
    echo '
          </ul>
        </nav>
      </div>';
}

echo '
      <!-- Newsletter Signup CTA -->
      <div class="card bg-gradient border-0 mb-5 mt-5" style="background: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%);">
        <div class="card-body text-center py-5 text-white">
          <h3 class="mb-3">🎉 Never Miss a Birthday Deal!</h3>
          <p class="mb-4 lead">Join thousands of birthday celebrants getting exclusive deals, freebies, and celebration tips delivered to their inbox.</p>
          <a href="/signup" class="btn btn-light btn-lg px-4">
            Start Your Free Enrollment
          </a>
          <div class="mt-3">
            <small class="text-white-50">✨ Free forever • ⚡ Instant access • 🎁 Exclusive deals</small>
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