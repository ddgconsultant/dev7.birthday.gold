<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Add bg_theme.css for content-header-dark
$additionalstyles = '<link href="' . cssUrl('/public/css/v7/bg_theme.css') . '" rel="stylesheet">';

// Add pill button styles
$additionalstyles .= '
<style>
/* Filter Pills Container */
.filter-pills {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 2rem;
}

/* Card header adjustments */
.card-header .filter-pills {
    margin-bottom: 0;
    padding: 0.25rem 0;
}

.card-header.bg-light {
    background-color: #f8f9fa !important;
    border-bottom: 1px solid #dee2e6;
}

.filter-label {
    font-weight: 600;
    color: #6c757d;
    margin-right: 0.5rem;
}

/* Pill Buttons */
.filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 1rem;
    background: #f8f9fa;
    color: #212529;
    text-decoration: none;
    border-radius: 1.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-pill:hover {
    background: #e9ecef;
    color: #212529;
    text-decoration: none;
}

.filter-pill.active {
    background: #0d6efd;
    color: white;
}

.filter-pill.active:hover {
    background: #0b5ed7;
    color: white;
}

/* Org Chart Link Styling */
.org-chart-link {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 1rem;
    background: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 1.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.org-chart-link:hover {
    background: #5c636a;
    color: white;
    text-decoration: none;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-pills {
        flex-wrap: wrap;
    }
    
    .org-chart-link {
        margin-left: 0;
        margin-top: 0.5rem;
        width: 100%;
        justify-content: center;
    }
}

/* Fix badge hover states with enhanced visual feedback */
.badge {
    transition: all 0.2s ease;
}

.badge.bg-primary:hover {
    color: white !important;
    background-color: #0a58ca !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.25);
}

.badge.bg-success:hover {
    color: white !important;
    background-color: #157347 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(25, 135, 84, 0.25);
}

/* Ensure links within badges keep white text */
a.badge {
    text-decoration: none;
}

a.badge:hover {
    color: white !important;
    text-decoration: none;
}
</style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <h1>Join Our Team at Birthday.Gold</h1>
        <p class="lead">Be part of a crew dedicated to celebrating life's special moments</p>
    </div>
</div>

<div class="container py-5">
        <p class="lead text-center mb-5">We're looking for passionate, creative, and driven individuals to join us in making every birthday unforgettable. If you're ready to embark on a rewarding career journey, explore the opportunities with Birthday.Gold!</p>
    <hr class="mt-5">    
        <h3 class="mt-5 mb-3">Why Work With Us?</h3>
        <div class="mb-3">
            <h6 class="mb-0">Innovative Culture</h6>
            <p>At Birthday.Gold, innovation is at the heart of everything we do. We encourage our team to think outside the box and bring new ideas to the table.</p>
        </div>
        <div class="mb-3">
            <h6 class="mb-0">Diverse Team</h6>
            <p>We celebrate diversity and believe it enhances our ability to deliver unique birthday experiences. Join a team where your unique perspectives are valued.</p>
        </div>
        <div class="mb-3">
            <h6 class="mb-0">Work-Life Balance</h6>
            <p>We understand the importance of a healthy work-life balance and offer flexible working arrangements to suit your lifestyle.</p>
        </div>
      <!--  <div class="mb-3">
            <h6 class="mb-0">Career Growth</h6>
            <p>We're committed to your professional development and offer various opportunities for growth and advancement within the company.</p>
        </div>
-->
<hr class="mt-5">
        <h3 class="mt-5 mb-3">Benefits</h3>
        <ul>
           <!--  <li><strong>Comprehensive Health Coverage:</strong> We provide our employees with extensive health insurance plans that cover medical, dental, and vision care.</li>  -->
           <!-- <li><strong>Retirement Plans:</strong> Plan for the future with our competitive retirement plans, including employer contributions.</li>  -->
            <li><strong>Flexible PTO:</strong> If we can't celebrate life by taking time off... what are we even doing?  Enjoy time off to relax and rejuvenate, so you can return to work feeling refreshed and energized.</li>
            <li><strong>Employee Discounts:</strong> As part of our team, enjoy exclusive discounts and freebies from our wide range of partners.</li>
            <li><strong>Join A Start Up:</strong> Be a part of something that is growing and help shape our future.</li>
     </ul>
     <hr class="mt-5">
        <h3 class="mt-5 mb-3">Job Descriptions</h3>
        <p class="mb-4">We like to provide as much transparency as we can in regards to working with our team.  Which is why we list all of our open AND filled positions, so you can get to know our team and what they do as well as learn about the opportunity you are applying for.</p>

        <div class="card">
            <div class="card-header bg-light">
                <div class="filter-pills mb-0">
                    <span class="filter-label">FILTER:</span>
                    <button class="filter-pill active" onclick="filterJobs('all')">
                        <i class="bi bi-grid"></i> All
                    </button>
                    <button class="filter-pill" onclick="filterJobs('open')">
                        <i class="bi bi-briefcase"></i> Open
                    </button>
                    <button class="filter-pill" onclick="filterJobs('filled')">
                        <i class="bi bi-check-circle"></i> Filled
                    </button>
                    <a class="org-chart-link" target="jobdescription" href="https://whimsical.com/organization-chart-DLzWNLXvT4wTb8VHD2Q7TH">
                        <i class="bi bi-diagram-3"></i> Our Org Chart
                    </a>
                </div>
            </div>
            <div class="card-body pt-2 px-5">
                <!-- Your job listings here -->
                <div id="job-listings">
<?PHP


// Assuming $database is your PDO instance
$sql = "SELECT * FROM bg_content 
        WHERE category ='Job Listing' 
        ORDER BY FIELD(category, 'Job Listing', 'Role Description'), `grouping`, `rank`, create_dt DESC";

$stmt = $database->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    $date = new DateTime($row['create_dt']);
    $formattedDate = $date->format('m/d/Y');
    
    echo '<div class="my-5 all ' . ($row['type'] == 'Job Listing' ? 'open' : 'filled') . '">';
    echo '<h4 class="mb-0 mt-3 fw-bold">' . htmlspecialchars($row['display_name']) . '</h3>';

    if ($row['type'] == 'Job Listing') {
        // Job Listings - always blue badges
        $link = empty($row['description']) 
            ? '/career-apply?i=' .  $qik->encodeId($row['id']) 
            : htmlspecialchars($row['description']);
        
        echo '<a target="jobdescription" href="' . $link . '" ' .
             'class="badge bg-primary rounded-pill">' .
             'Open - posted ' . htmlspecialchars($formattedDate) . ' | Read More</a>';
             
        if (!empty($row['label'])) {
            echo '<p>' . htmlspecialchars($row['label']) . '</p>';
        }
    } else {
        // Role Descriptions - green badges with meet-our-team links
        $userId = !empty($row['description']) ? $row['description'] :'';
     if (!empty($userId)) {   echo '<a href="/meet-our-team?i=' . $qik->encodeId($userId) . '" ' .
             'class="badge bg-success text-white rounded-pill">' . 
             htmlspecialchars($row['label']) . '</a>';
     } else {

        echo '<span ' .
             'class="badge bg-success text-white rounded-pill">' . 
             htmlspecialchars($row['label']) . '</span>';
     }
             if (!empty($row['content'])) {  echo '<p>'. 
             htmlspecialchars($row['content']) .'</p>';
             }
            
    }
    
    echo '</div>';
}

