<?php
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

$presentation = isset($_GET['content']) ? $_GET['content'] : 'userenrollment_quicktraining';

$stmt = $database->prepare("SELECT * FROM bg_slides WHERE `grouping` = ? AND status = 'active' ORDER BY slide_order ASC");
$stmt->execute([$presentation]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);



$slides = $results;

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <title>Birthday.Gold Presentation</title>
    <meta name="description" content="Birthday.Gold Presentation">
  <link rel="stylesheet" type="text/css" media="all" href="static/css/webslides.css"> 
   <!-- Optional - CSS SVG Icons (Font Awesome) -->
   <link rel="stylesheet" type='text/css' media='all' href="static/css/svg-icons.css">
   <?/*
     <!-- Bootstrap CSS 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome CSS 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- Bootstrap Icons CSS 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
-->
*/
?>

  <style>
    .slide { 
      display: flex; 
      justify-content: center; 
      align-items: center; 
      text-align: center; 
    }

    .bg-bgold{
  font-family: 'San Francisco', helvetica, arial, sans-serif; 
  background: -webkit-gradient(linear, left top, left bottom, from(#00008b), color-stop(50%, #1a2b5e), to(#293a75));
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
<header role="banner">
      <nav role="navigation">
        <p class="logo"><a href="index.html" title="Birthday.Gold Presentation">Birthday.Gold</a></p>

        
    <div class="control-buttons">
      <button id="play-pause" title="Play/Pause">
        <svg class="fa-play" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M424.4 214.7L72.4 3.7C48.5-8.1 16 8.5 16 37.5V474.5c0 29 32.5 45.6 56.4 33.8l352-211.1c24-12.2 24-46 0-58.2z"/></svg>
      </button>
      <button id="stop" title="Stop">
        <svg class="fa-stop" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zM368 400c0 8.8-7.2 16-16 16H96c-8.8 0-16-7.2-16-16V112c0-8.8 7.2-16 16-16h256c8.8 0 16 7.2 16 16v288z"/></svg>
      </button>
      <button id="repeat" title="Repeat">
        <svg class="fa-repeat" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M512 224c0 13.3-10.7 24-24 24H488C465.1 132.3 362.9 64 256 64c-82.4 0-158.7 39.3-204.8 104.4c-5.4 7.6-16.4 9.4-24 4.1s-9.4-16.4-4.1-24C74.4 53.2 161.3 8 256 8c121.9 0 225.6 80.8 255.4 192H488C474.7 200 464 210.7 464 224zM468.9 407.6C437.6 452.8 378.8 480 316.8 480H248V456c0-13.3-10.7-24-24-24H64c-13.3 0-24 10.7-24 24V480c0 13.3 10.7 24 24 24H224c35.3 0 64-28.7 64-64V408h68.8c45.8 0 88.1-20.7 116.7-57.8c5.5-7.7 3.7-18.7-4.1-24C485.2 315.4 474.3 317.6 468.9 407.6zM152 296c-13.3 0-24-10.7-24-24c0-13.3 10.7-24 24-24s24 10.7 24 24C176 285.3 165.3 296 152 296zM72 296c-13.3 0-24-10.7-24-24c0-13.3 10.7-24 24-24s24 10.7 24 24C96 285.3 85.3 296 72 296z"/></svg>
      </button>
    </div>
      </nav>
    </header>

<main role="main">
       <?php

$output = '';
$counter=0;
foreach ($slides as $slide) {
  switch($slide['slide_order'])
  {
    case 0: 
echo ' <article id="webslides" class="'.($slide['section_class'] ?? 'vertical').'">';
      break;
      default: 
      $counter++;
    $sectionClass = $slide['section_class'] ?? '';
    $speechScript = $slide['speech_script'] ?? '';
    $sectionTag = $slide['section_tag'] ?? '';
       $content = $slide['content'] ?? '';
       $section_prefix = $slide['section_prefix'] ?? '';
       if (!empty($section_prefix)) $section_prefix="\n$section_prefix\n";
echo '

<!-- Slide #'.   $counter.' - ID:'.$slide['id'].' ====================================================================== -->'.$section_prefix.'
<section class="' . $sectionClass . '"  id="' . $sectionTag . '"
data-speech="' . str_replace(array("\r", "\n"), '', $speechScript). '">
' . $content . '
</section>
';
break;
}
}

?>

      </article>
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
            playPauseButton.innerHTML = '<svg class="fa-pause" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M144 479.6c0 13.3 10.7 24 24 24H280c13.3 0 24-10.7 24-24V32.4c0-13.3-10.7-24-24-24H168c-13.3 0-24 10.7-24 24v447.2z"/></svg>';
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
</script>
  </body>
</html>
