<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container main-content p-5">
        <h1 class="mb-4">Join Our Team at Birthday Gold</h1>
        <p>At Birthday Gold, we're more than just a team – we're a crew dedicated to celebrating life's special moments. We're looking for passionate, creative, and driven individuals to join us in making every birthday unforgettable. If you're ready to embark on a rewarding career journey, explore the opportunities with Birthday Gold!</p>
    <hr class="mt-5">    
        <h3 class="mt-5 mb-3">Why Work With Us?</h3>
        <div class="mb-3">
            <h6 class="mb-0">Innovative Culture</h6>
            <p>At Birthday Gold, innovation is at the heart of everything we do. We encourage our team to think outside the box and bring new ideas to the table.</p>
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
        We like to provide as much transparency as we can in regards to working with our team.  Which is why we list all of our open AND filled positions, so you can get to know our team and what they do as well as learn about the opportunity you are applying for.

        <div class="filter-buttons mt-3 fw-bold">FILTER: 
    <button class="btn btn-sm btn-primary" onclick="filterJobs('all')">ALL</button>
    <button class="btn btn-sm btn-primary" onclick="filterJobs('open')">Open</button>
    <button class="btn btn-sm btn-primary" onclick="filterJobs('filled')">Filled</button>
    <a class="ms-5 btn btn-sm btn-primary"  target="jobdescription"  href="https://whimsical.com/organization-chart-DLzWNLXvT4wTb8VHD2Q7TH">Our Org Chart</a>
</div>

<!-- Your job listings here -->
<div id="job-listings" class="my-5">
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

echo '</div>';

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
        <p>Log into your Birthday.Gold account and view the job listing to submit your application today and join us at Birthday Gold. We are excited to see what you bring to our team!</p>
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
}

// Initially show all jobs
filterJobs('all');
</script>



<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();