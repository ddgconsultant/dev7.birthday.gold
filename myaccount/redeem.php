<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------

$bodycontentclass = '';

$additionalstyles.="
<style>
/* Mobile-first responsive design */
.redeem-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    margin-bottom: 1.5rem;
}

.redeem-title-wrapper {
    display: flex;
    align-items: baseline;
    gap: 1rem;
}

.redeem-title {
    font-size: 2rem;
    font-weight: 400;
    color: #212529;
    margin: 0;
}

@media (min-width: 768px) {
    .redeem-title {
        font-size: 2.5rem;
    }
}

.reward-count-badge {
    background-color: #28a745;
    color: white;
    font-size: 1.5rem;
    font-weight: 500;
    padding: 0.375rem 1rem;
    border-radius: 50rem;
    vertical-align: baseline;
    margin-bottom: 3px;
}

/* Section headers */
.section-header {
    font-size: 1.75rem;
    font-weight: 400;
    color: #212529;
    margin-bottom: 1.5rem;
}

/* Enhanced flip cards */
.flip-card {
    background-color: transparent;
    width: 100%;
    height: 280px;
    perspective: 1000px;
    margin-bottom: 1.5rem;
}

@media (min-width: 576px) {
    .flip-card {
        height: 300px;
    }
}

@media (min-width: 768px) {
    .flip-card {
        height: 320px;
    }
}

.flip-card-inner {
position: relative;
width: 100%;
height: 100%;
text-align: center;
transition: transform 0.6s;
transform-style: preserve-3d;
}

.flip-card:hover .flip-card-inner,
.flip-card.flipped .flip-card-inner {
transform: rotateY(180deg);
}

.flip-card-front, .flip-card-back {
    position: absolute;
    width: 100%;
    height: 100%;
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
    border-radius: 15px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease;
}

.flip-card:hover .flip-card-front,
.flip-card:hover .flip-card-back {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.flip-card-front {
    background-color: #fff;
    padding: 1.25rem;
    justify-content: space-between;
}

.card-logo-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 120px;
    width: 100%;
    overflow: hidden;
}

.card-logo-wrapper img {
    max-height: 100px;
    max-width: 80%;
    object-fit: contain;
}

@media (min-width: 576px) {
    .card-logo-wrapper {
        height: 140px;
    }
    .card-logo-wrapper img {
        max-height: 120px;
    }
}

.flip-card-back {
    background-color: #f8f9fa;
    transform: rotateY(180deg);
    padding: 1.25rem;
    border: 1px solid #dee2e6;
}

/* Mobile touch indicator */
.flip-indicator {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 4px;
}

@media (min-width: 768px) {
    .flip-indicator {
        display: none;
    }
}

/* Availability badges */
.availability-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-available {
    background: #28a745;
    color: white;
}

.badge-upcoming {
    background: #ffc107;
    color: #212529;
}

.badge-expiring {
    background: #dc3545;
    color: white;
}

/* Company info section */
.company-info {
    text-align: center;
    padding: 1rem 0;
}

.company-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.5rem;
}

.reward-description {
    font-size: 0.9rem;
    color: #28a745;
    font-weight: 500;
}

/* Redeem instructions */
.redeem-instructions {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
    font-size: 0.9rem;
    line-height: 1.6;
}

.redeem-header-back {
    background: #f8f9fa;
    color: #495057;
    padding: 0.75rem;
    margin: -1.25rem -1.25rem 1rem;
    text-align: center;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.redeem-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.btn-redeem {
    flex: 1;
    min-width: 120px;
    font-weight: 600;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.875rem;
}

.btn-maps {
    padding: 0.75rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 4rem 1rem;
}

.empty-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1rem;
}

.empty-title {
    font-size: 1.5rem;
    color: #495057;
    margin-bottom: 0.5rem;
}

.empty-text {
    color: #6c757d;
    margin-bottom: 2rem;
}

