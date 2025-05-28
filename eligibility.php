<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<style>
        .thick-border .nav-tabs .nav-link {
            border-width: 3px;
        }
        .thick-border .nav-tabs .nav-link.active {
            border-bottom-width: 3px;
        }
        .custom-border-bottom {
    position: relative;
}

.custom-border-bottom::after {
    content: '';
    position: absolute;
    bottom: -30px; /* Adjust the position as needed */
    left: 10%;
    width: 80%;
    border-bottom: 1px solid var(--bs-secondary); /* Adjust the border style as needed */
}

    </style>

<div class="container main-content my-5 thick-border">
    <h1 class="text-center mb-4">Birthday Gold Eligibility</h1>
    <ul class="nav nav-tabs" id="myTab" role="tablist" >
        <li class="nav-item" role="presentation" >
            <button class="nav-link px-2 px-md-5 active" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab" aria-controls="features" aria-selected="true">General</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-5" id="limitations-tab" data-bs-toggle="tab" data-bs-target="#limitations" type="button" role="tab" aria-controls="limitations" aria-selected="false">Guidelines</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-5" id="usa-tab" data-bs-toggle="tab" data-bs-target="#usa" type="button" role="tab" aria-controls="usa" aria-selected="false">USA Only</button>
        </li>
    </ul>

    <!-- CONTENT FOR TABS -->
    <div class="tab-content" id="myTabContent">


   <!-- Features -->
        <div class="tab-pane fade show active p-4" id="features" role="tabpanel" aria-labelledby="features-tab" >
        <div class="container px-4 py-5" id="featured-3">
    <h2 class="pb-2 border-bottom">Who We Made It For</h2>
    <div class="row g-6 py-5 row-cols-1 row-cols-lg-3">
        <div class="feature col mb-5">
        <button class="btn btn-primary btn-lg me-3">
                                            <i class="bi bi-gift-fill fs-2"></i>
                                        </button>
                            
            <h3 class="fs-2">Exceptional Birthday Experience</h3>
            <p class="pe-5">Our Birthday Gold service is designed to provide an exceptional birthday experience for our users. We offer exclusive deals, personalized gifts, and unforgettable experiences tailored to make your birthday special.</p>
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


        <!-- Limitations -->
        <div class="tab-pane fade p-4" id="limitations" role="tabpanel" aria-labelledby="limitations-tab">
            <div class="container px-4 py-5" id="custom-cards">
                <h2 class="pb-2 border-bottom">Important Guidelines and Limitations</h2>
                    <div class="row g-5 py-5">
                        <div class="col-md-5">
                        <p class="fs-4">Birthday.Gold strives to offer a wide range of rewards.</p>
                        <p class="fs-4">Certain preferences and restrictions may limit the number of business reward programs available for you to enroll in.</p>
                        <p class="fs-4">It's essential to be aware of these limitations to make the most informed decisions about your birthday celebrations and ensure that you can fully enjoy the perks and benefits tailored to your specific needs and preferences.</p>
           <a href="#" class="btn btn-primary">Terms and Conditions</a>
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
        <button type="button" class="btn btn-sm btn-info fs-8 py-1" data-bs-toggle="modal" data-bs-target="#learnMoreModal">
            <i class="bi bi-info-circle"></i> Learn more
        </button>
    </div>
</div>

                                    <!-- Modal -->
                                    <div class="modal fade" id="learnMoreModal" tabindex="-1" aria-labelledby="learnMoreModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="learnMoreModalLabel">Learn More</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- Modal content goes here -->
                                                    Here you can add detailed information about Birthday.Gold and the age requirements for accounts and gift certificates.
                                                    <button type="button" class="btn btn-sm btn-info fs-8 py-1" data-bs-toggle="modal" data-bs-target="#learnMoreModal">
                                                        <i class="bi bi-info-circle"></i> Learn more
                                                    </button>
                                                    
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>



                                    <div class="d-flex flex-column gap-2 mb-5 custom-border-bottom">
    <div class="d-flex align-items-center">
        <button class="btn btn-primary btn-lg me-3">
            <i class="bi bi-people fs-2"></i>
        </button>
        <h4 class="fw-semibold mb-0">Parents with Children</h4>
    </div>
    <p class="text-muted mb-0 pb-0">Parents can sign up their children for our Birthday Gold service, ensuring a memorable birthday experience for the whole family. As the responsible party, parents manage their child's account and select appropriate enrollments. It's important to note that parents may need to be present when redeeming rewards to verify the child's eligibility and facilitate the process. This ensures a seamless and enjoyable experience for both the children and the participating businesses.</p>
    <div class="mt-0 pt-0">
        <button type="button" class="btn btn-sm btn-info fs-8 py-1" data-bs-toggle="modal" data-bs-target="#learnMoreModal">
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
    <p class="text-muted mb-0 pb-0">Some businesses offer special privileges and discounts available for military personnel, teachers, and medical professionals as a token of appreciation for their service and dedication. You can indicate your special honor class with Birthday Gold to receive distinctive rewards. Please note that businesses may require identification to redeem these rewards, as Birthday Gold does not offer these rewards directly.</p>
    <div class="mt-0 pt-0">
        <button type="button" class="btn btn-sm btn-info fs-8 py-1" data-bs-toggle="modal" data-bs-target="#learnMoreModal">
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
        <button type="button" class="btn btn-sm btn-info fs-8 py-1" data-bs-toggle="modal" data-bs-target="#learnMoreModal">
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




   <!-- Features with title -->
<div class="tab-pane fade p-4" id="usa" role="tabpanel" aria-labelledby="usa-tab">
    <h3>USA Only Geography</h3>
    
    <div class="d-flex align-items-start mb-3">
        <button class="btn btn-primary btn-lg me-3">
            <i class="bi bi-check-circle-fill fs-2"></i>
        </button>
<?PHP echo '        <p>Currently, our Birthday Gold service is exclusively available within the United States. We are thrilled to offer a wide range of rewards and perks from a diverse array of ' . $website['biznames'] . ' across the nation. While we hope to expand our services to other regions in the future, our focus is on delivering an exceptional experience to our American users.</p>'; ?>
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
        <p>One of the standout features of Birthday Gold is the ability to limit your rewards to businesses that are local to you. Whether you prefer to celebrate close to home or explore new places within your community, our platform allows you to customize your rewards to fit your preferences. This means you can enjoy exclusive deals from your favorite local shops, restaurants, and entertainment venues, making your birthday celebration even more special.</p>
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
        <p>With Birthday Gold, you can rest assured that your birthday will be celebrated with the finest local experiences. From dining and shopping to entertainment and more, we bring you the best that your community has to offer. Join Birthday Gold today and discover a world of rewards that make every birthday an extraordinary event!</p>
    </div>
</div>



    </div>


<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
