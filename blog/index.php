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
# SEO METADATA
#-------------------------------------------------------------------------------
$pagetitle = 'Birthday Deals & Freebies Blog - Birthday Gold';
$pagedescription = 'Discover the best birthday rewards, freebies, and celebration tips. Expert guides on maximizing birthday benefits from restaurants, retailers, and entertainment venues.';
$pagekeywords = 'birthday deals, birthday freebies, birthday rewards, birthday discounts, birthday guides, birthday tips';
$canonical_url = 'https://birthday.gold/blog/';

// Open Graph and Twitter Card meta tags
$og_image = 'https://birthday.gold/public/images/og-blog.jpg';
$additionalmetatags = [
    '<meta property="og:title" content="' . htmlspecialchars($pagetitle) . '">',
    '<meta property="og:description" content="' . htmlspecialchars($pagedescription) . '">',
    '<meta property="og:url" content="' . $canonical_url . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:image" content="' . $og_image . '">',
    '<meta name="twitter:card" content="summary_large_image">',
    '<meta name="twitter:title" content="' . htmlspecialchars($pagetitle) . '">',
    '<meta name="twitter:description" content="' . htmlspecialchars($pagedescription) . '">',
    '<meta name="twitter:image" content="' . $og_image . '">',
    '<link rel="canonical" href="' . $canonical_url . '">'
];

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
$header_flush = true; // Ensure header content is flush
$additionalstyles = [];
$additionalstyles[] = '
<style>
/* Content Header Dark styling */
.content-header-dark {
    background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    color: white;
    padding: 4rem 0 3rem;
    margin-bottom: 0;
    position: relative;
}

.content-header-dark h1 {
    color: white;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.content-header-dark .lead {
    color: rgba(255,255,255,0.9);
}

.content-header-dark .breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
}

.content-header-dark .breadcrumb-item + .breadcrumb-item::before {
    color: rgba(255,255,255,0.5);
}

.content-header-dark a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
}

.content-header-dark a:hover {
    color: white;
}

/* Blog styles */
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

/* SEO-friendly article structure */
article {
    margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .content-header-dark {
        padding: 3rem 0 2rem;
    }
    
    .content-header-dark h1 {
        font-size: 2rem;
    }
}
</style>
';

// Add structured data for SEO
$additionalscripts = [];

// Blog structured data
$blog_schema = [
    "@context" => "https://schema.org",
    "@type" => "Blog",
    "name" => "Birthday Gold Blog",
    "description" => $pagedescription,
    "url" => $canonical_url,
    "publisher" => [
        "@type" => "Organization",
        "name" => "Birthday Gold",
        "logo" => [
            "@type" => "ImageObject",
            "url" => "https://birthday.gold/public/images/logo.png"
        ]
    ]
];

// Add blog posts to schema
if (!empty($all_posts)) {
    $blog_schema["blogPost"] = [];
    foreach ($all_posts as $post) {
        $blog_schema["blogPost"][] = [
            "@type" => "BlogPosting",
            "headline" => $post['display_name'],
            "datePublished" => date('c', strtotime($post['create_dt'])),
            "dateModified" => date('c', strtotime($post['modify_dt'] ?? $post['create_dt'])),
            "author" => [
                "@type" => "Organization",
                "name" => "Birthday Gold"
            ],
            "url" => "https://birthday.gold/blog/" . $post['name'],
            "description" => createExcerpt($post['content'], $post['description'], 160)
        ];
    }
}

