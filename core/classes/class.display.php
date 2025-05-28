<?php
// display.php

class Display
{
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function __construct($local_config)
  {
    // Use $config
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function selectList($options)
  {
    // display HTML select list
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public  function calendar_day($display_calendar_day)
  {
    $output = '';

    $output .= '
<div class="calendar mx-1">
<span class="calendar-month">
' . $display_calendar_day[0] . '
<div style="display: block; height: 3px;"></div>
' . $display_calendar_day[1] . '
</span>
<span class="calendar-day fs-10">' . $display_calendar_day[2] . '</span>
</div>
';

    return $output;
  }




  static function addmousetracking($options = [])
  {
    $output = '<script src="/public/js/mouse-tracks.js"></script>';
    $output .= '';

    return $output;
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function availabilitycheckjs($button = 'checkButton', $input = 'inputUsername')
  {
    echo '
<script>
$("#' . $button . '").click(function(){
var username = $("#' . $input . '").val();
$.post(\'/helper_checkavailability\', {username: username, _token: "' . $this->inputcsrf_token('tokenonly') . '"}, function(data){
if(data == "1"){
$("#availability").html("Available").css("color", "green");
}
else if(data == "2"){
$("#availability").html("Belongs to you").css("color", "orange");
}
else if (data == "0"){
$("#availability").html("Not Available").css("color", "red");
} else {
$("#availability").html("Error").css("color", "red");
}
});
});
</script>
';
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function backtotop() {
    $style = '
        <style>
            #backToTop {
                position: fixed;
                bottom: 20px;
                right: calc(((100vw - 1140px) / 2) - 200px); /* adjust based on container width */
                z-index: 99;
                display: none;
                cursor: pointer;
                font-size: 48px; /* Larger icon size */
                color: rgba(211, 211, 211, 0.9); /* Light gray color */
                transition: color 0.3s ease, bottom 0.3s ease; /* Smooth transition for position change */
            }

            #backToTop:hover {
                color: rgba(169, 169, 169, 1); /* Slightly darker on hover */
            }

            #backToTop.above-footer {
                bottom: 335px !important; /* Moves up by 200px when the footer is in view */
            }
        </style>
    ';
    $content = '
    <div id="backToTop" data-bs-toggle="tooltip" title="Scroll back to top">
        <i class="bi bi-arrow-up-circle-fill"></i>
    </div>

    <script>
        // Show or hide the back-to-top button based on scroll position
        window.onscroll = function() {
            var backToTopButton = document.getElementById("backToTop");

            // Show the button when scrolling past the viewport height
            if (document.body.scrollTop > window.innerHeight || document.documentElement.scrollTop > window.innerHeight) {
                backToTopButton.style.display = "block";
            } else {
                backToTopButton.style.display = "none";
            }
        };

        // IntersectionObserver to detect if the footer is visible
        document.addEventListener("DOMContentLoaded", function() {
            var backToTopButton = document.getElementById("backToTop");
            var footer = document.querySelector("footer");

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        backToTopButton.classList.add("above-footer");
                    } else {
                        backToTopButton.classList.remove("above-footer");
                    }
                });
            }, { threshold: 0.1 });

            observer.observe(footer);
        });

document.getElementById("backToTop").onclick = function() {
    smoothScrollToTop(300); // Adjust the duration here (e.g., 300ms for faster scroll)
};

