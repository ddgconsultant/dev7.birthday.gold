<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Birthday.Gold Eligibility";

// Add modern tab styles for Birthday.Gold theme
$additionalstyles = '
<style>
/* Tab navigation with active bottom border - matching loginhistory */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    gap: 0;
    overflow: hidden;
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    background: none;
    border-radius: 0;
    position: relative;
    cursor: pointer;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd !important;
    background: none;
}

.custom-border-bottom {
    position: relative;
}

.custom-border-bottom::after {
    content: "";
    position: absolute;
    bottom: -30px;
    left: 10%;
    width: 80%;
    border-bottom: 1px solid var(--bs-secondary);
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Handle tab switching for modern tabs
    const tabLinks = document.querySelectorAll(".nav-tab-item");
    const tabPanes = document.querySelectorAll(".tab-pane");
    
    tabLinks.forEach(link => {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and panes
            tabLinks.forEach(tab => tab.classList.remove("active"));
            tabPanes.forEach(pane => {
                pane.classList.remove("show", "active");
            });
            
            // Add active class to clicked tab
            this.classList.add("active");
            
            // Show corresponding pane
            const targetId = this.getAttribute("href").substring(1);
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
            }
        });
    });
});
</script>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Dark header section -->
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-check-circle me-3"></i>Birthday.Gold Eligibility</h1>
        <p class="lead">Learn about who can use Birthday.Gold and important guidelines</p>
    </div>
</div>

<!-- Modern tab navigation -->
<div class="container mt-4">
    <nav class="nav-tabs-modern">
        <a href="#features" class="nav-tab-item active">
            <i class="bi bi-info-circle me-2"></i>General
        </a>
        <a href="#limitations" class="nav-tab-item">
            <i class="bi bi-list-check me-2"></i>Guidelines
        </a>
        <a href="#usa" class="nav-tab-item">
            <i class="bi bi-geo-alt me-2"></i>USA Only
        </a>
    </nav>
</div>

<!-- Tab content -->
<div class="container mb-5">
    <div class="tab-content" id="myTabContent">
        <!-- General Tab -->
        <div class="tab-pane fade show active" id="features" role="tabpanel" aria-labelledby="features-tab">
        <div class="container px-4 py-5" id="featured-3">
    <h2 class="pb-2 border-bottom">Who We Made It For</h2>
    <div class="row g-6 py-5 row-cols-1 row-cols-lg-3">
        <div class="feature col mb-5">
        <button class="btn btn-primary btn-lg me-3">
                                            <i class="bi bi-gift-fill fs-2"></i>
                                        </button>
                            
            <h3 class="fs-2">Exceptional Birthday Experience</h3>
            <p class="pe-5">Our Birthday.Gold service is designed to provide an exceptional birthday experience for our users. We offer exclusive deals, personalized gifts, and unforgettable experiences tailored to make your birthday special.</p>
        </div>
        <div class="feature col mb-5">
        <button class="btn btn-primary btn-lg me-3">
                                            <i class="bi bi-people-fill fs-2"></i>
                                        </button>
<?PHP
echo '                                        
            <h3 class="fs-2">Something for Everyone</h3>
            <p class="pe-5">Birthday.Gold is the ultimate destination for people of all ages looking to make their birthdays extra special. Whether you\'re a child eagerly awaiting your big day, a teenager planning a fun celebration with friends, an adult seeking unique ways to commemorate your milestone, or a senior wanting to create lasting memories with loved ones, Birthday.Gold has something for everyone. Our platform offers a wide array of birthday rewards and exclusive offers from over ' . $website['numberofbiz'] . '+ ' . $website['biznames'] . ', ensuring that your birthday is filled with joy and surprises.</p>
        </div>
