<?PHP
// Set image directory 
$imgDir = '/images/site_covers/';

// Get images   
$images = scandir($_SERVER['DOCUMENT_ROOT'].'/public'.$imgDir);

// Remove . and ..
$images = array_diff($images, array('.', '..'));

// Pick random image
$randImage = $images[array_rand($images)];

// Output CSS
$css = "
.slider-bg1, .slider-bg2 {
  background-image: url(..$imgDir$randImage);
}

.birthdaygold,  .birthdaygold-white  {
  font-weight: bold !important;  
  color: gold !important;
  }
  
  .birthdaygold-white {
  color: white !important;
  }
    

.slider-height-contact {
  height: 300px;
  background-repeat: no-repeat;
  background-position: center center;
  background-size: cover
}

.hero-caption2 h3 {
  color: #fff;
  -webkit-text-stroke: 1px black;
}
  ";

#echo $css;

// Output to file
file_put_contents('stylesbg.css', $css);