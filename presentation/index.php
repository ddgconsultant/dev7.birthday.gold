<?php
$addClasses[] = 'fileuploader';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# PREP DEFAULTS
#-------------------------------------------------------------------------------
// presentation defaults
$presentation_orientation = 'vertical';
$presentation_header = 'role="banner"';
$output = '';
$counter = 0;
$finaljs = '';
$timingdelay = 1000;
$js_function_advance = 'function advanceSlide() {  }';


#-------------------------------------------------------------------------------
# INTERNAL FUNCTIONS
#-------------------------------------------------------------------------------
// Function to replace placeholders with session data
function replace_placeholders($content, $removequotes = false)
{
  foreach ($_SESSION['form_data'] as $key => $value) {
    // Ensure that $value is a string before using htmlspecialchars
    if (is_array($value)) {
      $value = implode(', ', $value); // Convert array to string, you can customize this as needed
    }
    $value = (string)$value;
    
    // Remove double quotes if $removequotes is true
    if ($removequotes) {
      $value = str_replace('"', '', $value);
    }
    
    $content = str_replace("{{{$key}}}", htmlspecialchars($value), $content);
  }
  return $content;
}





#-------------------------------------------------------------------------------
# HANDLE DIRECT PRESENTATION LINK
#-------------------------------------------------------------------------------
$presentation = isset($_GET['content']) ? $_GET['content'] : 'presentationlist';