'
?>
        <div class="feature col mb-5">
        <button class="btn btn-primary btn-lg me-3">
                                            <i class="bi bi-check-circle-fill fs-2"></i>
                                        </button>
                                 
            <h3 class="fs-2">Simple and Rewarding</h3>
            <p class="pe-5">Using Birthday.Gold is simple and rewarding. Sign up to discover personalized birthday offers tailored just for you. From freebies to VIP experiences, our extensive range of perks is designed to make your birthday unforgettable. Explore our celebration map to find the best deals in your area, and create a personalized birthday itinerary to ensure you don't miss out on any special treats. With Birthday.Gold, every birthday is a golden opportunity to celebrate life’s precious moments.</p>
        </div>
    </div>
</div>

        </div>


        <!-- Guidelines Tab -->
        <div class="tab-pane fade" id="limitations" role="tabpanel" aria-labelledby="limitations-tab">
            <div class="container px-4 py-5" id="custom-cards">
                <h2 class="pb-2 border-bottom">Important Guidelines and Limitations</h2>
                    <div class="row g-5 py-5">
                        <div class="col-md-5">
                        <p class="fs-4">Birthday.Gold strives to offer a wide range of rewards.</p>
                        <p class="fs-4">Certain preferences and restrictions may limit the number of business reward programs available for you to enroll in.</p>
                        <p class="fs-4">It's essential to be aware of these limitations to make the most informed decisions about your birthday celebrations and ensure that you can fully enjoy the perks and benefits tailored to your specific needs and preferences.</p>
           <a href="/terms" class="btn btn-primary">Terms and Conditions</a>
                        </div>
                        <div class="col-md-7">
                            <div class="row row-cols-1 row-cols-sm-2 g-6">

                            <div class="d-flex flex-column gap-2 mb-5 custom-border-bottom">
    <div class="d-flex align-items-center">
        <button class="btn btn-primary btn-lg me-3">
            <i class="bi bi-calendar fs-2"></i>
        </button>
        <h4 class="fw-semibold mb-0">Age Restrictions</h4>
    </div>
    <p class="text-muted mb-0 pb-0">While Birthday.Gold is open to everyone who wants to celebrate their birthday, a minimum age of 16 is required to sign up for a paid account. However, gift certificates can be enjoyed by users of any age, making it easy for everyone to benefit from our service. Please note that Birthday.Gold enrollments are age-restricted, and businesses may require identification to redeem certain rewards. This ensures that the rewards are appropriately matched to the eligible recipients.</p>
    <div class="mt-0 pt-0">
        <button type="button" class="btn btn-sm btn-info fs-8 py-1" data-bs-toggle="modal" data-bs-target="#ageRestrictionsModal">
            <i class="bi bi-info-circle"></i> Learn more
        </button>
    </div>
</div>



                                    <div class="d-flex flex-column gap-2 mb-5 custom-border-bottom">
    <div class="d-flex align-items-center">
        <button class="btn btn-primary btn-lg me-3">
            <i class="bi bi-people fs-2"></i>
        </button>
        <h4 class="fw-semibold mb-0">Parents with Children</h4>
    </div>
    <p class="text-muted mb-0 pb-0">Parents can sign up their children for our Birthday.Gold service, ensuring a memorable birthday experience for the whole family. As the responsible party, parents manage their child's account and select appropriate enrollments. It's important to note that parents may need to be present when redeeming rewards to verify the child's eligibility and facilitate the process. This ensures a seamless and enjoyable experience for both the children and the participating businesses.</p>
    <div class="mt-0 pt-0">
        <button type="button" class="btn btn-sm btn-info fs-8 py-1" data-bs-toggle="modal" data-bs-target="#parentsChildrenModal">
            <i class="bi bi-info-circle"></i> Learn more
        </button>
    </div>
</div>

