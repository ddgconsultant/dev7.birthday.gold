<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$pagedata['pagetitle']='Birthday Rewards Enrollment - Birthday Gold';
$pagedata['metakeywords']='Birthday Rewards Enrollment, Birthday Rewards, Birthday Gold, Reward Enrollment';
$pagedata['metadescriptions']='Join Birthday Gold for easy Birthday Rewards Enrollment! Discover exclusive birthday rewards & perks. Sign up now for the best reward enrollment deals!';



include($dir['core_components'] . '/bg_pagestart.inc');

$additionalstyles.='
<style>
#megaMenuButton {
  font-size: 1.5rem !important; /* Adjust the font size as needed */
}
</style>
<!-- ===============================================-->
<!--    Header JS & Components-->
<!-- ===============================================-->
<meta name="theme-color" content="#ffffff">
<script src="/public/assets/js/config.js"></script>
<!-- ===============================================-->
<!--    Stylesheets: -->
<!-- ===============================================-->

<link href="/public/css/v3/theme.css" rel="stylesheet" id="style-default">

 ';
#<link href="/public/assets/css/theme.css" rel="stylesheet" id="style-default">
#<link href="/public/assets/css/user.css" rel="stylesheet" id="user-style-default">


$bodyclass='class="d-flex"';


$additionalstyles.='
<style>
body { display: block !important; flex-direction: unset !important; min-height: 100% !important;       /*  min-height: unset !important; */ }
.main-content { flex: unset !important; padding: 0px !important; }
</style>';


$additionalstyles.='
	<link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" >
    ';

$additionalstyles .= "
 <style >
