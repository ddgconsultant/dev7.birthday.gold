<?PHP

$additionalstyles .= '
<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap " rel="stylesheet">
<style >
.right-panel { position: relative; height: calc(100vh - 75px); overflow: hidden; background-color: #757575 }
.right-panel .post-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: flex-start; padding: 20px; box-sizing: border-box; color: white; background: rgba(0, 0, 0, 0.2); z-index: 1; pointer-events: none }
.carousel-control-prev, .carousel-control-next { z-index: 3 }
.carousel-control-prev, .carousel-control-next { height: 50%; top: 25%; bottom: auto; z-index: 3 }
.carousel-control-prev-icon, .carousel-control-next-icon { width: 30px; height: 30px }
.right-panel .post-actions { z-index: 4 !important; pointer-events: auto; position: relative }
.right-panel .post-actions .icon-container { z-index: 5 !important; pointer-events: auto; cursor: pointer }
.right-panel .post-header, .right-panel .post-description, .right-panel .post-tags, .right-panel .post-actions .icon-container { pointer-events: auto }
.carousel-item img { width: 100%; height: 100%; object-fit: contain }
.right-panel .post-header { display: flex; align-items: center; gap: 10px }
.right-panel .post-header img { width: 50px; height: 50px; border-radius: 50% }
.right-panel .post-header .username { font-weight: bold; font-size: 1.2rem }
.right-panel .post-header .follow-icon { display: flex; align-items: center; gap: 5px; color: white }
.right-panel .follow-icon, .right-panel .icon-container { cursor: pointer }
.right-panel .follow-icon .follow-count { font-size: 0.8rem; color: white }
.right-panel .title { margin-top: 10px; font-size: 1.5rem; font-weight: bold }
.right-panel .post-description { font-size: 1rem }
.right-panel .post-tags { margin-top: 5px }
.right-panel .post-actions { display: flex; flex-direction: column; align-items: flex-start; gap: 10px; position: absolute; left: 20px; z-index: 3; pointer-events: auto }
.right-panel .post-actions .location-badge { background-color: #17a2b8; font-size: 0.9rem; padding: 5px 10px; border-radius: 10px; z-index: 5 }
.right-panel .post-actions .icon-bar { display: flex; gap: 20px; z-index: 5 }
.right-panel .post-actions .icon-container { display: flex; flex-direction: column; align-items: center; cursor: pointer; z-index: 5 }
.right-panel .post-actions .icon { font-size: 1.5rem }
.right-panel .post-actions .interaction-count { font-size: 0.7rem; color: white; margin-top: 2px; z-index: 5 }
.error-message { position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background-color: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; z-index: 9999; display: none }
.carousel-dot-indicator { position: absolute;  width: 100%; display: flex; justify-content: center; gap: 10px }
.carousel-dot-indicator .dot { width: 12px; height: 12px; background-color: #fff; border-radius: 50%; opacity: 0.5; cursor: pointer; }
.carousel-dot-indicator .dot.active { opacity: 1; width: 16px; height: 16px;   transform: translateY(-2px); }
.hidden-overlay { visibility: hidden !important; opacity: 0; pointer-events: none; transition: visibility 0s, opacity 0.3s ease }
.text-shadow { text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7) }

.image-count-badge{position:absolute;right:20px;background-color:rgba(0,0,0,0.7);color:white;border-radius:5px;font-size:1rem;z-index:10;padding:5px 10px}
.carousel-item {
transition: transform 0.6s ease-in-out;
}
</style > ';

// Sample $row['description'] with random number of slides
$slidesCount = rand(3, 14); // Random number of slides between 3 and 14
$slideDescriptions = [];
for ($i = 0; $i < $slidesCount; $i++) {
    $textMessage = $qik->generateLoremIpsum(rand(3, 30), 'words');
    $slideDescriptions[] = $textMessage;
}
$row['description'] = implode('||', $slideDescriptions);

// Split the description into individual slides based on the "||" delimiter
$slidesArray = explode('||', $row['description']);
$totalSlides = count($slidesArray); // Get the total number of slides
$dotcontent = '';

// Generate random active slide
$randomActiveSlide = rand(1, $totalSlides); 

// Determine mute button right margin based on the number of slides
$muteBtnRight = ($totalSlides >= 10) ? '100px' : '90px';  

// Shuffle the slides and randomly select how many to display
$slides = range(1, $totalSlides);
shuffle($slides);
$numSlidesToShow = rand(3, $totalSlides);
$selectedSlides = array_slice($slides, 0, $numSlidesToShow);

// Initialize carousel content
$elementcontent['text'] = '';
#$elementcontent['text'] .= 'Randomly selected ' . $numSlidesToShow . ' slides: ' . implode(', ', $selectedSlides);
$elementcontent['text'] .= '
<div id="postCarousel" class="carousel slide h-100" data-bs-ride="false">
<div class="carousel-inner h-100">
';

// Gradient background array
$gradients = [
    'linear-gradient(to right, #ff7e5f, #feb47b)',
    'linear-gradient(to right, #43cea2, #185a9d)',
    'linear-gradient(to right, #ff6a00, #ee0979)',
    'linear-gradient(to right, #00c6ff, #0072ff)',
    'linear-gradient(to right, #e1eec3, #f05053)'
];

// Loop through the selected slides and generate each carousel item
foreach ($selectedSlides as $i => $slideIndex) {
    // Make the first slide active
    $activeClass = ($i === 0) ? 'active' : '';
    
    // Random gradient background
    $randomGradient = $gradients[array_rand($gradients)];

  $elementcontent['text'] .= '
  <div class="carousel-item ' . $activeClass . ' h-100" style="background: ' . $randomGradient . '; height: 100%; position: relative; text-align: center;">
      <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 75%;">
          <p style="color: white; font-size: calc(2rem + 1.5vw); font-weight: bold; text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5); margin: 0;">
              ' . htmlspecialchars(trim($slidesArray[$slideIndex - 1])) . '
          </p>
      </div>
  </div>';

        $dotcontent .=   '
    <div class="dot ' . $activeClass . '" role="button" data-bs-target="#postCarousel" data-bs-slide-to="' . ($i) . '"></div>';
    }



$carouselslidecount = ($i + 1);

// End the carousel-inner div
$elementcontent['text'] .= '
</div>';

// Add the carousel controls (Previous and Next buttons)
$elementcontent['text'] .= '
<button class="carousel-control-prev" type="button" data-bs-target="#postCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
</button>
<button class="carousel-control-next" type="button" data-bs-target="#postCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
</button>';

// Add dot indicators and image count badge
$elementcontent['text'] .= '
<div class="carousel-dot-indicator chrome-bottom-padding-carousel post-overlay-vtoggle">' . $dotcontent . '</div>
<div class="image-count-badge px-1 chrome-bottom-padding-2 post-overlay-vtoggle monotypenumbers" id="imageCountBadge">
[1/' . $carouselslidecount . ']
</div>';

// JavaScript for carousel
$elementcontent['js-text'] = "
<script>
$(document).ready(function() {
// Initialize Bootstrap carousel
var myCarousel = document.querySelector('#postCarousel');
var carousel = new bootstrap.Carousel(myCarousel, {
    interval: false, // No auto-slide
    wrap: true
});

// Update the image count badge dynamically
const totalSlides = $('#postCarousel .carousel-item').length;
".'$(\'#imageCountBadge\').text(`[1/${totalSlides}]`);
'."
$('#postCarousel').on('slid.bs.carousel', function(e) {
    const currentIndex = $(e.relatedTarget).index() + 1;
".'    $(\'#imageCountBadge\').text(`[${currentIndex}/${totalSlides}]`);
'."    // Update the active dot indicator
    $('.carousel-dot-indicator .dot').removeClass('active');
    $('.carousel-dot-indicator .dot').eq(currentIndex - 1).addClass('active'); // Adjust for zero-indexing
});
});
</script>
";