<div class="d-flex flex-column gap-2 mb-5 custom-border-bottom">
    <div class="d-flex align-items-center">
        <button class="btn btn-primary btn-lg me-3">
            <i class="bi bi-star fs-2"></i>
        </button>
        <h4 class="fw-semibold mb-0">Honor Classes</h4>
    </div>
    <p class="text-muted mb-0 pb-0">Some businesses offer special privileges and discounts available for military personnel, teachers, and medical professionals as a token of appreciation for their service and dedication. You can indicate your special honor class with Birthday.Gold to receive distinctive rewards. Please note that businesses may require identification to redeem these rewards, as Birthday.Gold does not offer these rewards directly.</p>
    <div class="mt-0 pt-0">
        <button type="button" class="btn btn-sm btn-info fs-8 py-1" data-bs-toggle="modal" data-bs-target="#honorClassesModal">
            <i class="bi bi-info-circle"></i> Learn more
        </button>
    </div>
</div>



<div class="d-flex flex-column gap-2 mb-5 custom-border-bottom">
    <div class="d-flex align-items-center">
        <button class="btn btn-primary btn-lg me-3">
            <i class="bi bi-list-check fs-2"></i>
        </button>
        <h4 class="fw-semibold mb-0">Dietary Preferences</h4>
    </div>
    <p class="text-muted mb-0 pb-0">In your Enrollment Profile you can indicate some dietary preferences and restrictions.  Please note that the specific rewards offered by companies are beyond our control. We aim to provide options that suit your needs, but availability may vary based on the participating businesses. Rest assured, we continuously work with our partners to expand and enhance the variety of rewards to better meet the diverse needs of our users.</p>
    <div class="mt-0 pt-0">
        <button type="button" class="btn btn-sm btn-info fs-8 py-1" data-bs-toggle="modal" data-bs-target="#dietaryPreferencesModal">
            <i class="bi bi-info-circle"></i> Learn more
        </button>
    </div>
</div>

                                </div>

                            </div>
                        </div>
                    </div>
            </div>
        </div>




        <!-- USA Only Tab -->
        <div class="tab-pane fade" id="usa" role="tabpanel" aria-labelledby="usa-tab">
            <div class="container px-4 py-5">
    <h3>USA Only Geography</h3>
    
    <div class="d-flex align-items-start mb-3">
        <button class="btn btn-primary btn-lg me-3">
            <i class="bi bi-check-circle-fill fs-2"></i>
        </button>
<?PHP echo '        <p>Currently, our Birthday.Gold service is exclusively available within the United States. We are thrilled to offer a wide range of rewards and perks from a diverse array of ' . $website['biznames'] . ' across the nation. While we hope to expand our services to other regions in the future, our focus is on delivering an exceptional experience to our American users.</p>'; ?>
    </div>
    
    <div class="d-flex align-items-start mb-3">
        <button class="btn btn-primary btn-lg me-3">
            <i class="bi bi-check-circle-fill fs-2"></i>
        </button>
<?PHP echo '        <p>At this time, we partner exclusively with USA-based ' . $website['biznames'] . ', ensuring that our users have access to the best rewards and offers from businesses that are local and relevant to them. This allows us to provide a highly personalized experience, tailoring rewards and deals that are meaningful and accessible to each user.</p>'; ?>
    </div>
    
    <div class="d-flex align-items-start mb-3">
        <button class="btn btn-primary btn-lg me-3">
            <i class="bi bi-check-circle-fill fs-2"></i>
        </button>
        <p>One of the standout features of Birthday.Gold is the ability to limit your rewards to businesses that are local to you. Whether you prefer to celebrate close to home or explore new places within your community, our platform allows you to customize your rewards to fit your preferences. This means you can enjoy exclusive deals from your favorite local shops, restaurants, and entertainment venues, making your birthday celebration even more special.</p>
    </div>
    
    <div class="d-flex align-items-start mb-3">
        <button class="btn btn-primary btn-lg me-3">
            <i class="bi bi-check-circle-fill fs-2"></i>
        </button>
        <p>Our advanced navigation system takes your location into account, ensuring that you receive offers and rewards that are conveniently located. We understand the importance of ease and accessibility, which is why we only present businesses that are around you, unless you choose to adjust your settings to explore offers in other areas. This location-based feature ensures that your birthday celebration is not only memorable but also hassle-free, with all the best deals just a short distance away.</p>
                </div>
                
                <div class="d-flex align-items-start mb-3">
                    <button class="btn btn-primary btn-lg me-3">
                        <i class="bi bi-check-circle-fill fs-2"></i>
                    </button>
                    <p>With Birthday.Gold, you can rest assured that your birthday will be celebrated with the finest local experiences. From dining and shopping to entertainment and more, we bring you the best that your community has to offer. Join Birthday.Gold today and discover a world of rewards that make every birthday an extraordinary event!</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Age Restrictions Modal -->