/* Previous styles remain */
:root { --gold: #FFD700; --dark-gold: #B8860B; }
.hero-section { background: linear-gradient(135deg, #fff8e8 0%, #fff 100%); }
.features-section { background-color: #fff8e8; padding: 80px 0; }
.preview-card { background-color: #fff4e6; padding: 40px; border-radius: 1rem; box-shadow: 0 4px 6px rgba(200, 200, 200, 0.5); }
.btn-gold { background-color: var(--gold); border-color: var(--dark-gold); color: #000; }
.btn-gold:hover { background-color: var(--dark-gold); border-color: var(--dark-gold); color: #fff; }
.celebration-icon { font-size: 6rem; color: var(--gold); }
.trusted-companies { opacity: 0.7; }
.card { transition: transform 0.3s ease; }
.card:hover { transform: translateY(-5px); }
/* New styles */
.steps-section { position: relative; padding: 80px 0; }
.steps-section::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, var(--gold), transparent); }
.step-number { width: 40px; height: 40px; background-color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 1rem; }
.testimonial-card { background: linear-gradient(135deg, #fff8e8 0%, #fff 100%); border-radius: 1rem; padding: 2rem; margin-bottom: 2rem; }
.stats-box { text-align: center; padding: 2rem; background-color: #fff; border-radius: 1rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
.faq-section { background-color: #C8D5E4; }
</style > <style > .hero-section { background: linear-gradient(135deg, #fff8e8 0%, #fff 100%); position: relative; overflow: hidden; }
.hero-background { opacity: 0.3; pointer-events: none; }
.step-icon { width: 80px; height: 80px; margin: 0 auto; position: relative; }
.step-icon::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(45deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.3)); border-radius: 50%; z-index: -1; }
/* Animation for confetti and decorative elements */
@keyframes float {
0% { transform: translateY(0); }
50% { transform: translateY(-10px); }
100% { transform: translateY(0); }
}
.hero-background g { animation: float 3s ease-in-out infinite; }
.hero-background g:nth-child(2) { animation-delay: -0.5s; }
.hero-background g:nth-child(3) { animation-delay: -1s; }
.hero-section { background-image: url('/public/assets/img/generic/14308557_5454272.jpg'); background-size: cover;        /* Ensures the image covers the entire section */ background-position: center;        /* Centers the image */ background-repeat: no-repeat;        /* Prevents tiling of the image */ width: 100%;        /* Ensures the section takes up the full width of the viewport */ }
</style > <style > .sectionpad { padding: 180px 0 !important; }
.iconxsize { font-size: 48px }
.achieve-p { color: black; font-size: 22px; font-weight: bold; margin-bottom: 4px; }
</style >
";


$additionalstyles.="
<style >
.slider { height: 60px; position: relative; width: 100%; display: grid; place-items: center; overflow: hidden; }
.slider::before,
.slider::after { position: absolute; background-image: linear-gradient(to right, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0) 100%); content: ''; height: 100%; width: 25%; z-index: 2; pointer-events: none; }
.slider::before { left: 0; top: 0; }
.slider::after { right: 0; top: 0; transform: rotateZ(180deg); }
.slide-track { width: calc(260px * 20); display: flex; animation: scroll 20s linear infinite; justify-content: space-between; }
.slide { width: 200px; height: 80px; padding: 0px 30px; display: grid; place-items: center; transition: 0.5s; cursor: pointer; }
.slide img { width: 80px; }
.slide:hover { transform: scale(0.8) }
@keyframes scroll {
0% { transform: translateX(0px); }
100% { transform: translateX(calc(-200px * 5)); }
}
@media screen and (max-width:768px) {
.slide-track { width: calc(80px * 20); }
.slide { width: 80px; }
@keyframes scroll {
0% { transform: translateX(0px); }
100% { transform: translateX(calc(-80px * 5)); }
}
}
</style >
";

include($dir['core_components'] . '/bg_header.inc');
?>


<link href="/public/assets/vendors/swiper/swiper-bundle.min.css" rel="stylesheet">




  <!-- ===============================================-->
  <!--    Main Content-->
  <!-- ===============================================-->

  <main class="main container-fluid main-content" id="top">
    <!-- ============================================-->
    <!-- section begin ============================-->
    <section class="py-0 overflow-hidden" id="banner" data-bs-theme="light">
      <div class="bg-holder overlay" style="background-image:url(/public/assets/img/generic/14308557_5454272.jpg);background-position: center bottom;">
      </div>
      <!--/.bg-holder-->
<?PHP
echo '
      <div class="container">
        <div class="row flex-center pt-5 pt-lg-8 pb-lg-9 pb-xl-0">
          <div class="col-md-11 col-lg-8 col-xl-5 pb-7 pb-xl-9 text-center text-xl-start">
           
  <h1 class="display-1 fw-bold mb-4 text-white">
            You Pick,<br>
            We Enroll.
        </h1>
        <p class="lead mb-5 fw-bold text-white">
            Select birthday <span class="typed-text fw-bold" data-typed-text=\'["rewards","drinks","discounts","food","points","freebies"]\'></span><br>from a variety of '.$website['biznames'].'.<br>
            We\'ll handle the enrollment so you can focus on celebrating.
        </p>
        <div class="">
        <a href="/signup" class="btn btn-gold btn-lg px-4 py-3 mb-3"><h1 class="py-0 my-0 text-black">Start Collecting Rewards</h1></a>
        <br>
        <a href="/discover" class="btn btn-gold btn-lg px-4">Discover Available Rewards</a>
        </div>


          </div>
          <div class="col-xl-7 align-self-end mt-4 mt-xl-0">
            <a class="img-landing-banner rounded" href="/">
              <img class="img-fluid d-dark-none" src="//cdn.birthday.gold/public/images/bg_capture1.jpg" alt="">
              <img class="img-fluid d-light-none" src="//cdn.birthday.gold/public/images/bg_capture1.jpg" alt="">
            </a>
          </div>
        </div>
      </div>
      <!-- end of .container-->
';
?>
    </section>
    <!-- section close ============================-->
    <!-- ============================================-->




    <!-- ============================================-->
    <!-- section begin ============================-->

  <?PHP
  $additionalstyles.='
<style > 
.logo-banner-sandbox { overflow: hidden; box-sizing: border-box; position: relative; width: 100%; padding-top: 3rem; height: 150px !important; background-color: #fff !important; }
.logo-banner-content-sandbox { display: flex; white-space: nowrap; animation: scroll-sandbox 45s linear infinite; }
/* Faster animation for mobile devices */
@media (max-width:767px) {
.logo-banner-content-sandbox { background-color: white !important; animation-duration: 20s;         /* Adjust the duration as needed for mobile */ }
}
.logo-banner-content-sandbox img { height: 48px; margin: 0 40px; filter: grayscale(100%); }
@keyframes scroll-sandbox {
0% { transform: translateX(0); }
100% { transform: translateX(-100%); }
}
</style > <style > .managetext h1.fs-7 { font-size: 2.5rem;     /* Default font size */ }
.managetext p.lead { font-size: 1.5rem;     /* Default font size */ }
@media (max-width:576px) {
.managetext h1.fs-7 { font-size: 1.5rem; }
.managetext p.lead { font-size: 1rem; }
}
@media (min-width:576px) and (max-width:768px) {
.managetext h1.fs-7 { font-size: 2rem; }
.managetext p.lead { font-size: 1.25rem; }
}
@media (min-width:768px) {
.managetext h1.fs-7 { font-size: 2.5rem; }
.managetext p.lead { font-size: 1.5rem; }
}
</style >
';
?>


 
<div class="logo-banner-sandbox mb-0 bg-white">
  <div class="logo-banner-content-sandbox" id="logoBannerContentSandbox">
    <img src="/public/assets/img/logos/bw/dqmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/sonicmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/Godivamark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/KrispyKrememark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/smashburgermark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/wingstopmark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/atlantabreadmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/qdobamark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/crumblmark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/applebees-logo-black-and-white.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/chilismark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/nothingbundtcakesmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/targetmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/wingstopmark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/atlantabreadmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/qdobamark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/crumblmark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/pngaaa.com-389591.png" alt="Logo">
    <img src="/public/assets/img/logos/Baskin-Robbins+Logo_2_thmb.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/dqmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/sonicmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/Godivamark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/KrispyKrememark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/smashburgermark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/wingstopmark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/atlantabreadmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/qdobamark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/crumblmark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/applebees-logo-black-and-white.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/chilismark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/nothingbundtcakesmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/targetmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/wingstopmark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/atlantabreadmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bw/qdobamark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/bw/crumblmark.jpeg" alt="Logo">
    <img src="/public/assets/img/logos/pngaaa.com-389591.png" alt="Logo">
    <img src="/public/assets/img/logos/Baskin-Robbins+Logo_2_thmb.png" alt="Logo">
  </div>
</div>

    <script>
  // JavaScript to duplicate logos for seamless scrolling
  const logoBannerContentSandbox = document.getElementById('logoBannerContentSandbox');
  const logosSandbox = logoBannerContentSandbox.innerHTML;
  logoBannerContentSandbox.innerHTML += logosSandbox;
</script>




<!-- ============================================-->
<!-- ============================================-->
<!-- section begin ============================-->
<?PHP
if (!empty($enableadminpageeditor)) {   $admin->admineditor('body-1'); }
### ADMIN PAGE EDITOR: START-body-1 ###

echo '
<!-- Features Section -->
<section class="features-section sectionpad">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 col-lg-6 mb-5 mb-md-0">
                <h1 class="display-5 fw-bold mb-4">
                Birthday Rewards Enrollment
                </h1>
                <p class="text-muted mb-4">
                Say goodbye to hours signing up for birthday rewards. We automatically enroll you in the programs you choose. Plus, unlock a bounty of special birthday rewards from hundreds of '.$website['biznames'].'. More treats and less hassle!
                </p>
                <a href="/discover" class="text-warning text-decoration-none d-inline-flex align-items-center fw-bold">
                    Browse available rewards
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right ms-2" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </a>
            </div>
            <div class="col-md-8 col-lg-6">
                <div class="row g-4">
                    <div class="col-4">
                        <div class="card h-100 border-0 shadow-sm" data-aos="fade-up">
                            <div class="card-body">
                                <h5 class="card-title fw-bold">Easy Sign-up</h5>
                                <p class="card-text small text-muted">One profile, hundreds of rewards programs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card h-100 border-0 shadow-sm" data-aos="fade-down">
                            <div class="card-body">
                                <h5 class="card-title fw-bold">Auto Enroll</h5>
                                <p class="card-text small text-muted">We handle all the paperwork</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card h-100 border-0 shadow-sm" data-aos="fade-right">
                            <div class="card-body">
                                <h5 class="card-title fw-bold">Track Rewards</h5>
                                <p class="card-text small text-muted">Never miss a birthday treat</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center my-4">
                <a href="/signup" class="btn btn-gold btn-lg px-5">Sign-up Now</a>
</div>
            </div>
        </div>
    </div>
</section>
';
?>




<!-- Rewards Preview Section -->
<section class="py-5 sectionpad">
<div class="container">
    <div class="preview-card">
        <h2 class="text-center display-5 fw-bold mb-4">Popular Birthday Rewards</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="bg-light rounded mb-3 p-4 text-center">
                            <span class="celebration-icon zoom-on-scroll" data-delay="0">🎂</span>
                        </div>
                        <h4 class="card-title fw-bold mb-2">Free Dessert</h4>
                        <p class="card-text text-muted small">Enjoy a dessert at your favorite restaurants</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="bg-light rounded mb-3 p-4 text-center">
                            <span class="celebration-icon zoom-on-scroll" data-delay="100">☕</span>
                        </div>
                        <h4 class="card-title fw-bold mb-2">Free Drinks</h4>
                        <p class="card-text text-muted small">Get free beverages from popular coffee shops</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="bg-light rounded mb-3 p-4 text-center">
                            <span class="celebration-icon zoom-on-scroll" data-delay="200">🎁</span>
                        </div>
                        <h4 class="card-title fw-bold mb-2">Special Gifts</h4>
                        <p class="card-text text-muted small">Receive exclusive birthday gifts from retailers</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>



<?PHP
$additionalstyles.="
<!-- Add this CSS -->
<style>
.zoom-on-scroll {
    transform: scale(0.5);
    opacity: 0;
    transition: transform 0.8s ease, opacity 0.8s ease;
}

.zoom-on-scroll.active {
    transform: scale(1);
    opacity: 1;
}
</style>

";
?>
<!-- Add this JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const zoomElements = document.querySelectorAll('.zoom-on-scroll');

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const delay = entry.target.getAttribute('data-delay') || 0;
                    setTimeout(() => {
                        entry.target.classList.add('active');
                    }, delay);
                } else {
                    entry.target.classList.remove('active');
                }
            });
        },
        { threshold: 0.5 } // Trigger when 50% of the element is in view
    );

    zoomElements.forEach((el) => observer.observe(el));
});
</script>

<!-- How It Works Section -->
<section class="steps-section sectionpad">
    <div class="container">
        <h2 class="text-center display-5 fw-bold mb-5">How Birthday.Gold Works</h2>
        <div class="row g-4">
            <div class="col-md-3" data-aos="zoom-in">
                <div class="text-center">
                    <div class="step-number mx-auto">1</div>
                    <h4 class="fw-bold mb-3">Create Profile</h4>
                    <p class="text-muted">Enter your details once and we'll use them for all your reward enrollments</p>
                </div>
            </div>
            <div class="col-md-3" data-aos="zoom-in" data-aos-delay="100">
                <div class="text-center">
                    <div class="step-number mx-auto">2</div>
                    <h4 class="fw-bold mb-3">Choose Rewards</h4>
                    <p class="text-muted">Browse and select from hundreds of birthday reward programs</p>
                </div>
            </div>
            <div class="col-md-3" data-aos="zoom-in" data-aos-delay="200">
                <div class="text-center">
                    <div class="step-number mx-auto">3</div>
                    <h4 class="fw-bold mb-3">We Enroll You</h4>
                    <p class="text-muted">Our system automatically handles all the sign-ups for you</p>
                </div>
            </div>
            <div class="col-md-3" data-aos="zoom-in" data-aos-delay="300">
                <div class="text-center">
                    <div class="step-number mx-auto">4</div>
                    <h4 class="fw-bold mb-3">Enjoy Rewards</h4>
                    <p class="text-muted">Get notifications when your birthday rewards are ready to claim</p>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center mt-5">
        <a href="/signup" class="btn btn-gold btn-lg px-4">Start Collecting Rewards</a>
</div>
    </div>
</section>

<?PHP
echo '
<!-- Statistics Section -->
<section class="py-5 sectionpad" style="background:  #eee">
<div class="container text-center">
    <h2 class="mb-4 fw-bold">Our Achievements</h2>
    <p class="mb-5 text-muted">Trusted by thousands of members and businesses worldwide.</p>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="stats-box p-4 shadow rounded" style="background: #fff;">
                <div class="icon mb-3 iconxsize text-warning">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <h3 class="display-4 fw-bold text-warning count-up" data-target="' . $website['numberofbiz'] . '" data-suffix="+">0</h3>
                <p class="achieve-p">Participating Businesses</p>
                         <p class="text-muted mb-0">... and growing!  We are adding new, exciting businesses with great rewards every month.</p>
            </div>
        </div>
<div class="col-md-3">
    <div class="stats-box p-4 shadow rounded" style="background: #fff;">
        <div class="icon mb-3 iconxsize text-info">
            <i class="bi bi-people-fill"></i>
        </div>
        <h3 class="display-4 fw-bold text-info count-up" data-target="'.$app->statvalue('number_of_active_users').'">0</h3>
        <p class="achieve-p">Happy Members</p>
        <p class="text-muted mb-0">At Birthday.Gold, we strive to bring happiness back to celebrating birthdays.</p>
    </div>
</div>
        <div class="col-md-3">
            <div class="stats-box p-4 shadow rounded" style="background: #fff;">
                <div class="icon mb-3 iconxsize text-success">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h3 class="display-4 fw-bold text-success count-up" data-target="189" data-prefix="$">0</h3>
                <p class="achieve-p">Avg. Value in Rewards</p>
                   <p class="text-muted mb-0">Based on paid accounts having 30 enrollments, users receive over $189 in redeemable awards.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-box p-4 shadow rounded" style="background: #fff;">
                <div class="icon mb-3 iconxsize text-primary">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <h3 class="display-4 fw-bold text-primary count-up" data-target="4">0</h3>
                <p class="achieve-p">Hours Saved per Member</p>
                   <p class="text-muted mb-0">We save a user over 4 hours of their valuable time by filling in forms and tracking their rewards.</p>
            </div>
        </div>
    </div>
</div>
</section>
';

echo '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const counters = document.querySelectorAll(".count-up");
    const speed = 100; // Adjust speed here

    const formatNumber = (num) => {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    };

    const formatWithPrefixSuffix = (value, counter) => {
        const prefix = counter.getAttribute("data-prefix") || "";
        const suffix = counter.getAttribute("data-suffix") || "";
        return prefix + formatNumber(value) + suffix;
    };

    const countUp = (counter) => {
        const target = +counter.getAttribute("data-target");
        const current = +counter.innerText.replace(/[^0-9.-]+/g, ""); // Removes any non-numeric characters
        const increment = target / speed;

        if (current < target) {
            const newValue = Math.ceil(current + increment);
            counter.innerText = formatWithPrefixSuffix(newValue, counter);
            setTimeout(() => countUp(counter), 15);
        } else {
            counter.innerText = formatWithPrefixSuffix(target, counter);
        }
    };

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                countUp(counter);
                observer.unobserve(counter); // Stop observing after animation
            }
        });
    }, { threshold: 0.5 }); // Trigger when 50% visible

    counters.forEach(counter => {
        observer.observe(counter);
    });
});
</script>
';
?>