echo '</div>'; // Close job-listings div
echo '</div></div>'; // Close card-body and card

echo '
    <hr class="mt-5">
        <h3 class="mt-5 mb-3">Disclaimers, Policies, and Notices</h3>
        <a href="employment-policies" class="btn btn-primary">Read our Employment Policies</a>
      ';
      /*
        <p><strong>Equal Opportunity Employment:</strong> At Birthday Gold, we are committed to creating an inclusive environment for all employees. We are proud to be an equal opportunity employer.</p>
        <p><strong>Data Protection and Privacy:</strong> We take the privacy and security of employee and customer data seriously. Learn more about our data protection policies.</p>
      */
      
      echo '
      <hr class="mt-5">
        <h3 class="mt-5 mb-3">Apply Online</h3>
        <p>Ready to make a difference in how people celebrate their special day?</p>
        <p>Log into your Birthday.Gold account and view the job listing to submit your application today and join us at Birthday.Gold. We are excited to see what you bring to our team!</p>
    </div>
';

?>

    <script>
function filterJobs(status) {
    // Get all job listing elements
    var jobs = document.getElementById('job-listings').children;

    for (var i = 0; i < jobs.length; i++) {
        // Check the status of each job
        if (status === 'all') {
            jobs[i].style.display = '';
        } else if (status === 'open' && jobs[i].classList.contains('open')) {
            jobs[i].style.display = '';
        } else if (status === 'filled' && jobs[i].classList.contains('filled')) {
            jobs[i].style.display = '';
        } else {
            jobs[i].style.display = 'none';
        }
    }
    
    // Update active state of filter pills
    var pills = document.querySelectorAll('.filter-pill');
    pills.forEach(function(pill) {
        pill.classList.remove('active');
    });
    
    // Add active class to the clicked pill
    if (status === 'all') {
        pills[0].classList.add('active');
    } else if (status === 'open') {
        pills[1].classList.add('active');
    } else if (status === 'filled') {
        pills[2].classList.add('active');
    }
}

// Initially show all jobs
filterJobs('all');
</script>



<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();