function smoothScrollToTop(duration) {
    const startPosition = window.pageYOffset;
    const startTime = performance.now();

    function scrollStep(currentTime) {
        const timeElapsed = currentTime - startTime;
        const progress = Math.min(timeElapsed / duration, 1);
        const scrollPosition = startPosition * (1 - easeOutQuad(progress));

        window.scrollTo(0, scrollPosition);

        if (progress < 1) {
            requestAnimationFrame(scrollStep);
        }
    }

    function easeOutQuad(t) {
        return t * (2 - t); // Ease-out effect for a smooth ending
    }

    requestAnimationFrame(scrollStep);
}

    </script>
    ';

    return ['style' => $style, 'content' => $content];
}





  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function avatar($input = [])
  {
  }

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function cover($input = [])
  {
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function logo($input = [])
  {
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function generateAvatarUrl($fileuploader, $input = [], $style = 'fun')
  {
    ## global $fileuploader;
    global $database;

    switch ($style) {
      default:
        // Possible options for each attribute
        $seeds = ['Bob', 'Kiki', 'Ginger', 'Angel', 'Misty', 'Willow', 'Loki'];
        $backgroundTypes = ['solid'];
        $eyeTypes = ['cute', 'glasses', 'love', 'plain', 'shades', 'stars', 'wink', 'wink2'];
        $mouthTypes = ['cute', 'lilSmile', 'smileLol', 'smileTeeth', 'tongueOut', 'wideSmile'];
        // Define an array of background colors
        $backgroundColors = [
          '#e8b6ae', '#f0c1ad', '#eed0ba', '#cdc0d6',
          '#b8b1cf', '#959bad', '#97aeb8', '#a5bcc1', '#b2c9c9',
          '#9ac7a8', '#b8dbc9', '#c5e3d2', '#d1ebdb', '#d6e1c8',
          '#e5c28c', '#e8d097', '#edd9aa', '#f2e6bb', '#f9f3dd'
        ];
        // Select random options
        $seed = $seeds[array_rand($seeds)];
        $backgroundColor = str_replace('#', '', $backgroundColors[array_rand($backgroundColors)]);
        $backgroundType = $backgroundTypes[array_rand($backgroundTypes)];
        $eyes = $eyeTypes[array_rand($eyeTypes)];
        $mouth = $mouthTypes[array_rand($mouthTypes)];
        break;
    }

    // Construct the query string
    $queryString = "seed={$seed}&backgroundColor={$backgroundColor}&backgroundType={$backgroundType}&eyes={$eyes}&mouth={$mouth}&size=128";

    // Generate a hash from the query string
    $hash = md5($queryString);
    $fileName = "{$hash}.svg";
    $targetLocation = "public/defaultavatars/{$fileName}";

    # $targetLocation = "%{$targetLocation}%"; // Prepare the target location for the SQL LIKE query

    // Prepare the SQL query
    $sql = 'SELECT COUNT(1) cnt FROM bg_users WHERE avatar LIKE "%' . $targetLocation . '%"';
    // Execute the query
    $stmt = $database->prepare($sql);
    $stmt->execute();
    // Fetch the count
    $count = $stmt->fetchColumn();



    // Check if the file already exists
    # if (file_exists($_SERVER['DOCUMENT_ROOT']."/../../cdn.birthday.gold/{$targetLocation}")) {
    if ($count > 0) {
      // Return the URL to the existing file
      return "//files.birthday.gold/{$targetLocation}";
    } else {
      // Construct the URL to fetch the avatar
      $url = "https://api.dicebear.com/8.x/fun-emoji/svg?{$queryString}";

      // Fetch the avatar from the API
      $avatarContent = file_get_contents($url);

      // Create a temporary file to simulate file upload
      $tmpFile = tempnam(sys_get_temp_dir(), 'avatar');
      file_put_contents($tmpFile, $avatarContent);

      // Create an array to simulate the file upload
      $fileArray = [
        'name' => $fileName,
        'type' => 'image/svg+xml',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tmpFile)
      ];

      // Upload the file using $fileuploader
      $uploadResult = $fileuploader->uploadFile($fileArray, $targetLocation);

      // Clean up the temporary file
      unlink($tmpFile);

      // Check if the upload was successful
      if ($uploadResult['success']) {
        // Return the URL to the new file
        return "//files.birthday.gold/{$targetLocation}";
      } else {
        // Handle the upload failure
        session_tracking('avatarupload-failed', $uploadResult);
        return ['success' => false, 'message' => 'File upload failed.'];
      }
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function getInitialAvatarUrl($first_name, $last_name, $size = 100)
  {
    $name = urlencode($first_name . ' ' . $last_name);
    $svg_content = file_get_contents("https://ui-avatars.com/api/?name={$name}&size={$size}&background=random");
    $data_uri = 'data:image/svg+xml;base64,' . base64_encode($svg_content);
    return $data_uri;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function createInitialAvatar($first_name, $last_name, $avatar_path = '/public/avatars/')
  {
    global $website;
    $initials = strtoupper($first_name[0] . $last_name[0]);
    $avatar_filename = $_SERVER['DOCUMENT_ROOT'] . $avatar_path . $initials . '.png';
    $avatar_url = $website['fullurl'] . $avatar_path . $initials . '.png';
    // If the avatar already exists, return its path
    if (file_exists($avatar_filename)) {
      return $avatar_url;
    }

    // List of colors
    $colors = [
      '#FF5733', '#33FF57', '#3357FF', '#FF33A1', '#33FFA1', '#A133FF', '#FFA133',
      '#FF5733', '#33FF57', '#3357FF', '#FF33A1', '#33FFA1', '#A133FF', '#FFA133',
      '#FF5733', '#33FF57', '#3357FF', '#FF33A1', '#33FFA1', '#A133FF', '#FFA133'
    ];

    // Random color selection
    $color1 = $colors[array_rand($colors)];
    $color2 = $colors[array_rand($colors)];

    // Create the image
    $image = imagecreatetruecolor(100, 100);

    // Determine whether to use a solid or gradient background
    $useGradient = rand(0, 1) === 1;

    if ($useGradient) {
      // Gradient background
      $color1_rgb = sscanf($color1, "#%02x%02x%02x");
      $color2_rgb = sscanf($color2, "#%02x%02x%02x");
      for ($i = 0; $i < 100; $i++) {
        $r = (int)($color1_rgb[0] + ($color2_rgb[0] - $color1_rgb[0]) * ($i / 100));
        $g = (int)($color1_rgb[1] + ($color2_rgb[1] - $color1_rgb[1]) * ($i / 100));
        $b = (int)($color1_rgb[2] + ($color2_rgb[2] - $color1_rgb[2]) * ($i / 100));
        $line_color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $i, 100, $i, $line_color);
      }
    } else {
      // Solid background
      $bg_color = sscanf($color1, "#%02x%02x%02x");
      $background_color = imagecolorallocate($image, $bg_color[0], $bg_color[1], $bg_color[2]);
      imagefill($image, 0, 0, $background_color);
    }

    // Set the text color
    $text_color = imagecolorallocate($image, 255, 255, 255); // White text

    // Set the path to the font file
    $font_path = $_SERVER['DOCUMENT_ROOT'] . '/public/fonts/OpenSans-Regular.ttf';

    // Check if the font file exists
    if (!file_exists($font_path)) {
      throw new Exception('Font file not found: ' . $font_path);
    }


    // Calculate text bounding box for centering
    $font_size = 40;
    $bbox = imagettfbbox($font_size, 0, $font_path, $initials);
    $x = (100 - ($bbox[2] - $bbox[0])) / 2;
    $y = (100 - ($bbox[1] - $bbox[7])) / 2;
    $y += $bbox[1] - $bbox[7]; // Adjust for baseline


    // Add the initials text
    imagettftext($image, 40, 0, 20, 60, $text_color, $font_path, $initials);

    // Ensure the directory exists
    if (!is_dir(dirname($avatar_filename))) {
      mkdir(dirname($avatar_filename), 0755, true);
    }

    // Save the image to file
    if (!imagepng($image, $avatar_filename)) {
      throw new Exception('Failed to save image: ' . $avatar_filename);
    }

    imagedestroy($image);

    return  $avatar_url;
  }


  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function socialapplink($platform, $url, $outputtype='icon')
  {
      global $qik;
      
      // Get user agent details
      $browser_details = $qik->getbrowser('full', $_SERVER['HTTP_USER_AGENT']);
      $device_platform = strtolower($browser_details['platform']);
  
      // Default values
      $username = '';
      $content_id = '';
      $applink = $url; // Default to the web URL
  
      // Parse the username or content ID based on the URL format
      switch ($platform) {
          case 'twitter':
              if (preg_match('/twitter\.com\/([^\/]+)(?:\/status\/(\d+))?/', $url, $matches)) {
                  $username = $matches[1];
                  $content_id = $matches[2] ?? '';
              }
              $applink = $content_id ? "twitter://status?id=$content_id" : "twitter://user?screen_name=$username";
              break;
  
          case 'facebook':
              if (preg_match('/facebook\.com\/([^\/]+)(?:\/posts\/(\d+))?/', $url, $matches)) {
                  $username = $matches[1];
                  $content_id = $matches[2] ?? '';
              }
              $applink = $content_id ? "fb://facewebmodal/f?href=https://www.facebook.com/$username/posts/$content_id" : "fb://page/$username";
              break;
  
          case 'instagram':
              if (preg_match('/instagram\.com\/([^\/]+)(?:\/p\/([^\/]+))?/', $url, $matches)) {
                  $username = $matches[1];
                  $content_id = $matches[2] ?? '';
              }
              $applink = $content_id ? "instagram://media?id=$content_id" : "instagram://user?username=$username";
              break;
  
          case 'linkedin':
              if (preg_match('/linkedin\.com\/company\/([^\/]+)|linkedin\.com\/posts\/([^\/]+)/', $url, $matches)) {
                  $username = $matches[1] ?? '';
                  $content_id = $matches[2] ?? '';
              }
              $applink = $content_id ? "linkedin://post/$content_id" : "linkedin://company/$username";
              break;
  
          case 'tiktok':
              if (preg_match('/tiktok\.com\/@([^\/]+)(?:\/video\/(\d+))?/', $url, $matches)) {
                  $username = $matches[1];
                  $content_id = $matches[2] ?? '';
              }
              $applink = $content_id ? "tiktok://video/$content_id" : "tiktok://user?screen_name=$username";
              break;
  
          case 'youtube':
              if (preg_match('/youtube\.com\/watch\?v=([^\/]+)/', $url, $matches)) {
                  $content_id = $matches[1];
              } elseif (preg_match('/youtube\.com\/@([^\/]+)/', $url, $matches)) {
                  $username = $matches[1];
              }
              $applink = $content_id ? "vnd.youtube://watch?v=$content_id" : "youtube://www.youtube.com/@$username";
              break;
  
          case 'pinterest':
              if (preg_match('/pinterest\.com\/([^\/]+)(?:\/pin\/(\d+))?/', $url, $matches)) {
                  $username = $matches[1];
                  $content_id = $matches[2] ?? '';
              }
              $applink = $content_id ? "pinterest://pin/$content_id" : "pinterest://www.pinterest.com/$username/";
              break;
      }
  
      // Determine best link based on platform
      if (!empty($device_platform) && ($device_platform === 'android' || $device_platform === 'mac')) {
          $final_link = $applink;
      } else {
          $final_link = $url;
      }
  

      switch ($outputtype) {
          case 'icon':
            return '<a class="icon-item btn-outline-light me-1" href="' . htmlspecialchars($final_link, ENT_QUOTES, 'UTF-8') . '" target="smwindow"><i class="bi bi-' . $platform . '"></i></a>';
              break;
  
         default:
              return $final_link;
              break;
  
       
      }
   }
  



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function isappledevice($type = '')
  {
    global $session;
    $browser = $session->get('browser_detail');
    $result = false;
    $deviceMaker = strtolower($browser['device_maker'] ?? '');
    $platform = strtolower($browser['platform'] ?? '');
    $platformDescription = strtolower($browser['platform_description'] ?? '');
    $deviceName = strtolower($browser['device_name'] ?? '');
    $deviceType = strtolower($browser['device_type'] ?? '');

    // Check for various Apple device indicators
    $reason = "";

    if (strpos($deviceMaker, 'apple') !== false) {
      $reason = "Device maker is Apple";
    } elseif (strpos($platform, 'ios') !== false) {
      $reason = "Platform is iOS";
    } elseif (strpos($platformDescription, 'ipod') !== false) {
      $reason = "Platform description contains iPod";
    } elseif (strpos($platformDescription, 'iphone') !== false) {
      $reason = "Platform description contains iPhone";
    } elseif (strpos($platformDescription, 'ipad') !== false) {
      $reason = "Platform description contains iPad";
    } elseif (strpos($platformDescription, 'macos') !== false) {
      $reason = "Platform description contains macOS";
    } elseif (strpos($deviceName, 'iphone') !== false) {
      $reason = "Device name contains iPhone";
    } elseif (strpos($deviceName, 'ipad') !== false) {
      $reason = "Device name contains iPad";
    }
    // Uncomment these if you want to include them
    // elseif (strpos($deviceType, 'mobile phone') !== false) {
    //     $reason = "Device type is mobile phone";
    // } elseif (strpos($deviceType, 'tablet') !== false) {
    //     $reason = "Device type is tablet";
    // }

    if (!empty($reason)) {
      $result = true;
    }
    switch ($type) {
      case 'details':
        return array('result' => $result, 'reason' => $reason, 'details' => $browser);

      default:
        return $result;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function input_datefield($fieldnames = [], $options = [])
  {
      // Maintain exact backward compatibility for fieldnames
      if (empty($fieldnames)) {
          $fieldnames = [
              'date' => 'birthday',
              'year' => 'year',
              'month' => 'month',
              'day' => 'day'
          ];
      }
  
      // Maintain backward compatibility for options while adding Cypress data attributes
      if (!isset($options['minyears'])) $options['minyears'] = 105;
      if (!isset($options['maxyears'])) $options['maxyears'] = 0;
      if (empty($options['divclass'])) $options['divclass'] = 'form-outline';
      if (!isset($options['labelclass'])) $options['labelclass'] = 'form-label';
      if (!isset($options['birthday_label'])) {
          $options['birthday_label'] = '<label class="' . $options['labelclass'] . '"  for="' . $fieldnames['date'] . '">Birthday</label>';
      }
      if (!isset($options['nochangetag'])) {
          $options['nochangetag'] = '<span><small class="ps-2 text-light-emphasis fst-italic">DOB can\'t be changed, make sure it\'s right.</small></span>';
      }
  
      // Calculate year bounds (maintaining existing format)
      $yearMax = htmlspecialchars(date('Y') + $options['maxyears']);
      $yearMin = htmlspecialchars(date('Y') - $options['minyears']);
  
      // Maintain existing value parsing logic
      if (!empty(trim($options['value'] ?? ''))) {
          $dateParts = explode('/', $options['value']);
          $value_Y = $dateParts[0] ?? '';
          $value_M = $dateParts[1] ?? '';
          $value_D = $dateParts[2] ?? '';
      } else {
          $options['value'] = $value_Y = $value_M = $value_D = '';
      }
  
      $output = '';
      $browser_string = $this->isappledevice('details');
      
      // Apple device output (maintaining exact structure)
      if ($browser_string['result'] === true || !empty($options['forceapple'])) {
          $labels = [];
          $labellist = ['month' => 'DOB MM', 'day' => 'DD', 'year' => 'YYYY'];
          foreach ($labellist as $labelname => $value) {
              $string = '<label ';
              if ($options['labelclass'] != '') $string .= ' class="' . $options['labelclass'] . '" ';
              $string .= ' for="' . $fieldnames['date'] . '">' . $value . '</label>';
              $labels[$labelname] = $string;
          }
  
          $output = '
  <!-- Date input for iPad -->
  <div>
      <div id="iPadDOB" class="d-flex justify-content-between">
          <div class="' . $options['divclass'] . ' flex-grow-1">
              <input type="number" 
                  name="' . $fieldnames['month'] . '" 
                  id="' . $fieldnames['month'] . '" 
                  data-cy="' . $fieldnames['month'] . '-input"
                  placeholder="Month" 
                  max="12" 
                  min="1" 
                  maxlength="2" 
                  value="' . $value_M . '" 
                  class="form-control number-only" 
                  style="flex: 1;">' . 
              $labels['month'] . 
          '</div>
          <div class="' . $options['divclass'] . ' flex-grow-1">
              <input type="number" 
                  name="' . $fieldnames['day'] . '" 
                  id="' . $fieldnames['day'] . '" 
                  data-cy="' . $fieldnames['day'] . '-input"
                  placeholder="Day" 
                  max="31" 
                  min="1" 
                  maxlength="2" 
                  value="' . $value_D . '" 
                  class="form-control number-only" 
                  style="flex: 1;">' . 
              $labels['day'] . 
          '</div>
          <div class="' . $options['divclass'] . ' flex-grow-1">
              <input type="number" 
                  name="' . $fieldnames['year'] . '" 
                  id="' . $fieldnames['year'] . '" 
                  data-cy="' . $fieldnames['year'] . '-input"
                  placeholder="Year" 
                  max="' . $yearMax . '" 
                  min="' . $yearMin . '" 
                  maxlength="4" 
                  value="' . $value_Y . '" 
                  class="form-control number-only" 
                  style="flex: 1;">' . 
              $labels['year'] . 
          '</div>
          <i class="ps-3 mt-2 bi bi-apple d-none"></i>
      </div>
      ' . $options['birthday_label'] . '
      ' . $options['nochangetag'] . '
  </div>';
      }
  
      // Non-Apple device output (maintaining exact structure)
      if ($output == '') {
          $output = '
  <!-- Date input for desktop#3 -->
  <div id="xdesktopDOB" class="form-floating">
      <input class="form-control" 
          id="basic-form-dob" 
          name="birthday" 
          data-cy="' . $fieldnames['date'] . '-input"
          maxlength="10" 
          type="date" 
          value="' . $options['value'] . '">
      <label class="form-label" for="basic-form-dob">Date of Birth</label>
  </div>
  <span class="ms-2 mt-0 pt-0">
      <small class="mt-0 pt-0 text-light-emphasis fst-italic">
          <span class="d-none d-md-inline">Birthday</span>
          <span class="d-md-none d-sm-inline">DOB</span> 
          can\'t be changed, make sure it\'s right.
      </small>
  </span>';
      }
  
      return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public  function input_datefield_PREVIOUS($fieldnames = [], $options = [])
  {

    if (!isset($options['minyears'])) $options['minyears'] = 105;
    if (!isset($options['maxyears'])) $options['maxyears'] = 0;
    $yearMax = htmlspecialchars(date('Y') + $options['maxyears']);
    $yearMin = htmlspecialchars(date('Y') - $options['minyears']);

    global $session;
    $output = '';
    $browser = $session->get('browser_detail');
    #breakpoint($browser);

    ## -- set defaults
    if (empty($fieldnames)) $fieldnames = ['date' => 'birthday', 'year' => 'year', 'month' => 'month', 'day' => 'day'];
    if (empty($options['divclass'])) $options['divclass'] = 'form-outline';
    if (!isset($options['labelclass'])) $options['labelclass'] = 'form-label';
    if (!isset($options['birthday_label'])) $options['birthday_label'] = '<label class="' . $options['labelclass'] . '"  for="' . $fieldnames['date'] . '">Birthday</label>';
    if (!isset($options['nochangetag'])) $options['nochangetag'] = '<span><small class="ps-2 text-light-emphasis fst-italic">DOB can\'t be changed, make sure it\'s right.</small></span>';
    if (!empty(trim($options['value'] ?? ''))) {
      $dateParts = explode('/', $options['value']);
      $value_Y = $dateParts[0] ?? '';
      $value_M = $dateParts[1] ?? '';
      $value_D = $dateParts[2] ?? '';
  } else {
      $options['value'] = $value_Y = $value_M = $value_D = '';
  }
  

    ## determine device
    $browser_string = $this->isappledevice('details');
    if ($browser_string['result'] === true || !empty($options['forceapple'])) {

      $labels = [];
      $labellist = ['month' => 'DOB MM', 'day' => 'DD', 'year' => 'YYYY'];
      foreach ($labellist as  $labelname => $value) {
        $string = '<label ';
        if ($options['labelclass'] != '') $string .= ' class="' . $options['labelclass'] . '" ';
        $string .= ' for="' . $fieldnames['date'] . '">' . $value . '</label>';
        $labels[$labelname] = $string;
      }

      $output = '
  <!-- Date input for iPad -->
  <div>
  <div id="iPadDOB"  class="d-flex justify-content-between">
    <div class="' . $options['divclass'] . ' flex-grow-1"><input type="number" name="' . $fieldnames['month'] . '" id="' . $fieldnames['month'] . '" placeholder="Month" max="12" min="1" maxlength=2 value="' . $value_M . '" class="form-control  number-only" style="flex: 1;">' . $labels['month'] . '</div>
    <div class="' . $options['divclass'] . ' flex-grow-1"><input type="number" name="' . $fieldnames['day'] . '" id="' . $fieldnames['day'] . '" placeholder="Day" max="31" min="1" maxlength=2 value="' . $value_D . '" class="form-control  number-only" style="flex: 1;">' . $labels['day'] . '</div>
    <div class="' . $options['divclass'] . ' flex-grow-1"><input type="number" name="' . $fieldnames['year'] . '" id="' . $fieldnames['year'] . '" placeholder="Year" max="' . $yearMax . '" min="' . $yearMin . '" maxlength=4 value="' . $value_Y . '" class="form-control number-only" style="flex: 1;">' . $labels['year'] . '</div>
      <i class="ps-3 mt-2 bi bi-apple d-none"></i>
      </div>
      ';
    
      $output .= '
         ' . $options['birthday_label'] . '
         ' . $options['nochangetag'] . '
      </div>

  ';
    }


    if ($output == '') {

      $output = '
<!-- Date input for desktop -->
<div id="desktopDOB" class="' . $options['divformtypeclass'] . '">
    <input type="date" name="' . $fieldnames['date'] . '" id="' . $fieldnames['date'] . '" value="' . $options['value'] . '" class="form-control"  placeholder="Birthdate" aria-label="Birthdate">
    ';
      if (!empty($options['birthday_label'])) {
        $output .= '<label ';
        if ($options['labelclass'] != '') $output .= ' class="' . $options['labelclass'] . '" ';
        $output .= ' for="' . $fieldnames['date'] . '">' . $options['birthday_label'] . '</label>';
      }
      $output .= '   <i class="ps-3 mt-2 bi bi-microsoft d-none"></i>
</div>
' . $options['nochangetag'] . '


<script>
document.addEventListener("DOMContentLoaded", function() { 
  var birthdayInput = document.getElementById("birthday");

  birthdayInput.addEventListener("input", function() {
      this.value = this.value.replace(/[^0-9/]/g, ""); // Remove invalid characters
      if (this.value.length > 10) {
        this.value = this.value.slice(0, 10); // Truncate to 10 characters
        // Optionally display an error message
    }
      // Basic format validation (MM/DD/YYYY)
      if (this.value.match(/^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/\d{4}$/)) {
          // Valid format 
      } else {
          // Invalid format- you might want to alert the user here   
      }
  });
});

</script>
';

      $output = '
<!-- Date input for desktop#2 -->
<!--
<div id="desktopDOB" class="form-floating">
    <input type="date" name="birthday" id="birthday" value="" class="form-control"  placeholder="Birthdate" aria-label="Birthdate">
    <label  for="birthday">Birthday</label>   <i class="ps-3 mt-2 bi bi-microsoft d-none"></i>
</div>
-->
<div id="XdesktopDOB" class="Xform-floating">
<link href="/public/assets/vendors/flatpickr/flatpickr.min.css" rel="stylesheet" >

<label for="birthday">Birthday</label>
<input class="form-control datetimepicker" id="birthday" name="birthday"  type="text"  maxlength="10" placeholder="dd/mm/yy" data-options=\'{"disableMobile":true}\' /
</div>
<span class="ms-2 mt-0 pt-0 "><small class="mt-0 pt-0 text-light-emphasis fst-italic"><span class="d-none d-md-inline">Birthday</span><span class="d-md-none d-sm-inline">DOB</span> can\'t be changed, make sure it\'s right.</small></span>
';
      $output = '
<!-- Date input for desktop#3 -->
<div id="xdesktopDOB" class="form-floating">
<input class="form-control" id="basic-form-dob" name="birthday" maxlength="10" type="date" >
<label class="form-label" for="basic-form-dob">Date of Birth</label>
</div>
<span class="ms-2 mt-0 pt-0 "><small class="mt-0 pt-0 text-light-emphasis fst-italic"><span class="d-none d-md-inline">Birthday</span><span class="d-md-none d-sm-inline">DOB</span> can\'t be changed, make sure it\'s right.</small></span>
';
    }
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function tooltip($input = '')
  {

    if (strpos($input, '-js-') !== false) {
      $output = '
<!-- Initialize Tooltip -->
';
      if ($input == '-js-') {
        $output .= '
<script>
';
      }
      $output .= '

function initializeTooltips() {
var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-toggle="tooltip"]\'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
return new bootstrap.Tooltip(tooltipTriggerEl);
});
}

$(document).ready(function() {
// Call it the first time
initializeTooltips();
});
';
      if ($input == '-js-') {
        $output .= '
</script>
';
      }
    } else $output = ' data-toggle="tooltip" data-placement="top" title="' . $input . '"';

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function togglepaswordjs()
  {
    $output = '
<script>
document.addEventListener("DOMContentLoaded", function() {
const togglePassword = document.querySelector(".toggle-password");
togglePassword.addEventListener("click", function(e) {
// Toggle the type attribute
const passwordInput = document.querySelector(e.target.getAttribute("toggle"));
if (passwordInput.type === "password") {
passwordInput.type = "text";
togglePassword.classList.remove("bi-eye-fill"); // Remove the eye-closed icon
togglePassword.classList.add("bi-eye-slash-fill"); // Add the eye-open icon
} else {
passwordInput.type = "password";
togglePassword.classList.remove("bi-eye-slash-fill"); // Remove the eye-open icon
togglePassword.classList.add("bi-eye-fill"); // Add the eye-closed icon
}
});
});
</script>';
    return $output;
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function submitbuttoncolorjs($formid = 'mainform', $buttonid = 'mainsubmit')
  {
    $output = '
<script>
// form submit button color change
document.addEventListener("DOMContentLoaded", function () {
const form = document.getElementById("' . $formid . '");
const inputs = form.querySelectorAll("input[type=\'text\'], input[type=\'password\'], input[type=\'date\'], input[type=\'email\'], input[type=\'checkbox\']");
const loginButton = document.getElementById("' . $buttonid . '");

function toggleButtonState() {
let allFilled = true;
inputs.forEach(input => {
if ((input.type === "checkbox" && !input.checked) || (input.type !== "checkbox" && input.value.trim() === "")) {
allFilled = false;
}
});

if (allFilled) {
loginButton.classList.remove("btn-primary");
loginButton.classList.add("btn-success");
} else {
loginButton.classList.remove("btn-success");
loginButton.classList.add("btn-primary");
}
}

inputs.forEach(input => {
input.addEventListener("input", toggleButtonState);
});
});
</script>
';
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function randomwords($word_set, $format = '')
  {
    $set = $word_set;

    if (is_array($word_set)) {
      $wordcategories['custom'] = $word_set;
    }
    $wordcategories = ['action' => ["easier", "faster", "better", "fun'ner", "free'er"]];
    $randomKey = array_rand($wordcategories[$set]);
    $randomWord = $wordcategories[$set][$randomKey];

    switch (strtolower($format)) {
      case 'upper':
        $randomWord = strtoupper($randomWord);
        break;
      case 'lower':
        $randomWord = strtolower($randomWord);
        break;
      case 'ucwords':
        $randomWord = ucwords($randomWord);
        break;
    }

    return $randomWord;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function formatdate($date, $format = 'M j, Y')
  {
    if (!$date) {
      return null;
    }

    try {
      $datetime = new DateTime($date);
      return $datetime->format($format);
    } catch (Exception $e) {
      return null;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function applink($apptype, $row)
  {
    $output = [];
    global $website;
    $output['header'] = 'App: <a href="#" id="showlinks"><i class="bi bi-link-45deg text-black"></i></a> / 
<a href="#" id="showqrcodes"><i class="bi bi-qr-code-scan text-black"></i></a>';
    $output['applink'] =$output['url'] = $output['applink_iphone'] = $output['applink_google'] = $output['applink_default'] = $output['qrlink'] = false;
    switch ($apptype) {
      case 'iphone':
        $output['url'] = $row['appapple']??'';
        $output['applink'] = $output['applink_iphone'] = '<a class="applink" href="' . $output['url'] . '" target="_downloadapp"><img src="/public/images/icon/applestoreicon.png" height="40"></a>';
        break;
      case 'android':
        $output['url'] = $row['appgoogle']??'';
        $output['applink'] = $output['applink_google'] = '<a class="applink" href="' . $output['url'] . '" target="_downloadapp"><img src="/public/images/icon/googleappicon.png" height="40"></a>';
        break;
      default:
        $output['applink'] = $output['applink_default'] = '<a href="/myaccount/profile"Profile Data missing</a>';
        #  default: $appicon='<a href="'.$row['appgoogle'].'"><img src="/public/images/icon/googleappicon.png" height="40"></a>'; break;
    }
    if (!isset($output['url']) || empty($output['url'])) $output['url'] = '';

    if ($output['url'] == '') $output['qrlink'] = '';
    else {
      $output['qrlink'] = '<img class="qrlink d-none" style="width:40px" src="' . $website['fullurl'] . '/qr?i=' . urlencode($output['url']) . '">';
      $output['qrlink_url'] = '' . $website['fullurl'] . '/qr?i=' . urlencode($output['url']) . '';
    }
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function inputcsrf_token($input = '')
  {
    global $csrf_token;
    switch ($input) {
      case 'tokenonly':
        return $csrf_token;
      default:
        return '<input type="hidden" name="_token" value="' . $csrf_token . '">';
    }
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function input_csrftoken($input = '')
  {
    global $csrf_token;
    switch ($input) {
      case 'tokenonly':
        return $csrf_token;
      default:
        return '<input type="hidden" name="_token" value="' . $csrf_token . '">';
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function pagename_prev($input = '')
  {
    $path = $_SERVER['PHP_SELF']; // Get the current script path
    $filename = basename($path, '.php'); // Extract the filename without the .php extension

    // Check if the filename is 'index', which is common for directory default pages
    if ($filename === 'index') {
      $dirPath = dirname($path); // Get the directory path
      $dirName = basename($dirPath); // Extract the last part of the directory path as the directory name
      $output = strtoupper($dirName); // Use the directory name, capitalized, as the output
    } else {
      $output = ucfirst($filename); // If not 'index', use the filename, capitalized, as the output
    }

    if ($output !== '') {
      $output =  $input . ' - ' . $output; // Prepend ' - ' to the output if it's not empty
    }

    return $output; // Return the formatted output
  }

  

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function pagename($input = '')
  {
     if ($input) {
         return $input;
     }
  
     $path = $_SERVER['PHP_SELF'];
     $filename = basename($path, '.php');
     
     if ($filename === 'index') {
         $dirPath = dirname($path); 
         $dirName = basename($dirPath);
         return strtoupper($dirName);
     }
     
     // Check if page is in /blog/ folder
     if (strpos($path, '/blog/') !== false) {
         // Convert hyphens to spaces and title case for blog pages
         $blogTitle = str_replace('-', ' ', $filename);
         $blogTitle = ucwords($blogTitle);
         return 'birthday.gold '.$blogTitle;
     }
     
     // Convert hyphens to spaces and title case for all other pages
     $pageTitle = str_replace('-', ' ', $filename);
     $pageTitle = ucwords($pageTitle);
     return 'birthday.gold - '.$pageTitle;
  }

  

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function companyimage($input = '', $cdnstatus = true)
  {
    return '//cdn.birthday.gold/public/images/company_images/' . $input . '';
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function cdn($input = '', $type = 'images', $cdnstatus = true)
  {
    if ($cdnstatus)
      return '//cdn.birthday.gold/public/' . $type . '/' . $input . '';
    else
      return '/public/' . $type . '/' . $input . '';
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function format($data)
  {
    // format data for display
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public static function socialmedialink($type, $input, $decoration = '')
  {
    $output = '';
    $networks = ['facebook' => 'facebook', 'twitter' => 'twitter-x', 'instagram' => 'instagram', 'tiktok' => 'tiktok'];
    $found = false;

    foreach ($networks as $network => $icon) {
      if (isset($input[$network]) && $input[$network] != "") {
        switch ($type) {
          case 'li-a':
            $output .= '<li class="social-media-icon"><a href="' . $input[$network] . '" target="socialmedia"><i class="bi bi-' . $icon . '"></i></a></li>' . "\n";
            break;
          case 'index-company':
            $output .= '<a class="btn btn-square btn-outline-primary border-2 m-1 social-media-icon" href="' . $input[$network] . '"><i class="bi bi-' . $icon . '"></i></a>' . "\n";
            $found = true;
            break;
        }
      }
    }

    if (!$found && $decoration == 'blank_spacer') {
      $output .= '<span class="social-media-spacer"></span>';
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function starrating($rating, $type = 'basic')
  {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    $output = '';
    for ($i = 0; $i < $fullStars; $i++) {
      $output .= '<i class="bi bi-star-fill text-warning"></i>';
    }

    if ($halfStar) {
      $output .= '<i class="bi bi-star-half text-warning"></i>';
    }

    for ($i = 0; $i < $emptyStars; $i++) {
      $output .= '<i class="bi bi-star text-warning"></i>';
    }
    if (strpos($type, 'tooltip') !== false) {
      list($junk, $numberofreviews) = explode('|', $type);
      $output = '<span  data-bs-toggle="tooltip" data-bs-placement="top" title="' . $rating . ' - ' . $numberofreviews . ' reviews">' . $output . '</span>';
    }
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function formaterrormessage0($errormessage = '', $messgeid = '', $version = '5.3', $centerText = false)
{
    if ($errormessage == '') return '';

    // Determine the icon based on the background class (if any)
    $icon = '';
    if (strpos($errormessage, 'alert-danger') !== false) {
        $icon = '<i class="bi bi-exclamation-triangle-fill"></i> '; // Example icon for danger alerts
    } elseif (strpos($errormessage, 'alert-warning') !== false) {
        $icon = '<i class="bi bi-exclamation-circle-fill"></i> '; // Example icon for warning alerts
    } elseif (strpos($errormessage, 'alert-success') !== false) {
        $icon = '<i class="bi bi-check-circle-fill"></i> '; // Example icon for success alerts
    } elseif (strpos($errormessage, 'alert-info') !== false) {
        $icon = '<i class="bi bi-info-circle-fill"></i> '; // Example icon for info alerts
    } else {
        $icon = '<i class="bi bi-exclamation-triangle-fill"></i> '; // Default icon for unknown alert type
    }

    // Determine text alignment class
    $textAlignment = $centerText ? ' text-center' : ' text-left';

    // Check if the message already contains an alert class; if not, wrap it
    if (strpos($errormessage, 'alert') === false) {
        $errormessage = '<div class="alert alert-danger' . $textAlignment . '">' . $icon . $errormessage . '</div>';
    } else {
        // Prepend the icon and add text alignment if an alert class already exists
        $errormessage = preg_replace('/(<div class="alert[^>]*>)/', '$1' . $icon . $textAlignment, $errormessage, 1);
    }

    $search = array();
    $replace = array();

    switch ($version) {
        case '5.0':
            if (strpos($errormessage, 'dismissible') === false) {  // Handle reduced-sized alert messages
                $search[] = '<div class="alert';
                $replace[] = '<div role="alert" class="alert-dismissible fade show alert';
            }

            if (strpos($errormessage, 'alert') !== false && strpos($errormessage, '<button') === false) {  // Handle added close alert button
                $search[] = '</div>';
                $replace[] = '<button type="button" class="btn-close-custom" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button></div>';
            }
            break;

        default:
            if (strpos($errormessage, 'dismissible') === false) {  // Handle reduced-sized alert messages
                $search[] = '<div class="alert';
                $replace[] = '<div role="alert" class="alert-dismissible fade show alert';
            }

            if (strpos($errormessage, 'alert') !== false && strpos($errormessage, '<button') === false) {  // Handle added close alert button
                $search[] = '</div>';

                if (empty($messgeid)) {
                    $replace[] = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                } else {
                    global $qik, $session;
                    list($messagetype, $message_id) = explode(':', $messgeid);
                    $replace[] = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" data-message-id="' . $messagetype . ':' . $qik->encodeId($message_id) . '"></button></div>';
                    $session->set('footerjs_dismiss_alert', 'true');
                }
            }

            $errormessage = str_replace($search, $replace, $errormessage);
            break;
    }

    $errormessage = str_replace($search, $replace, $errormessage);

    return $errormessage;
}

  

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function formaterrormessage($errormessage = '', $messgeid = '', $version = '5.3', $centerText = false)
{
    if ($errormessage == '') return '';

    // Determine the icon based on the background class (if any)
    $icon = ''; // Default empty icon

    if (is_string($errormessage)) {
        // Determine the icon based on the background class (if any)
        if (strpos($errormessage, 'alert-danger') !== false) {
            $icon = '<i data-tracker-tag="displayformaterrormessageiconET1" class="bi bi-exclamation-triangle-fill"></i> '; // Icon for danger alerts
        } elseif (strpos($errormessage, 'alert-warning') !== false) {
            $icon = '<i data-tracker-tag="displayformaterrormessageiconEC" class="bi bi-exclamation-circle-fill"></i> '; // Icon for warning alerts
        } elseif (strpos($errormessage, 'alert-success') !== false) {
            $icon = '<i data-tracker-tag="displayformaterrormessageiconCC" class="bi bi-check-circle-fill"></i> '; // Icon for success alerts
        } elseif (strpos($errormessage, 'alert-info') !== false) {
            $icon = '<i data-tracker-tag="displayformaterrormessageiconiC" class="bi bi-info-circle-fill"></i> '; // Icon for info alerts
        } else {
            $icon = '<i data-tracker-tag="displayformaterrormessageiconET" class="bi bi-exclamation-triangle-fill"></i> '; // Default icon for unknown alert type
        }
    } else {
        // Optionally handle the case where $errormessage is not a string
        $icon = '<i data-tracker-tag="displayformaterrormessageiconET" class="bi bi-exclamation-triangle-fill"></i> '; // Default icon for non-string messages
    }
    

    // Determine text alignment class
    $textAlignment = $centerText ? ' text-center' : ' text-left';

    // Check if the message already contains an alert class; if not, wrap it
// Ensure $errormessage is a string
if (is_array($errormessage)) {
  // Optionally handle the array case or convert it to a string
  $errormessage = implode(' ', $errormessage); // Convert array to string
}

// Check if the message already contains an alert class; if not, wrap it
if (strpos($errormessage, 'alert') === false) {
  $errormessage = '<div data-tracker-tag="displayformaterrormessagediv" class="alert alert-danger' . $textAlignment . '">' . $icon . $errormessage . '</div>';
} else {
  // Ensure the text alignment class is added correctly without affecting the message content
  if (strpos($errormessage, $textAlignment) === false) {
      $errormessage = str_replace('alert ', 'alert ' . $textAlignment . ' ', $errormessage);
  }
  // Prepend the icon
  if (strpos($errormessage, $icon) === false) {
      $errormessage = preg_replace('/(<div class="alert[^>]*>)/', '$1' . $icon, $errormessage, 1);
  }
}


    $search = array();
    $replace = array();

    switch ($version) {
      case '5.3':
        // If dismissible is not set, make the alert dismissible
        if (strpos($errormessage, 'dismissible') === false) {  
            $search[] = '<div class="alert';
            $replace[] = '<div data-tracker-tag="displayformaterrormessage53" role="alert" class="alert-dismissible fade show alert';
        }

        // Add the close button if not already present
        if (strpos($errormessage, 'alert') !== false && strpos($errormessage, '<button') === false) {
            $search[] = '</div>';

            if (empty($messgeid)) {
                $replace[] = '<button  data-tracker-tag="displayformaterrorbutton53" type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            } else {
                global $qik, $session;
                list($messagetype, $message_id) = explode(':', $messgeid);
                $replace[] = '<button  data-tracker-tag="displayformaterrorbutton53a"  type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" data-message-id="' . $messagetype . ':' . $qik->encodeId($message_id) . '"></button></div>';
                $session->set('footerjs_dismiss_alert', 'true');
            }
        }
        break;
        case '5.0':
            if (strpos($errormessage, 'dismissible') === false) {  // Handle reduced-sized alert messages
                $search[] = '<div class="alert';
                $replace[] = '<div role="alert" class="alert-dismissible fade show alert';
            }

            if (strpos($errormessage, 'alert') !== false && strpos($errormessage, '<button') === false) {  // Handle added close alert button
                $search[] = '</div>';
                $replace[] = '<button  data-tracker-tag="displayformaterrorbutton50"  type="button" class="btn-close-custom" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button></div>';
            }
            break;

        default:
            if (strpos($errormessage, 'dismissible') === false) {  // Handle reduced-sized alert messages
                $search[] = '<div class="alert';
                $replace[] = '<div role="alert" class="alert-dismissible fade show alert';
            }

            if (strpos($errormessage, 'alert') !== false && strpos($errormessage, '<button') === false) {  // Handle added close alert button
                $search[] = '</div>';

                if (empty($messgeid)) {
                    $replace[] = '<button   data-tracker-tag="displayformaterrorbuttonD" type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                } else {
                    global $qik, $session;
                    list($messagetype, $message_id) = explode(':', $messgeid);
                    $replace[] = '<button   data-tracker-tag="displayformaterrorbuttonDa" type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" data-message-id="' . $messagetype . ':' . $qik->encodeId($message_id) . '"></button></div>';
                    $session->set('footerjs_dismiss_alert', 'true');
                }
            }

            $errormessage = str_replace($search, $replace, $errormessage);
            break;
    }


    return $errormessage;
}


public function enrollerextensiondownload($options=[]){
global $website;
// Path to your manifest.json file
$manifestPath = 'https://dev.birthday.gold/admin/bgreb_v3/chrome_extension/'.$website['bge_extensionversion'].'/manifest.json';

  // Read the file contents
  $manifestContents = file_get_contents($manifestPath);
  
  // Decode the JSON data
  $manifestData = json_decode($manifestContents, true);
  
  // Get the version
  $version = $manifestData['version'];
  
 $button='
  <a href="https://dev.birthday.gold/admin/bgreb_v3/produce-download?zip=1" class="btn btn-primary">Download Chrome Extension: v.' . $version . '</a>
  ';
  return $button;
}


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
function formaterrormessagex($errormessage = '', $messgeid = '', $version = '5.3', $centerText = false)
{
    if ($errormessage == '') return '';
    // Determine icon based on alert type
    $icons = [
        'alert-danger' => 'bi-exclamation-triangle-fill',
        'alert-warning' => 'bi-exclamation-circle-fill',
        'alert-success' => 'bi-check-circle-fill',
        'alert-info' => 'bi-info-circle-fill',
        'default' => 'bi-exclamation-triangle-fill'
    ];

    $icon = '';
    foreach ($icons as $class => $iconClass) {
        if (strpos($errormessage, $class) !== false) {
            $icon = '<i class="bi ' . $iconClass . '"></i> ';
            break;
        }
    }
    if ($icon == '') {
        $icon = '<i class="bi ' . $icons['default'] . '"></i> ';
    }

    // Set text alignment class
    $textAlignment = $centerText ? ' text-center' : ' text-left';

    // Wrap message with alert if not already an alert
    if (strpos($errormessage, 'alert') === false) {
        $errormessage = '<div class="alert alert-danger' . $textAlignment . ' alert-dismissible  fade show " id="'.rand().'">' . $icon . $errormessage . '</div>';
    } else {
        // Ensure text alignment and prepend icon
        if (strpos($errormessage, $textAlignment) === false) {
            $errormessage = str_replace('alert ', 'alert ' . $textAlignment . ' ', $errormessage);
        }
        if (strpos($errormessage, $icon) === false) {
            $errormessage = preg_replace('/(<div class="alert[^>]*>)/', '$1' . $icon, $errormessage, 1);
        }
    }

    // Handle Bootstrap version-specific behavior
    $replaceButton = function($btnClass, $message_id = '') use ($messgeid) {
        global $qik, $session;
        $session->unset('footerjs_dismiss_alert');
        $btn = '<button type="button" class="' . $btnClass . '" data-bs-dismiss="alert" aria-label="Close">';
        if (!empty($message_id)) {
            list($type, $id) = explode(':', $messgeid);
            $btn .= ' data-message-id="' . $type . ':' . $qik->encodeId($id) . '"';
          #  $session->set('footerjs_dismiss_alert', 'true');
        }
        $btn .= '</button>';
        return $btn;
    };

    if (strpos($errormessage, 'dismissible') === false) {
        $errormessage = str_replace('<div class="alert', '<div  id="'.rand().'" class="alert alert-dismissible fade show ', $errormessage);
    }

    if (strpos($errormessage, '<button') === false) {
        switch ($version) {
            case '5.3':
                $errormessage = str_replace('</div>', $replaceButton('btn-close') . '</div>', $errormessage);
                break;
            case '5.0':
                $errormessage = str_replace('</div>', $replaceButton('btn-close-custom') . '<i class="bi bi-x"></i></button></div>', $errormessage);
                break;
            default:
                $errormessage = str_replace('</div>', $replaceButton('btn-close') . '</div>', $errormessage);
                break;
        }
    }
#breakpoint($errormessage);
    return $errormessage;
}


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getMessage($type = 'loginfailure')
  {
    $messages = [
      'loginfailure' => [
        "Oops! You've entered the twilight zone of wrong passwords.",
        "Nice try, but that's not the magic word.",
        "Password incorrect. Would you like to play a game of 'Guess the Password'?",
        "Access denied. You shall not pass!",
        "Wrong credentials. Do you need a hint?",
        "Incorrect. Are you sure you're you?",
        "Login failed. You didn't say the magic word.",
        "Wrong password. Maybe caps lock is your enemy?",
        "Login failed. Did you forget your password or your identity?",
        "Incorrect. You might want to hit the 'Forgot Password' this time."
      ],
      // Add more types and their messages here
    ];

    if (isset($messages[$type])) {
      // Randomly select a message from the array
      $index = array_rand($messages[$type]);
      return $messages[$type][$index];
    } else {
      return "Unknown error type.";
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function formataddress($type = 'long', $input = null)
  {
    if (empty($input)) {
      global $session;
      $current_user_data = $session->get('current_user_data');
      $output = str_replace('|,', '', '|' . $current_user_data['mailing_address'] . ', |' . $current_user_data['city'] . ', ' . $current_user_data['state'] . '  ' . $current_user_data['zip_code']);
      return $output;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_staffmembers($selected = '', $type = 'changeoptions')
  {
    global $database, $qik;

    $sql = "SELECT distinct u.user_id, u.first_name, u.last_name, u.username
FROM bg_users u
JOIN bg_user_attributes a ON u.user_id = a.user_id
WHERE a.type = 'staff' AND a.status = 'A' AND u.status = 'active'
ORDER BY u.last_name";

    $stmt = $database->prepare($sql);
    $stmt->execute();
    $staffMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    switch ($type) {
      case 'changeoptions':
        $output = ' <option value="">Select One</option>
<option value="" disabled>──────────────────────────</option>
<option value="-nochange-">[ No Change ]</option>
<option value="-remove-">[ Remove ]</option>    
<option value="" disabled>──────────────────────────</option>
';

        break;
      default:
        $output = '';
    }


    foreach ($staffMembers as $staff) {
      $isSelected = ($staff['user_id'] == $selected) ? 'selected="selected"' : '';
      $output .= '<option value="' . $qik->encodeId($staff['user_id']) . '" ' . $isSelected . '>' . htmlspecialchars($staff['last_name']) . ', ' . htmlspecialchars($staff['first_name']) . ' (' . htmlspecialchars($staff['username']) . ')</option>' . "\n";
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_users($selected = '', $type = 'changeoptions')
  {
    global $database, $qik;

    $sql = "SELECT distinct u.user_id, u.first_name, u.last_name, u.username
FROM bg_users u
WHERE  u.status = 'active'
ORDER BY u.last_name";

    $stmt = $database->prepare($sql);
    $stmt->execute();
    $staffMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);


    switch ($type) {
      case 'changeoptions':
        $output = ' <option value="">Select One</option>
<option value="" disabled>──────────────────────────</option>
<option value="-nochange-">[ No Change ]</option>
<option value="-remove-">[ Remove ]</option>    
<option value="" disabled>──────────────────────────</option>
';

        break;
      default:
        $output = '';
    }


    foreach ($staffMembers as $staff) {
      $isSelected = ($staff['user_id'] == $selected) ? 'selected="selected"' : '';
      $output .= '<option value="' . $qik->encodeId($staff['user_id']) . '" ' . $isSelected . '>' . htmlspecialchars($staff['last_name']) . ', ' . htmlspecialchars($staff['first_name']) . ' (' . htmlspecialchars($staff['username']) . ')</option>' . "\n";
    }

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_resultsize($selected = '10')
  {
    $output = '
<option value="25">25</option>
<option value="50">50</option>
<option value="100">100</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_sortorder($selected = 'name')
  {
    $output = '
<option value="popularity">Popularity</option>
<option value="rating">Rating</option>
<option value="name">Name</option>
<option value="value">Value</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_title($selected = '')
  {
    $output = '
<option   value="">(Pick One)</option>
<option value="Mr.">Mr.</option>
<option value="Ms.">Ms.</option>
<option value="Mrs.">Mrs.</option>
<option value="Miss">Miss</option>
<option value="Dr.">Dr.</option>
<option value="Prof.">Prof.</option>
<option value="Rev.">Rev.</option>  
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_gender($selected = '')
  {
    $output = '
<option value="">(Pick One)</option>
<option value="male">Male</option>
<option value="female">Female</option>
<option value="n/a">Prefer Not To Say</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_phonetype($selected = '')
  {
    $output = '
<option value="">(Pick One)</option>
<option value="android">Android</option>
<option value="iphone">iPhone</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_displaylength($selected = '')
  {
    $output = '
<option value="7">7 Days</option>
<option value="30">30 Days</option>
<option value="180">6 Months</option>
<option value="365">1 Year</option>
<option value="all">All</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_accountstatus($selected = '')
  {
    $output = '
<option value="">(Pick One)</option>
<option value="active">Active</option>
<option value="giftlock">Gift Lock</option>
<option value="pending">Pending</option>
<option value="validated">Validated</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_accounttype($selected = '')
  {
    $output = '
<option value="">(Pick One)</option>
<option value="user">User</option>
<option value="parental">Parental</option>
<option value="minor">Minor</option>
<option value="giftcertificate">Gift Certificate</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_accountplan($selected = '')
  {
    $output = '
<option value="">(Pick One)</option>    
<option value="free">Free</option>
<option value="gold">Gold</option>
<option value="life">Life</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_accountadmin($selected = '')
  {
    $output = '
<option value="N">None</option>
<option value="admin">Admin</option>
<option value="superadmin">Super Admin</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }


##--------------------------------------------------------------------------------------------------------------------------------------------------
function list_relationships($selected = '')
{
    $output = '
    <option value="">Select One</option>
<option value="friend">Friend</option>
<option value="family">Family</option>
<option value="coworker">Coworker</option>
<option value="other">Other</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected" value="' . $selected . '"', $output);
    return $output;
}


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_consultant_roles($selected = '')
  {
    $output = '
<option value="N">None</option>
<option value="associate">Associate</option>
<option value="senior">Senior</option>
';
    $output = str_replace('value="' . $selected . '"', 'selected="selected"  value="' . $selected . '"', $output);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function list_state($selected = '')
  {
    $output = '
<option value="">(State) Not specified</option>
<option value="Alabama">Alabama</option>
<option value="Alaska">Alaska</option>
<option value="Arizona">Arizona</option>
<option value="Arkansas">Arkansas</option>
<option value="California">California</option>
<option value="Colorado">Colorado</option>
<option value="Connecticut">Connecticut</option>
<option value="Delaware">Delaware</option>
<option value="Florida">Florida</option>
<option value="Georgia">Georgia</option>
<option value="Hawaii">Hawaii</option>
<option value="Idaho">Idaho</option>
<option value="Illinois">Illinois</option>
<option value="Indiana">Indiana</option>
<option value="Iowa">Iowa</option>
<option value="Kansas">Kansas</option>
<option value="Kentucky">Kentucky</option>
<option value="Louisiana">Louisiana</option>
<option value="Maine">Maine</option>
<option value="Maryland">Maryland</option>
<option value="Massachusetts">Massachusetts</option>
<option value="Michigan">Michigan</option>
<option value="Minnesota">Minnesota</option>
<option value="Mississippi">Mississippi</option>
<option value="Missouri">Missouri</option>
<option value="Montana">Montana</option>
<option value="Nebraska">Nebraska</option>
<option value="Nevada">Nevada</option>
<option value="New Hampshire">New Hampshire</option>
<option value="New Jersey">New Jersey</option>
<option value="New Mexico">New Mexico</option>
<option value="New York">New York</option>
<option value="North Carolina">North Carolina</option>
<option value="North Dakota">North Dakota</option>
<option value="Ohio">Ohio</option>
<option value="Oklahoma">Oklahoma</option>
<option value="Oregon">Oregon</option>
<option value="Pennsylvania">Pennsylvania</option>
<option value="Rhode Island">Rhode Island</option>
<option value="South Carolina">South Carolina</option>
<option value="South Dakota">South Dakota</option>
<option value="Tennessee">Tennessee</option>
<option value="Texas">Texas</option>
<option value="Utah">Utah</option>
<option value="Vermont">Vermont</option>
<option value="Virginia">Virginia</option>
<option value="Washington">Washington</option>
<option value="Washington D.C.">Washington D.C.</option>
<option value="West Virginia">West Virginia</option>
<option value="Wisconsin">Wisconsin</option>
<option value="Wyoming">Wyoming</option>';

    $output = str_replace('value="' . $selected . '"', 'selected="selected" value="' . $selected . '"', $output);

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function videorecorderJS()
  {
    $output = <<<'EOS'
<script>
let vidrec_mediaRecorder;
let vidrec_recordedBlobs;
let vidrec_stream;
let vidrec_cameraMuted = true;
let vidrec_audioMuted = true;

const vidrec_videoElement = document.getElementById('vidrec_video');
const vidrec_recordedVideoElement = document.getElementById('vidrec_recordedVideo');
const vidrec_recordButton = document.getElementById('vidrec_recordButton');
const vidrec_stopButton = document.getElementById('vidrec_stopButton');
const vidrec_previewButton = document.getElementById('vidrec_previewButton');
const vidrec_submitButton = document.getElementById('vidrec_submitButton');
const vidrec_videoSource = document.getElementById('vidrec_videoSource');
const vidrec_audioSource = document.getElementById('vidrec_audioSource');
const vidrec_muteCameraButton = document.getElementById('vidrec_muteCameraButton');
const vidrec_muteAudioButton = document.getElementById('vidrec_muteAudioButton');

vidrec_recordButton.addEventListener('click', vidrec_startRecording);
vidrec_stopButton.addEventListener('click', vidrec_stopRecording);
vidrec_previewButton.addEventListener('click', vidrec_previewRecording);
vidrec_submitButton.addEventListener('click', vidrec_submitRecording);
vidrec_videoSource.addEventListener('change', vidrec_getStream);
vidrec_audioSource.addEventListener('change', vidrec_getStream);
vidrec_muteCameraButton.addEventListener('click', () => vidrec_toggleMute('video'));
vidrec_muteAudioButton.addEventListener('click', () => vidrec_toggleMute('audio'));

navigator.mediaDevices.enumerateDevices()
.then(vidrec_gotDevices).then(vidrec_getStream)
.catch(vidrec_handleError);

function vidrec_gotDevices(deviceInfos) {
const vidrec_videoSelect = document.getElementById('vidrec_videoSource');
const vidrec_audioSelect = document.getElementById('vidrec_audioSource');

vidrec_videoSelect.innerHTML = ''; // Clear existing options
vidrec_audioSelect.innerHTML = ''; // Clear existing options
deviceInfos.forEach(deviceInfo => {
const option = document.createElement('option');
option.value = deviceInfo.deviceId;
if (deviceInfo.kind === 'videoinput') {
option.text = deviceInfo.label || `Camera ${vidrec_videoSelect.length + 1}`;
vidrec_videoSelect.appendChild(option);
} else if (deviceInfo.kind === 'audioinput') {
option.text = deviceInfo.label || `Microphone ${vidrec_audioSelect.length + 1}`;
vidrec_audioSelect.appendChild(option);
}
});

console.log('Devices:', deviceInfos); // Log device information for debugging
}

function vidrec_getStream() {
const vidrec_videoSourceValue = document.getElementById('vidrec_videoSource').value;
const vidrec_audioSourceValue = document.getElementById('vidrec_audioSource').value;
const constraints = {
video: {deviceId: vidrec_videoSourceValue ? {exact: vidrec_videoSourceValue} : undefined},
audio: {deviceId: vidrec_audioSourceValue ? {exact: vidrec_audioSourceValue} : undefined}
};

navigator.mediaDevices.getUserMedia(constraints)
.then(mediaStream => {
const vidrec_videoElement = document.getElementById('vidrec_video');
vidrec_stream = mediaStream;
vidrec_videoElement.srcObject = vidrec_stream;
vidrec_videoElement.muted = true;
vidrec_videoElement.play();

if (vidrec_cameraMuted) {
vidrec_toggleMute('video', true);
}
if (vidrec_audioMuted) {
vidrec_toggleMute('audio', true);
}
})
.catch(vidrec_handleError);
}

function vidrec_handleError(error) {
console.error('Error: ', error);
}

function vidrec_startRecording() {
vidrec_recordedBlobs = [];
let options = {mimeType: 'video/webm;codecs=vp9'};
vidrec_mediaRecorder = new MediaRecorder(vidrec_videoElement.srcObject, options);

vidrec_mediaRecorder.ondataavailable = vidrec_handleDataAvailable;
vidrec_mediaRecorder.start();

vidrec_recordButton.disabled = true;
vidrec_stopButton.disabled = false;
vidrec_previewButton.style.display = 'none';
vidrec_submitButton.style.display = 'none';

vidrec_videoElement.muted = true;
vidrec_videoElement.style.display = 'block';
vidrec_recordedVideoElement.style.display = 'none';
}

function vidrec_stopRecording() {
vidrec_mediaRecorder.stop();
vidrec_recordButton.disabled = false;
vidrec_stopButton.disabled = true;
vidrec_previewButton.style.display = 'inline-block';
vidrec_submitButton.style.display = 'inline-block';

vidrec_videoElement.muted = false;
}

function vidrec_handleDataAvailable(event) {
if (event.data && event.data.size > 0) {
vidrec_recordedBlobs.push(event.data);
}
const vidrec_superBuffer = new Blob(vidrec_recordedBlobs, {type: 'video/webm'});
vidrec_recordedVideoElement.src = window.URL.createObjectURL(vidrec_superBuffer);
}

function vidrec_previewRecording() {
vidrec_videoElement.muted = true;
vidrec_videoElement.style.display = 'none';
vidrec_recordedVideoElement.style.display = 'block';
vidrec_recordedVideoElement.play();
}

function vidrec_submitRecording() {
const vidrec_blob = new Blob(vidrec_recordedBlobs, {type: 'video/webm'});
const vidrec_formData = new FormData();
vidrec_formData.append('video', vidrec_blob, 'interview.webm');
vidrec_formData.append('_token', document.getElementById('csrf_token').value); // Append CSRF token

$.ajax({
url: 'videorecorder.php',
method: 'POST',
data: vidrec_formData,
processData: false,
contentType: false,
success: function(response) {
alert('Video submitted successfully!');
},
error: function(jqXHR, textStatus, errorMessage) {
console.error(errorMessage);
}
});
}

async function vidrec_toggleMute(type, initial = false) {
if (type === 'video') {
if (!initial) {
vidrec_cameraMuted = !vidrec_cameraMuted;
}
if (vidrec_cameraMuted) {
const videoTrack = vidrec_stream.getVideoTracks()[0];
videoTrack.stop();
vidrec_stream.removeTrack(videoTrack);
vidrec_muteCameraButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
vidrec_videoElement.srcObject = null;
} else {
const vidrec_videoSourceValue = vidrec_videoSource.value;
try {
const mediaStream = await navigator.mediaDevices.getUserMedia({ video: { deviceId: vidrec_videoSourceValue ? { exact: vidrec_videoSourceValue } : undefined } });
const newVideoTrack = mediaStream.getVideoTracks()[0];
vidrec_stream.addTrack(newVideoTrack);
vidrec_videoElement.srcObject = vidrec_stream;
vidrec_muteCameraButton.innerHTML = '<i class="fas fa-eye"></i>';
} catch (error) {
vidrec_handleError(error);
}
}
} else if (type === 'audio') {
if (!initial) {
vidrec_audioMuted = !vidrec_audioMuted;
}
if (vidrec_audioMuted) {
const audioTrack = vidrec_stream.getAudioTracks()[0];
audioTrack.stop();
vidrec_stream.removeTrack(audioTrack);
vidrec_muteAudioButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
} else {
const vidrec_audioSourceValue = vidrec_audioSource.value;
try {
const mediaStream = await navigator.mediaDevices.getUserMedia({ audio: { deviceId: vidrec_audioSourceValue ? { exact: vidrec_audioSourceValue } : undefined } });
const newAudioTrack = mediaStream.getAudioTracks()[0];
vidrec_stream.addTrack(newAudioTrack);
vidrec_videoElement.srcObject = vidrec_stream;
vidrec_muteAudioButton.innerHTML = '<i class="fas fa-microphone"></i>';
} catch (error) {
vidrec_handleError(error);
}
}
}
}

</script>
EOS;

    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function videorecorderJS_original()
  {
    $output = <<<'EOS'
<script>
let mediaRecorder;
let recordedBlobs;
let stream;
let cameraMuted = true;
let audioMuted = true;

const videoElement = document.getElementById('video');
const recordedVideoElement = document.getElementById('recordedVideo');
const recordButton = document.getElementById('recordButton');
const stopButton = document.getElementById('stopButton');
const previewButton = document.getElementById('previewButton');
const submitButton = document.getElementById('submitButton');
const videoSource = document.getElementById('videoSource');
const audioSource = document.getElementById('audioSource');
const muteCameraButton = document.getElementById('muteCameraButton');
const muteAudioButton = document.getElementById('muteAudioButton');

recordButton.addEventListener('click', startRecording);
stopButton.addEventListener('click', stopRecording);
previewButton.addEventListener('click', previewRecording);
submitButton.addEventListener('click', submitRecording);
videoSource.addEventListener('change', getStream);
audioSource.addEventListener('change', getStream);
muteCameraButton.addEventListener('click', () => toggleMute('video'));
muteAudioButton.addEventListener('click', () => toggleMute('audio'));

navigator.mediaDevices.enumerateDevices()
.then(gotDevices).then(getStream)
.catch(handleError);

function gotDevices(deviceInfos) {
const videoSelect = videoSource;
const audioSelect = audioSource;
deviceInfos.forEach(deviceInfo => {
const option = document.createElement('option');
option.value = deviceInfo.deviceId;
if (deviceInfo.kind === 'videoinput') {
option.text = deviceInfo.label || `Camera ${videoSelect.length + 1}`;
videoSelect.appendChild(option);
} else if (deviceInfo.kind === 'audioinput') {
option.text = deviceInfo.label || `Microphone ${audioSelect.length + 1}`;
audioSelect.appendChild(option);
}
});
}

function getStream() {
const videoSourceValue = videoSource.value;
const audioSourceValue = audioSource.value;
const constraints = {
video: {deviceId: videoSourceValue ? {exact: videoSourceValue} : undefined},
audio: {deviceId: audioSourceValue ? {exact: audioSourceValue} : undefined}
};

navigator.mediaDevices.getUserMedia(constraints)
.then(mediaStream => {
stream = mediaStream;
videoElement.srcObject = stream;
videoElement.muted = true;
videoElement.play();

if (cameraMuted) {
toggleMute('video', true);
}
if (audioMuted) {
toggleMute('audio', true);
}
})
.catch(handleError);
}

function handleError(error) {
console.error('Error: ', error);
}

function startRecording() {
recordedBlobs = [];
let options = {mimeType: 'video/webm;codecs=vp9'};
mediaRecorder = new MediaRecorder(videoElement.srcObject, options);

mediaRecorder.ondataavailable = handleDataAvailable;
mediaRecorder.start();

recordButton.disabled = true;
stopButton.disabled = false;
previewButton.style.display = 'none';
submitButton.style.display = 'none';

videoElement.muted = true;
videoElement.style.display = 'block';
recordedVideoElement.style.display = 'none';
}

function stopRecording() {
mediaRecorder.stop();
recordButton.disabled = false;
stopButton.disabled = true;
previewButton.style.display = 'inline-block';
submitButton.style.display = 'inline-block';

videoElement.muted = false;
}

function handleDataAvailable(event) {
if (event.data && event.data.size > 0) {
recordedBlobs.push(event.data);
}
const superBuffer = new Blob(recordedBlobs, {type: 'video/webm'});
recordedVideoElement.src = window.URL.createObjectURL(superBuffer);
}

function previewRecording() {
videoElement.muted = true;
videoElement.style.display = 'none';
recordedVideoElement.style.display = 'block';
recordedVideoElement.play();
}

function submitRecording() {
const blob = new Blob(recordedBlobs, {type: 'video/webm'});
const formData = new FormData();
formData.append('video', blob, 'interview.webm');
formData.append('_token', document.getElementById('csrf_token').value); // Append CSRF token

$.ajax({
url: 'videorecorder.php',
method: 'POST',
data: formData,
processData: false,
contentType: false,
success: function(response) {
alert('Video submitted successfully!');
},
error: function(jqXHR, textStatus, errorMessage) {
console.error(errorMessage);
}
});
}

async function toggleMute(type, initial = false) {
if (type === 'video') {
if (!initial) {
cameraMuted = !cameraMuted;
}
if (cameraMuted) {
const videoTrack = stream.getVideoTracks()[0];
videoTrack.stop();
stream.removeTrack(videoTrack);
muteCameraButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
videoElement.srcObject = null;
} else {
const videoSourceValue = videoSource.value;
try {
const mediaStream = await navigator.mediaDevices.getUserMedia({ video: { deviceId: videoSourceValue ? { exact: videoSourceValue } : undefined } });
const newVideoTrack = mediaStream.getVideoTracks()[0];
stream.addTrack(newVideoTrack);
videoElement.srcObject = stream;
muteCameraButton.innerHTML = '<i class="fas fa-eye"></i>';
} catch (error) {
handleError(error);
}
}
} else if (type === 'audio') {
if (!initial) {
audioMuted = !audioMuted;
}
if (audioMuted) {
const audioTrack = stream.getAudioTracks()[0];
audioTrack.stop();
stream.removeTrack(audioTrack);
muteAudioButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
} else {
const audioSourceValue = audioSource.value;
try {
const mediaStream = await navigator.mediaDevices.getUserMedia({ audio: { deviceId: audioSourceValue ? { exact: audioSourceValue } : undefined } });
const newAudioTrack = mediaStream.getAudioTracks()[0];
stream.addTrack(newAudioTrack);
videoElement.srcObject = stream;
muteAudioButton.innerHTML = '<i class="fas fa-microphone"></i>';
} catch (error) {
handleError(error);
}
}
}
}

</script>
EOS;

    return $output;
  }


  // end of class
}