<!-- Testimonials Section -->
<section class="py-5 bg-light sectionpad">
    <div class="container">
        <h2 class="text-center display-5 fw-bold mb-5">What Our Members Say</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card" data-aos="flip-left">
                    <div class="mb-3">★★★★★</div>
                    <p class="mb-4">"I used to spend hours signing up for birthday rewards. Birthday.Gold makes it easy - I just picked my favorite places, they did the rest!"</p>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning" style="width: 40px; height: 40px;"></div>
                        <div class="ms-3">
                            <h6 class="fw-bold mb-0">Sarah M.</h6>
                            <small class="text-muted">Member since 2023</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card" data-aos="flip-right">
                    <div class="mb-3">★★★★★</div>
                    <p class="mb-4">"The amount of free birthday stuff I got was amazing! The notifications made sure I didn't miss any rewards. Best birthday ever!"</p>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning" style="width: 40px; height: 40px;"></div>
                        <div class="ms-3">
                            <h6 class="fw-bold mb-0">Michael R.</h6>
                            <small class="text-muted">Member since 2024</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card code" data-aos="flip-down">
                    <div class="mb-3">★★★★★</div>
                    <p class="mb-4">"Not only did I get great birthday rewards, but they also helped me track when each one expires. No more missed opportunities!"</p>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning" style="width: 40px; height: 40px;"></div>
                        <div class="ms-3">
                            <h6 class="fw-bold mb-0">Lisa K.</h6>
                            <small class="text-muted">Member since 2023</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section py-5 sectionpad">
    <div class="container">
        <h2 class="text-center display-5 fw-bold mb-5">Common Questions</h2>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Is Birthday.Gold really free?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes! Birthday.Gold is completely free to use. We make it easy to collect all your birthday rewards without any hidden fees.  If you want even more rewards and features, simply pick our paid plan.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How far in advance should I sign up?
                            </button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We recommend signing up at least 30 days before your birthday to ensure you're enrolled in time for all rewards. Some programs require advance registration to qualify.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                How do I claim my rewards?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We'll send you notifications when your rewards are available. Most rewards can be claimed by showing your email or membership card at the business location.  Don't worry - we'll provide all the details you need.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 sectionpad">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4">Ready to Start Collecting?</h2>
        <p class="lead mb-4">Join thousands of members who are maximizing their birthday celebrations.</p>
        <a href="/signup" class="btn btn-gold btn-lg px-5">Get Started For Free</a>
        <p class="text-muted mt-3">No credit card required</p>
    </div>
</section>

<?PHP
### ADMIN PAGE EDITOR: END-body-1 ###
echo '
<section class="sectionpad">
</section>
</main>
';
?>


<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
  AOS.init({
    easing: 'ease-out-back',
    duration: 1000
  });
</script>

<script>
  hljs.initHighlightingOnLoad();

  $('.hero__scroll').on('click', function(e) {
    $('html, body').animate({
      scrollTop: $(window).height()
    }, 1200);
  });
</script>

<?PHP
echo $display->addmousetracking();
  $nostickyfooter = true;
  include($dir['core_components'] . '/bg_footer.inc');
  $app->outputpage();