/* Filter pills */
.filter-pills {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.filter-pill {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    background: #e9ecef;
    color: #495057;
    font-size: 0.875rem;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-pill:hover {
    background: #dee2e6;
}

.filter-pill.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

/* Responsive grid adjustments */
@media (max-width: 575px) {
    .col-lg-4 {
        padding-left: 10px;
        padding-right: 10px;
    }
}

/* Loading animation for cards */
.flip-card {
    animation: fadeInUp 0.5s ease-out;
    animation-fill-mode: both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Stagger animation */
.flip-card:nth-child(1) { animation-delay: 0.1s; }
.flip-card:nth-child(2) { animation-delay: 0.2s; }
.flip-card:nth-child(3) { animation-delay: 0.3s; }
.flip-card:nth-child(4) { animation-delay: 0.4s; }
.flip-card:nth-child(5) { animation-delay: 0.5s; }
.flip-card:nth-child(6) { animation-delay: 0.6s; }

</style>
";

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');

$results = $account->getbusinesslist_rewards($current_user_data, 'card', '"success", "success-btn"', 10, true);
if (!empty($results)) {
$show_rewards = true;
} else {
$show_rewards = false;
}

    echo '
<div class="col-md-9 col-lg-9">
    <div class="redeem-header">
        <div class="redeem-title-wrapper">
            <h1 class="redeem-title">Your Rewards</h1>
            '.($show_rewards && count($results) > 0 ? '<span class="reward-count-badge">'.count($results).'</span>' : '').'  
        </div>
        <a href="/myaccount/redeem-list" class="btn btn-primary">View All Rewards</a>
    </div>


';

    // Show section header and filters if there are rewards
    if ($show_rewards && count($results) > 0) {
        echo '<h2 class="section-header">Currently Available Rewards</h2>';
        
        // Check what status types are available
        $has_available = false;
        $has_upcoming = false;
        $has_expiring = false;
        
        foreach ($results as $company) {
            $availability_tag = $app->getAvailabilityTag($company['availability_from_date'], $company['expiration_date']);
            if (!empty($availability_tag['availability'])) {
                if (strpos($availability_tag['availability'], 'Coming Soon') !== false) {
                    $has_upcoming = true;
                } elseif (strpos($availability_tag['availability'], 'Expiring') !== false) {
                    $has_expiring = true;
                } else {
                    $has_available = true;
                }
            } else {
                $has_available = true;
            }
        }
        
        // Only show filter pills if there are multiple status types
        if (($has_available && ($has_upcoming || $has_expiring)) || ($has_upcoming && $has_expiring)) {
            echo '
            <!-- Filter pills -->
            <div class="filter-pills mb-3">
                <div class="filter-pill active" data-filter="all">All Rewards</div>';
                
            if ($has_available) {
                echo '<div class="filter-pill" data-filter="available">Available Now</div>';
            }
            if ($has_upcoming) {
                echo '<div class="filter-pill" data-filter="upcoming">Coming Soon</div>';
            }
            if ($has_expiring) {
                echo '<div class="filter-pill" data-filter="expiring">Expiring Soon</div>';
            }
            
            echo '</div>';
        }
    }

    echo '<div class="row g-3">';

    if (!$show_rewards) {
        echo '
        <div class="col-12">
            <div class="empty-state">
                <div class="empty-icon">🎁</div>
                <h3 class="empty-title">No Rewards Yet</h3>
                <p class="empty-text">You currently have no rewards available<br>
                <small class="text-muted">Last checked: '. date('l, F j, Y g:i A').'</small></p>
                <a href="/myaccount/enrollment" class="btn btn-primary">Enroll in Programs</a>
            </div>
        </div>
        ';
    } else {
        foreach ($results as $company) {
            // Determine if the reward is available now or in the future
            $availability_tag = $app->getAvailabilityTag($company['availability_from_date'], $company['expiration_date']);

    
            // Determine availability status
            $status_class = 'available';
            $badge_class = 'badge-available';
            $badge_text = 'Available';
            
            if (!empty($availability_tag['availability'])) {
                if (strpos($availability_tag['availability'], 'Coming Soon') !== false) {
                    $status_class = 'upcoming';
                    $badge_class = 'badge-upcoming';
                    $badge_text = 'Coming Soon';
                } elseif (strpos($availability_tag['availability'], 'Expiring') !== false) {
                    $status_class = 'expiring';
                    $badge_class = 'badge-expiring';
                    $badge_text = 'Expiring Soon';
                }
            }
            
            echo '
            <!--  Flip Card ' . $company['company_name'] . ' -->
            <div class="col-12 col-md-6 col-lg-4" data-status="'.$status_class.'">
                <div class="flip-card">
                    <div class="flip-card-inner">
                        <!-- Front of the card -->
                        <div class="flip-card-front">
                            <div class="availability-badge '.$badge_class.'">'.$badge_text.'</div>
                            <div class="flip-indicator">
                                <i class="fas fa-hand-pointer"></i> Tap
                            </div>
                            <div class="card-logo-wrapper">
                                <img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="' . htmlspecialchars($company['company_name']) . ' Logo">
                            </div>
                            <div class="company-info">
                                <h5 class="company-name">' . $company['company_name'] . '</h5>
                                <p class="reward-description">' . ucfirst($company['spinner_description'] ?? 'Birthday ' . $company['category'] . ' reward') . '</p>
                            </div>
                        </div>
                        <!-- Back of the card -->
                        <div class="flip-card-back">
                            <div class="redeem-header-back">How to Redeem</div>
                            <div class="redeem-instructions">' . $company['redeem_instructions'] . '</div>
                            <div class="redeem-actions">
                                '.(strpos($app->mapsearchlink($company, $current_user_data), 'href') !== false ? 
                                    str_replace('class="btn', 'class="btn btn-maps btn-outline-secondary', $app->mapsearchlink($company, $current_user_data)) : '').'
                                <a href="/myaccount/redeem-details?id=' .$qik->encodeId($company['reward_id']) . '" class="btn btn-redeem btn-success">
                                    ' . ($availability_tag['redeembuttontext'] ?? 'Redeem Now'). '
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            ';
            
        }
    
        echo '
        </div>
    
        <div class="text-center mt-5">
            <a href="/myaccount/redeem-list" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </div>
        ';
    }
    
?>

<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterPills = document.querySelectorAll('.filter-pill');
    const cards = document.querySelectorAll('[data-status]');
    
    filterPills.forEach(pill => {
        pill.addEventListener('click', function() {
            // Update active state
            filterPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            
            cards.forEach(card => {
                if (filter === 'all' || card.getAttribute('data-status') === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Mobile touch handling for flip cards
    if ('ontouchstart' in window) {
        document.querySelectorAll('.flip-card').forEach(card => {
            card.addEventListener('click', function() {
                this.classList.toggle('flipped');
            });
        });
    }
});
</script>

</div> <!-- close col-md-9 -->
</div> <!-- close row -->
</div> <!-- close container -->
</div> <!-- close main-content -->
<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();