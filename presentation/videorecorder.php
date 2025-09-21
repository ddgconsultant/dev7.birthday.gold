<?php
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 





#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT   -- this is an AJAX POST
#-------------------------------------------------------------------------------
if ($app->formposted()) {


    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["video"]["name"]);

    if (move_uploaded_file($_FILES["video"]["tmp_name"], $target_file)) {
        // Save video metadata to the database
        $stmt = $database->prepare("INSERT INTO interview_videos (filename, uploaded_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $target_file);
        $stmt->execute();
        $stmt->close();

        echo "The file " . htmlspecialchars(basename($_FILES["video"]["name"])) . " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <title>Birthday.Gold Presentation</title>
  <meta name="description" content="Birthday.Gold Presentation">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
  <link rel="stylesheet" type="text/css" media="all" href="static/css/webslides.css"> 
  <link rel="stylesheet" type='text/css' media='all' href="static/css/svg-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
<header  >
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
      <svg class="fa-repeat" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
  <path d="M12 3C6.48 3 2 7.48 2 13c0 1.25.2 2.45.57 3.57l1.52-1.52c-.23-.74-.35-1.53-.35-2.34 0-4.08 3.32-7.4 7.4-7.4 1.52 0 2.93.47 4.11 1.27L14 10h8V2l-2.18 2.18C17.16 2.79 14.67 2 12 2zm10.41 7.58L18.88 12c.23.74.35 1.53.35 2.34 0 4.08-3.32 7.4-7.4 7.4-1.52 0-2.93-.47-4.11-1.27l2.53-2.53H4v8l2.18-2.18C6.84 21.21 9.33 22 12 22c5.52 0 10-4.48 10-10 0-1.25-.2-2.45-.57-3.57z"/>
</svg>
     </button>
    </div>
  </nav>
</header>

<main role="main">

<article id="webslides" class="vertical">
    
    <!-- Slide #1 - ID:601 ====================================================================== -->
    <section class="bg-white"  id=""
    data-speech="Describe how birthday.gold can support you to excel in your role.">
    <span class="background" style="background-image:url('assets/images/bg_interviewer.jpg');"></span>
   <div class="wrap">
     <div class="content-right">
       <h2>What are ways birthday.gold support you to be the best version of you in your role?</h2>
       <form action="formdatahandler.php" method="post" class="bg-trans-dark">
         <ul class="flexblock">
           <li><textarea name="answer11" rows="4" required></textarea></li>
           <li><button type="submit" class="radius" title="Next">Next &rsaquo;</button></li>
           <input type="hidden" name="presentation_set" value="interview_set">
           <input type="hidden" name="formname" value="question11_form">
           <input type="hidden" name="formsection" value="section11">
           <input type="hidden" name="next_slide_id" value="12">
         </ul>
       </form>
     </div>
   </div>
    </section>

    <section class="bg-white"  id=""
    data-speech="Describe how birthday.gold can support you to excel in your role.">
    <span class="background" style="background-image:url('assets/images/bg_interviewer.jpg');"></span>
   <div class="wrap">
     <div class="content-right">
        <h1>Video Interview - Sales Rep</h1>
        <p>Describe what you think is the ideal customer for Birthday.Gold and what would your 30-second sales pitch be to them?</p>
        <div class="mb-3 video-recorder d-flex align-items-center">
    <label for="videoSource" class="me-2 mt-3">Video&nbsp;Device: </label>
    <select id="videoSource" class="form-select me-2"></select>
    <button id="muteCameraButton" class="btn btn-secondary me-4 px-4">
        <svg class="svg-inline--fa fa-eye-slash" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="eye-slash" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" data-fa-i2svg=""><path fill="currentColor" d="M325.1 351.5L225.8 273.6c8.303 44.56 47.26 78.37 94.22 78.37C321.8 352 323.4 351.6 325.1 351.5zM320 400c-79.5 0-144-64.52-144-143.1c0-6.789 1.09-13.28 1.1-19.82L81.28 160.4c-17.77 23.75-33.27 50.04-45.81 78.59C33.56 243.4 31.1 251 31.1 256c0 4.977 1.563 12.6 3.469 17.03c54.25 123.4 161.6 206.1 284.5 206.1c45.46 0 88.77-11.49 128.1-32.14l-74.5-58.4C356.1 396.1 338.1 400 320 400zM630.8 469.1l-103.5-81.11c31.37-31.96 57.77-70.75 77.21-114.1c1.906-4.43 3.469-12.07 3.469-17.03c0-4.976-1.562-12.6-3.469-17.03c-54.25-123.4-161.6-206.1-284.5-206.1c-62.69 0-121.2 21.94-170.8 59.62L38.81 5.116C34.41 1.679 29.19 0 24.03 0C16.91 0 9.839 3.158 5.121 9.189c-8.187 10.44-6.37 25.53 4.068 33.7l591.1 463.1c10.5 8.203 25.57 6.333 33.69-4.073C643.1 492.4 641.2 477.3 630.8 469.1z"></path></svg>
    </button>
    <label for="audioSource" class="me-2 mt-3">Audio&nbsp;Device: </label>
    <select id="audioSource" class="form-select me-2"></select>
    <button id="muteAudioButton" class="btn btn-secondary px-4">
        <svg class="svg-inline--fa fa-microphone-slash" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="microphone-slash" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" data-fa-i2svg=""><path fill="currentColor" d="M383.1 464l-39.1-.0001v-33.77c20.6-2.824 39.98-9.402 57.69-18.72l-43.26-33.91c-14.66 4.65-30.28 7.179-46.68 6.144C245.7 379.6 191.1 317.1 191.1 250.9V247.2L143.1 209.5l.0001 38.61c0 89.65 63.97 169.6 151.1 181.7v34.15l-40 .0001c-17.67 0-31.1 14.33-31.1 31.1C223.1 504.8 231.2 512 239.1 512h159.1c8.838 0 15.1-7.164 15.1-15.1C415.1 478.3 401.7 464 383.1 464zM630.8 469.1l-159.3-124.9c15.37-25.94 24.53-55.91 24.53-88.21V216c0-13.25-10.75-24-23.1-24c-13.25 0-24 10.75-24 24l-.0001 39.1c0 21.12-5.559 40.77-14.77 58.24l-25.72-20.16c5.234-11.68 8.493-24.42 8.493-38.08l-.001-155.1c0-52.57-40.52-98.41-93.07-99.97c-54.37-1.617-98.93 41.95-98.93 95.95l0 54.25L38.81 5.111C34.41 1.673 29.19 0 24.03 0C16.91 0 9.839 3.158 5.12 9.189c-8.187 10.44-6.37 25.53 4.068 33.7l591.1 463.1c10.5 8.203 25.57 6.328 33.69-4.078C643.1 492.4 641.2 477.3 630.8 469.1z"></path></svg>
    </button>
</div>
        <div class="mb-3">
            <video id="video" width="100%" height="380" autoplay muted></video>
            <video id="recordedVideo" width="100%"  height="380" controls></video>
        </div>
        <div class="mb-3">
    <button id="recordButton" class="btn btn-primary px-4">Record</button>
    <button id="stopButton" class="btn btn-danger px-4" disabled>Stop</button>
    <button id="previewButton" class="btn btn-secondary px-4" style="display: none;">Preview</button>
    <button id="submitButton" class="btn btn-success px-4" style="display: none;">Submit</button>
</div>
<?PHP
echo '<input type="hidden" id="csrf_token" value="'.$display->inputcsrf_token('tokenonly').'">';
?>
    </div>


    </section>
    </article></main>


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



    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <?php echo $display->videorecorderJS(); ?>
</body>
</html>