$additionalscripts[] = '<script type="application/ld+json">' . json_encode($blog_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';

// Include page headers
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

?>
<!-- Hero Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h1 class="display-4 mb-3 fw-bold">Birthday Gold Blog</h1>
                <p class="lead mb-4">Expert guides to maximize birthday rewards and freebies</p>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
<?php

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

?>

<?php

// Featured Post
if ($featured_post) {
    ?>
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
                <?php echo getCategoryBadge($featured_post['grouping']); ?>
                <h2 class="h3 mb-3">
                  <a href="/blog/<?php echo htmlspecialchars($featured_post['name']); ?>" class="text-decoration-none text-dark">
                    <?php echo htmlspecialchars($featured_post['display_name']); ?>
                  </a>
                </h2>
                <div class="blog-meta mb-3">
                  <span class="me-3">
                    <i class="far fa-calendar me-1"></i> 
                    <?php echo date('F j, Y', strtotime($featured_post['create_dt'])); ?>
                  </span>
                  <span class="read-time">
                    <i class="far fa-clock me-1"></i> 
                    <?php echo getReadTime($featured_post['tags']); ?> min read
                  </span>
                </div>
                <p class="blog-excerpt mb-4"><?php echo createExcerpt($featured_post['content'], $featured_post['description'], 200); ?></p>
                <a href="/blog/<?php echo htmlspecialchars($featured_post['name']); ?>" class="btn btn-primary">
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
    </div>
<?php
}

// Latest Posts Grid
?>
  <!-- Latest Posts -->
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Latest Birthday Guides</h3>
        <div class="text-muted">
          Page <?php echo $page; ?> of <?php echo $total_pages; ?> • <?php echo $total_posts; ?> articles
        </div>
      </div>
      
      <div class="row">
<?php

foreach ($latest_posts as $index => $post) {
    $is_new = (strtotime($post['create_dt']) > strtotime('-7 days'));
    $excerpt = createExcerpt($post['content'], $post['description'], 120);
    $post_url = '/blog/' . htmlspecialchars($post['name']);
    
    ?>
        <div class="col-lg-6 mb-4">
          <article class="card blog-preview h-100 border-0 shadow-sm blog-card">
            <div class="card-body p-4">
              <header>
                <div class="d-flex align-items-start justify-content-between mb-2">
                  <div>
                    <?php echo getCategoryBadge($post['grouping']);
    
    if ($is_new) {
        echo '<span class="badge bg-success ms-1">New</span>';
    }
    
    ?>
                  </div>
                  <div class="blog-meta text-end">
                    <time datetime="<?php echo date('Y-m-d', strtotime($post['create_dt'])); ?>"><?php echo date('M j', strtotime($post['create_dt'])); ?></time>
                  </div>
                </div>
                
                <h2 class="h4 card-title mb-3">
                  <a href="<?php echo $post_url; ?>" class="text-decoration-none text-dark">
                    <?php echo htmlspecialchars($post['display_name']); ?>
                  </a>
                </h2>
              </header>
              
              <p class="blog-excerpt text-muted mb-3"><?php echo $excerpt; ?></p>
              
              <footer class="d-flex justify-content-between align-items-center">
                <div class="blog-meta">
                  <i class="far fa-clock me-1"></i> 
                  <span><?php echo getReadTime($post['tags']); ?> min read</span>
                </div>
                <a href="<?php echo $post_url; ?>" class="btn btn-outline-primary btn-sm" aria-label="Read more about <?php echo htmlspecialchars($post['display_name']); ?>">
                  Read More
                </a>
              </footer>
            </div>
          </article>
        </div>
<?php
}

// If no posts, show message
if (empty($latest_posts) && !$featured_post) {
    ?>
        <div class="col-12">
          <div class="text-center py-5">
            <i class="fas fa-birthday-cake fa-3x text-muted mb-3"></i>
            <h4>No blog posts yet!</h4>
            <p class="text-muted">Check back soon for amazing birthday deal guides and celebration tips.</p>
          </div>
        </div>
<?php
}

?>
      </div><?php // End posts row

// Pagination
if ($total_pages > 1) {
    ?>
      <div class="pagination-wrapper">
        <nav aria-label="Blog pagination">
          <ul class="pagination justify-content-center">
<?php
    
    // Previous page
    if ($page > 1) {
        ?>
            <li class="page-item">
                <a class="page-link" href="/blog/<?php echo ($page > 2 ? '?page=' . ($page - 1) : ''); ?>">
                  <i class="fas fa-chevron-left me-1"></i> Previous
                </a>
            </li>
        <?php
    }
    
    // Page numbers
    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = ($i == $page) ? ' active' : '';
        $url = ($i == 1) ? '/blog/' : '/blog/?page=' . $i;
        ?>
            <li class="page-item<?php echo $active; ?>">
                <a class="page-link" href="<?php echo $url; ?>"><?php echo $i; ?></a>
            </li>
        <?php
    }
    
    // Next page
    if ($page < $total_pages) {
        ?>
            <li class="page-item">
                <a class="page-link" href="/blog/?page=<?php echo ($page + 1); ?>">
                  Next <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </li>
        <?php
    }
    
    ?>
          </ul>
        </nav>
      </div>
<?php
}

?>
      <!-- Newsletter Signup CTA -->
      <div class="card bg-gradient border-0 mb-5 mt-5" style="background: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%);">
        <div class="card-body text-center py-5 text-white">
          <h3 class="mb-3">🎉 Never Miss a Birthday Deal!</h3>
          <p class="mb-4 lead">Join thousands of birthday celebrants getting exclusive deals, freebies, and celebration tips delivered to their inbox.</p>
          <a href="/signup" class="btn btn-warning btn-lg px-5 py-3 fw-bold text-dark mb-4">
            <i class="fas fa-gift me-2"></i>Start Your Free Enrollment<i class="fas fa-arrow-right ms-2"></i>
          </a>
          <div class="mt-3">
            <small class="text-dark">✨ Free forever • ⚡ Instant access • 🎁 Exclusive deals</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>