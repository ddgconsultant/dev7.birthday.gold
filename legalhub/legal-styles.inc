<?php
/**
 * Unified styles and components for Legal Hub pages
 * Include this file after setting $pagetitle
 */

// Unified CSS for all legal pages
$legalPageStyles = '
<style>
/* Legal Hub Unified Styles */
.legal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 0;
    margin-bottom: 3rem;
}

.legal-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.legal-header .lead {
    font-size: 1.25rem;
    opacity: 0.9;
}

/* Action Bar */
.legal-action-bar {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.legal-action-bar .btn {
    margin: 0.25rem;
}

.legal-action-bar .btn-group {
    margin: 0 0.5rem;
}

/* Content Styles */
.legal-content {
    max-width: 900px;
    margin: 0 auto;
}

.legal-content h2 {
    color: #333;
    font-size: 1.75rem;
    font-weight: 600;
    margin-top: 3rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.legal-content h3 {
    color: #495057;
    font-size: 1.4rem;
    font-weight: 600;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.legal-content p, .legal-content li {
    line-height: 1.7;
    color: #212529;
    margin-bottom: 1rem;
}

.legal-content ul, .legal-content ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
}

.legal-content .text-muted {
    font-size: 0.95rem;
}

/* View Toggle Styles */
.view-toggle {
    background: white;
    border: 1px solid #dee2e6;
}

.view-toggle .btn {
    border: none;
    background: transparent;
    color: #6c757d;
    padding: 0.375rem 1rem;
}

.view-toggle .btn.active {
    background: var(--bs-primary);
    color: white;
}

/* Print Styles */
@media print {
    .legal-action-bar,
    .legal-header,
    header,
    footer {
        display: none !important;
    }
    
    .legal-content {
        max-width: 100%;
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .legal-header h1 {
        font-size: 2rem;
    }
    
    .legal-action-bar {
        text-align: center;
    }
    
    .legal-action-bar .btn-group {
        display: flex;
        width: 100%;
        margin: 0.5rem 0;
    }
    
    .legal-action-bar .btn-group .btn {
        flex: 1;
    }
}
</style>
';

// Add styles to page
$additionalstyles = isset($additionalstyles) ? $additionalstyles . $legalPageStyles : $legalPageStyles;

/**
 * Generate the legal page header
 * @param string $title Page title
 * @param string $subtitle Optional subtitle
 */
function renderLegalHeader($title, $subtitle = '') {
    ?>
    <div class="legal-header">
        <div class="container">
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <?php if ($subtitle): ?>
                <p class="lead mb-0"><?php echo htmlspecialchars($subtitle); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Generate the action bar with consistent actions
 * @param array $options Configuration options
 */
function renderLegalActionBar($options = []) {
    global $current_user_data;
    
    // Default options
    $defaults = [
        'showViewToggle' => false,
        'showPrint' => true,
        'showShare' => true,
        'backUrl' => '/legalhub/',
        'backText' => 'Legal Hub'
    ];
    
    $options = array_merge($defaults, $options);
    
    // Determine back URL and text based on context
    if (isset($_REQUEST['register']) && $_REQUEST['register'] == 1) {
        $options['backUrl'] = '/signup';
        $options['backText'] = 'Sign Up';
    } elseif (!empty($current_user_data['user_id'])) {
        $options['backUrl'] = '/myaccount/';
        $options['backText'] = 'My Account';
    }
    
    ?>
    <div class="legal-action-bar">
        <div class="row align-items-center">
            <div class="col-md-4">
                <a href="<?php echo $options['backUrl']; ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to <?php echo $options['backText']; ?>
                </a>
            </div>
            
            <div class="col-md-4 text-center">
                <?php if ($options['showViewToggle']): ?>
                <div class="btn-group view-toggle" role="group">
                    <button type="button" class="btn btn-sm active" data-view="full">Full View</button>
                    <button type="button" class="btn btn-sm" data-view="summary">Summary</button>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4 text-end">
                <?php if ($options['showShare']): ?>
                <button class="btn btn-outline-secondary btn-sm" onclick="shareCurrentPage()">
                    <i class="bi bi-share me-1"></i>Share
                </button>
                <?php endif; ?>
                
                <?php if ($options['showPrint']): ?>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    // Share functionality
    function shareCurrentPage() {
        if (navigator.share) {
            navigator.share({
                title: document.title,
                url: window.location.href
            }).catch(err => console.log('Error sharing:', err));
        } else {
            // Fallback - copy to clipboard
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Link copied to clipboard!');
            });
        }
    }
    
    // View toggle functionality (if enabled)
    <?php if ($options['showViewToggle']): ?>
    document.querySelectorAll('.view-toggle .btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-toggle .btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const view = this.dataset.view;
            // Implement view switching logic here
            console.log('Switching to', view, 'view');
        });
    });
    <?php endif; ?>
    </script>
    <?php
}
?>