<div class="modal fade" id="ageRestrictionsModal" tabindex="-1" aria-labelledby="ageRestrictionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ageRestrictionsModalLabel"><i class="bi bi-calendar me-2"></i>Age Restrictions & Requirements</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3">Account Creation Requirements</h6>
                <ul class="mb-4">
                    <li><strong>Minimum Age:</strong> 16 years old to create a paid Birthday.Gold account</li>
                    <li><strong>Verification:</strong> Valid identification may be required during account setup</li>
                    <li><strong>Parental Consent:</strong> Users under 18 may need parental or guardian consent</li>
                </ul>
                
                <h6 class="fw-bold mb-3">Gift Certificates & Family Accounts</h6>
                <ul class="mb-4">
                    <li>Gift certificates can be purchased for recipients of <strong>any age</strong></li>
                    <li>Parents can manage Birthday.Gold accounts for children under 16</li>
                    <li>Family accounts allow multiple profiles under one primary account holder</li>
                </ul>
                
                <h6 class="fw-bold mb-3">Business Reward Redemption</h6>
                <ul class="mb-4">
                    <li>Individual businesses may have their own age restrictions for specific rewards</li>
                    <li>Alcohol-related rewards require recipients to be 21+ with valid ID</li>
                    <li>Some entertainment venues may have age-appropriate reward tiers</li>
                    <li>Businesses reserve the right to verify age before reward redemption</li>
                </ul>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> Birthday.Gold acts as a platform connecting users with businesses. Each participating business maintains its own policies regarding age requirements for their specific rewards and services.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Parents with Children Modal -->
<div class="modal fade" id="parentsChildrenModal" tabindex="-1" aria-labelledby="parentsChildrenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="parentsChildrenModalLabel"><i class="bi bi-people me-2"></i>Parents with Children</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3">Family Account Management</h6>
                <ul class="mb-4">
                    <li>Parents can create and manage accounts for multiple children under one primary account</li>
                    <li>Each child can have their own profile with age-appropriate reward selections</li>
                    <li>Parents have full control over which businesses their children can enroll in</li>
                    <li>Email notifications can be sent to the parent's email instead of the child's</li>
                </ul>
                
                <h6 class="fw-bold mb-3">Reward Redemption Process</h6>
                <ul class="mb-4">
                    <li>Parents must be present when children under 16 redeem rewards</li>
                    <li>Parent's ID may be required along with proof of relationship</li>
                    <li>Some businesses may require advance notification for child reward redemptions</li>
                    <li>Digital rewards can be sent directly to the parent's email for distribution</li>
                </ul>
                
                <h6 class="fw-bold mb-3">Safety & Privacy Features</h6>
                <ul class="mb-4">
                    <li>Children's personal information is protected under COPPA guidelines</li>
                    <li>Location tracking is disabled for accounts of users under 13</li>
                    <li>Parents can review all enrollment activities and reward redemptions</li>
                    <li>Marketing communications are sent only to the parent's contact information</li>
                </ul>
                
                <div class="alert alert-success">
                    <i class="bi bi-shield-check me-2"></i>
                    <strong>Family-Friendly:</strong> Birthday.Gold is committed to providing a safe, fun, and rewarding experience for families celebrating birthdays together.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Honor Classes Modal -->