#-------------------------------------------------------------------------------
# GET SLIDES
#-------------------------------------------------------------------------------
$stmt = $database->prepare("SELECT * FROM bg_slides WHERE `grouping` = ? AND status = 'active' ORDER BY slide_order ASC");
$stmt->execute([$presentation]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$slides = $results;

// Ensure form_data is initialized
if (!isset($_SESSION['form_data'])) {
  $_SESSION['form_data'] = [];
}
if (!empty($_SESSION['current_user_data'])) {
  $_SESSION['form_data'] = array_merge($_SESSION['form_data'], $_SESSION['current_user_data']);
}
#$_SESSION['form_data']['first_name'] = 'Carin';




#-------------------------------------------------------------------------------
# LOOP THROUGH SLIDES
#-------------------------------------------------------------------------------
foreach ($slides as $slide) {
  $section_suffix = '';
  $counter++; // Ensure slide counter is correctly incremented

  // Determine the section prefix for each slide
  $section_prefix = $slide['section_prefix'] ?? '';

  switch ($slide['slide_order']) {
      // handle dynamic presentation setup record here
      case 0:
          // Initial setup for the presentation
          $output .= '<article id="webslides" class="' . ($slide['section_class'] ?? $presentation_orientation) . '">';
          if (strpos(($slide['content'] ?? ''), '!shownavigation') !== false) $presentation_header = '';

          if (strpos(($slide['content'] ?? ''), '!autoadvance') !== false) {
              // get delay if any
              if (strpos(($slide['content'] ?? ''), '!autoadvance:') !== false) {
                  list($junktag, $timingdelay) = explode('!autoadvance:', $slide['content']);
              }
              $js_function_advance = 'function advanceSlide() {setTimeout(function() { window.ws.goNext(); }, ' . $timingdelay . ');  }';
          }
          break;

      //========================================================
      // all other slides records
      default:
          $sectionClass = $slide['section_class'] ?? '';
          $speechScript = replace_placeholders(($slide['speech_script'] ?? ''), true);
          $sectionTag = $slide['section_tag'] ?? '';
          $content = replace_placeholders($slide['content'] ?? '');

          ###########################################################
          ###  HANDLE DIFFERENT TYPES OF SLIDES
          switch (true) {
              //========================================================
              // Handle !MODULE prefix
              case (strpos($section_prefix ?? '', '!MODULE:') !== false):
                  // Extract module name from the section prefix
                  $slidemodule = str_replace('!MODULE:', '', $section_prefix);
                  $slidefile = $_SERVER['DOCUMENT_ROOT'] . '/presentation/module_slides/' . $slidemodule . '.inc';

                  // Check and include the module file if it exists
                  if (file_exists($slidefile)) {
                      include($slidefile); // Include the module file
                      $section_prefix = '<!-- !! ' . $section_prefix . ' -->';
                  } else {
                      $section_prefix = '<!-- --nofile-- ' . $section_prefix . ' -->';
                  }
                  break;

              //========================================================
              case '!videorecorder':
                  $finaljs .= '<script src="/public/js/jquery-3.6.0.min.js"></script>
<script src="/public/js/font-awesome/6.0.0-beta3-js-all.min.js"></script>
' . $display->videorecorderJS() . '';

                  $videorecordercontent = '
<div class="mb-3 video-recorder d-flex align-items-center">
<label for="vidrec_videoSource" class="me-2 mt-3">Video&nbsp;Device: </label>
<select id="vidrec_videoSource" class="form-select me-2"></select>
<button id="vidrec_muteCameraButton" class="btn btn-secondary me-4  px-4">
  <svg class="svg-inline--fa fa-eye-slash" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="eye-slash" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" data-fa-i2svg=""><path fill="currentColor" d="M325.1 351.5L225.8 273.6c8.303 44.56 47.26 78.37 94.22 78.37C321.8 352 323.4 351.6 325.1 351.5zM320 400c-79.5 0-144-64.52-144-143.1c0-6.789 1.09-13.28 1.1-19.82L81.28 160.4c-17.77 23.75-33.27 50.04-45.81 78.59C33.56 243.4 31.1 251 31.1 256c0 4.977 1.563 12.6 3.469 17.03c54.25 123.4 161.6 206.1 284.5 206.1c45.46 0 88.77-11.49 128.1-32.14l-74.5-58.4C356.1 396.1 338.1 400 320 400zM630.8 469.1l-103.5-81.11c31.37-31.96 57.77-70.75 77.21-114.1c1.906-4.43 3.469-12.07 3.469-17.03c0-4.976-1.562-12.6-3.469-17.03c-54.25-123.4-161.6-206.1-284.5-206.1c-62.69 0-121.2 21.94-170.8 59.62L38.81 5.116C34.41 1.679 29.19 0 24.03 0C16.91 0 9.839 3.158 5.121 9.189c-8.187 10.44-6.37 25.53 4.068 33.7l591.1 463.1c10.5 8.203 25.57 6.333 33.69-4.073C643.1 492.4 641.2 477.3 630.8 469.1z"></path></svg>
</button>
<label for="vidrec_audioSource" class="me-2 mt-3">Audio&nbsp;Device: </label>
<select id="vidrec_audioSource" class="form-select me-2"></select>
<button id="vidrec_muteAudioButton" class="btn btn-secondary  px-4">
  <svg class="svg-inline--fa fa-microphone-slash" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="microphone-slash" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" data-fa-i2svg=""><path fill="currentColor" d="M383.1 464l-39.1-.0001v-33.77c20.6-2.824 39.98-9.402 57.69-18.72l-43.26-33.91c-14.66 4.65-30.28 7.179-46.68 6.144C245.7 379.6 191.1 317.1 191.1 250.9V247.2L143.1 209.5l.0001 38.61c0 89.65 63.97 169.6 151.1 181.7v34.15l-40 .0001c-17.67 0-31.1 14.33-31.1 31.1C223.1 504.8 231.2 512 239.1 512h159.1c8.838 0 15.1-7.164 15.1-15.1C415.1 478.3 401.7 464 383.1 464zM630.8 469.1l-159.3-124.9c15.37-25.94 24.53-55.91 24.53-88.21V216c0-13.25-10.75-24-23.1-24c-13.25 0-24 10.75-24 24l-.0001 39.1c0 21.12-5.559 40.77-14.77 58.24l-25.72-20.16c5.234-11.68 8.493-24.42 8.493-38.08l-.001-155.1c0-52.57-40.52-98.41-93.07-99.97c-54.37-1.617-98.93 41.95-98.93 95.95l0 54.25L38.81 5.111C34.41 1.673 29.19 0 24.03 0C16.91 0 9.839 3.158 5.12 9.189c-8.187 10.44-6.37 25.53 4.068 33.7l591.1 463.1c10.5 8.203 25.57 6.328 33.69-4.078C643.1 492.4 641.2 477.3 630.8 469.1z"></path></svg>
</button>
</div>
<div class="mb-3">
<video id="vidrec_video" width="100%" height="380" autoplay muted></video>
<video id="vidrec_recordedVideo" width="100%"  height="380" controls  style="display: none;"></video>
</div>
<div class="mb-3">
<button id="vidrec_recordButton" class="btn btn-primary px-4">Record</button>
<button id="vidrec_stopButton" class="btn btn-danger px-4" disabled>Stop</button>
<button id="vidrec_previewButton" class="btn btn-secondary px-4" style="display: none;">Preview</button>
<button id="vidrec_submitButton" class="btn btn-success px-4" style="display: none;">Submit</button>
</div>
<input type="hidden" id="vidrec_csrf_token" value="' . $display->inputcsrf_token('tokenonly') . '">
';
                  $content = str_replace('[!videorecorder]', $videorecordercontent, $content);
                  break;

              //========================================================
              default:
                  $section_prefix = "\n$section_prefix\n";
                  break;
          }

          //========================================================
          // create actual slide
          $output .= '

<!-- Slide #' . $counter . ' - ID:' . $slide['id'] . ' ====================================================================== -->' . $section_prefix . '
<section class="' . $sectionClass . '"  id="' . $sectionTag . '"
data-speech="' . str_replace(array("\r", "\n"), '', $speechScript) . '">
' . $content . '
</section>
' . $section_suffix;

          break;
  }
}

$output .= '</article>';



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Birthday.Gold Presentation</title>
  <meta name="description" content="Birthday.Gold Presentation">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" type="text/css" media="all" href="static/css/webslides.css">
  <link rel="stylesheet" type='text/css' media='all' href="static/css/svg-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
.flexblock .large-icon{
  font-size: 2rem !important; /* Adjust the size as needed */
  vertical-align: middle !important; /* Aligns the icon vertically with the text */
  margin-right: 10px !important; /* Adds space between the icon and the text */
}

    #recordedVideo {
      display: none;
    }

    .video-recorder {
      display: flex;
      align-items: center;
    }

    .video-recorder .me-2 {
      margin-right: 0.5rem;
    }

    .video-recorder .me-4 {
      margin-right: 1rem;
    }
  </style>

  <style>
    textarea::placeholder {
      color: #bfbfbf;
    }
  </style>
  <style>
    .slide {
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
    }

    .bg-bgold {
      font-family: 'San Francisco', helvetica, arial, sans-serif;
      background: linear-gradient(to bottom, #00008b 0%, #1a2b5e 50%, #293a75 100%);
    }

    .control-buttons {
      position: fixed;
      bottom: 20px;
      right: 20px;
      display: flex;
      gap: 10px;
    }

    .control-buttons button {
      background-color: #fff;
      border: 1px solid #ccc;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .control-buttons button svg {
      width: 20px;
      height: 20px;
    }
  </style>
</head>

<body>
  <header <?PHP echo $presentation_header; ?>>
    <nav role="navigation">
      <p class="logo"><a href="index.html" title="Birthday.Gold Presentation">Birthday.Gold</a></p>
      <div class="control-buttons">
        <button id="play-pause" title="Play/Pause">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-play-fill" viewBox="0 0 16 16">
  <path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393"/>
</svg>
        </button>
        <button id="stop" title="Stop">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-stop" viewBox="0 0 16 16">
  <path d="M3.5 5A1.5 1.5 0 0 1 5 3.5h6A1.5 1.5 0 0 1 12.5 5v6a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 11zM5 4.5a.5.5 0 0 0-.5.5v6a.5.5 0 0 0 .5.5h6a.5.5 0 0 0 .5-.5V5a.5.5 0 0 0-.5-.5z"/>
</svg>
        </button>
        <button id="repeat" title="Repeat">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-repeat" viewBox="0 0 16 16">
  <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41m-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9"/>
  <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5 5 0 0 0 8 3M3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9z"/>
</svg>
        </button>
      </div>
    </nav>
  </header>

  <main role="main">

    <?PHP echo $output; ?>
  </main>

  <!-- Required -->
  <script src="static/js/webslides.js"></script>
  <script>
    window.ws = new WebSlides();

    const speechSynthesis = window.speechSynthesis;
    let currentSpeech = null;
    const playPauseButton = document.getElementById('play-pause');
    const stopButton = document.getElementById('stop');
    const repeatButton = document.getElementById('repeat');

    window.ws.el.addEventListener('ws:slide-change', () => {
      const currentSection = document.querySelector('.current');
      const speechText = currentSection.getAttribute('data-speech');
      stopSpeech();
      speakText(speechText);
    });

    playPauseButton.addEventListener('click', () => {
      if (speechSynthesis.paused) {
        speechSynthesis.resume();
        playPauseButton.innerHTML = '<svg class="fa-pause" xmlns=http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M144 479.6c0 13.3 10.7 24 24 24H280c13.3 0 24-10.7 24-24V32.4c0-13.3-10.7-24-24-24H168c-13.3 0-24 10.7-24 24v447.2z"/></svg>';
      } else if (speechSynthesis.speaking) {
        speechSynthesis.pause();
        playPauseButton.innerHTML = '<svg class="fa-play" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M424.4 214.7L72.4 3.7C48.5-8.1 16 8.5 16 37.5V474.5c0 29 32.5 45.6 56.4 33.8l352-211.1c24-12.2 24-46 0-58.2z"/></svg>';
      } else {
        const currentSection = document.querySelector('.current');
        const speechText = currentSection.getAttribute('data-speech');
        speakText(speechText);
      }
    });

    stopButton.addEventListener('click', () => {
      stopSpeech();
    });

    repeatButton.addEventListener('click', () => {
      const currentSection = document.querySelector('.current');
      const speechText = currentSection.getAttribute('data-speech');
      stopSpeech();
      speakText(speechText);
    });

    function speakText(text) {
      if (text) {
        let chunks = text.split('[break]');

        function speakChunk(index) {
          if (index < chunks.length) {
            const msg = new SpeechSynthesisUtterance();
            msg.text = chunks[index].trim();
            msg.voice = speechSynthesis.getVoices().find(voice => voice.name === 'Google UK English Female');
            msg.rate = 1;
            msg.onend = () => {
              speakChunk(index + 1);
            };
            speechSynthesis.speak(msg);
            currentSpeech = msg;
          } else {
            setTimeout(advanceSlide, 1000);
          }
        }

        speakChunk(0);
      }
    }

    function stopSpeech() {
      if (speechSynthesis.speaking || speechSynthesis.paused) {
        speechSynthesis.cancel();
        currentSpeech = null;
        playPauseButton.innerHTML = '<svg class="fa-play" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M424.4 214.7L72.4 3.7C48.5-8.1 16 8.5 16 37.5V474.5c0 29 32.5 45.6 56.4 33.8l352-211.1c24-12.2 24-46 0-58.2z"/></svg>';
      }
    }

    <?PHP echo $js_function_advance; ?>
  </script>

  <?PHP
  echo $finaljs;
  ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">

</body>

</html>