<div class="modal fade" id="honorClassesModal" tabindex="-1" aria-labelledby="honorClassesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="honorClassesModalLabel"><i class="bi bi-star me-2"></i>Honor Classes & Special Recognition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3">Eligible Honor Classes</h6>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="bi bi-shield-fill me-2"></i>Military Personnel</h6>
                        <ul>
                            <li>Active duty service members</li>
                            <li>Veterans</li>
                            <li>Reserve and National Guard</li>
                            <li>Military spouses and dependents</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="bi bi-mortarboard-fill me-2"></i>Educators</h6>
                        <ul>
                            <li>K-12 teachers</li>
                            <li>College professors</li>
                            <li>School administrators</li>
                            <li>Educational support staff</li>
                        </ul>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="bi bi-heart-pulse-fill me-2"></i>Healthcare Workers</h6>
                        <ul>
                            <li>Doctors and nurses</li>
                            <li>EMTs and paramedics</li>
                            <li>Hospital staff</li>
                            <li>Mental health professionals</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="bi bi-fire me-2"></i>First Responders</h6>
                        <ul>
                            <li>Police officers</li>
                            <li>Firefighters</li>
                            <li>Emergency dispatchers</li>
                            <li>Search and rescue personnel</li>
                        </ul>
                    </div>
                </div>
                
                <h6 class="fw-bold mb-3">Verification Requirements</h6>
                <ul class="mb-4">
                    <li>Valid professional ID or badge</li>
                    <li>Employment verification letter</li>
                    <li>Professional license or certification</li>
                    <li>DD Form 214 for veterans</li>
                </ul>
                
                <h6 class="fw-bold mb-3">Special Benefits</h6>
                <ul class="mb-4">
                    <li>Exclusive birthday rewards from participating businesses</li>
                    <li>Enhanced discounts and perks</li>
                    <li>Priority access to limited offers</li>
                    <li>Year-round appreciation discounts at select locations</li>
                </ul>
                
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Honor class benefits are provided directly by participating businesses. Verification requirements and available benefits may vary by location and business.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Dietary Preferences Modal -->
<div class="modal fade" id="dietaryPreferencesModal" tabindex="-1" aria-labelledby="dietaryPreferencesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dietaryPreferencesModalLabel"><i class="bi bi-list-check me-2"></i>Dietary Preferences & Restrictions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3">Supported Dietary Preferences</h6>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <ul>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Vegetarian</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Vegan</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Gluten-Free</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Dairy-Free</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Nut-Free</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Kosher</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Halal</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Low-Carb/Keto</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Sugar-Free</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Organic Preference</li>
                        </ul>
                    </div>
                </div>
                
                <h6 class="fw-bold mb-3">How It Works</h6>
                <ul class="mb-4">
                    <li>Set your dietary preferences in your Enrollment Profile</li>
                    <li>Birthday.Gold will highlight businesses that can accommodate your needs</li>
                    <li>Filter search results based on your dietary requirements</li>
                    <li>Receive personalized recommendations for compatible rewards</li>
                </ul>
                
                <h6 class="fw-bold mb-3">Important Considerations</h6>
                <ul class="mb-4">
                    <li>Not all businesses can accommodate all dietary restrictions</li>
                    <li>Always verify with the business directly about ingredients and preparation methods</li>
                    <li>Cross-contamination policies vary by establishment</li>
                    <li>Medical dietary needs should be discussed directly with each business</li>
                </ul>
                
                <h6 class="fw-bold mb-3">Allergy Information</h6>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-octagon me-2"></i>
                    <strong>Allergy Warning:</strong> If you have severe food allergies, always communicate directly with the business before redeeming food-related rewards. Birthday.Gold cannot guarantee the accuracy of allergen information provided by businesses.
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-lightbulb me-2"></i>
                    <strong>Tip:</strong> Many businesses offer alternative rewards for those with dietary restrictions, such as beverages, desserts, or non-food items. Check each business's full reward menu for options.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
