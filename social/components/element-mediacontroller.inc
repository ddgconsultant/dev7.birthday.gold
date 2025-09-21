<?PHP 


$additionalstyles.='
<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet"> 
<style > 
.right-panel { position: relative; height: calc(100vh - 75px); overflow: hidden; background-color: #757575; }
.right-panel .post-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: flex-start; padding: 20px; box-sizing: border-box; color: white; background: rgba(0, 0, 0, 0.5); z-index: 1; pointer-events: none; }
.carousel-control-prev, .carousel-control-next { z-index: 3; height: 50%; top: 25%; bottom: auto; }
.carousel-control-prev-icon, .carousel-control-next-icon { width: 30px; height: 30px; }
.right-panel .post-actions { z-index: 4 !important; pointer-events: auto; position: relative; display: flex; flex-direction: column; align-items: flex-start; gap: 10px; position: absolute; left: 20px; z-index: 3; }
.right-panel .post-actions .icon-container { z-index: 5 !important; pointer-events: auto; cursor: pointer; display: flex; flex-direction: column; align-items: center; }
.right-panel .post-header, .right-panel .post-description, .right-panel .post-tags, .right-panel .post-actions .icon-container { pointer-events: auto; }
.carousel-item img { width: 100%; height: 100%; object-fit: contain; }
.right-panel .post-header { display: flex; align-items: center; gap: 10px; }
.right-panel .post-header img { width: 50px; height: 50px; border-radius: 50%; }
.right-panel .post-header .username { font-weight: bold; font-size: 1.2rem; }
.right-panel .post-header .follow-icon { display: flex; align-items: center; gap: 5px; color: white; cursor: pointer; }
.right-panel .follow-icon .follow-count { font-size: 0.8rem; color: white; }
.right-panel .title { margin-top: 10px; font-size: 1.5rem; font-weight: bold; }
.right-panel .post-description { font-size: 1rem; }
.right-panel .post-tags { margin-top: 5px; }
.right-panel .post-actions .location-badge { background-color: #17a2b8; font-size: 0.9rem; padding: 5px 10px; border-radius: 10px; z-index: 5; }
.right-panel .post-actions .icon-bar { display: flex; gap: 20px; z-index: 5; }
.right-panel .post-actions .icon { font-size: 1.5rem; }
.right-panel .post-actions .interaction-count { font-size: 0.7rem; color: white; margin-top: 2px; z-index: 5; }
.error-message { position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background-color: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; z-index: 9999; display: none; }
.carousel-dot-indicator { position: absolute; width: 100%; display: flex; justify-content: center; gap: 10px; }
.carousel-dot-indicator .dot { width: 12px; height: 12px; background-color: #fff; border-radius: 50%; opacity: 0.5; cursor: pointer; }
.carousel-dot-indicator .dot.active { opacity: 1; width: 16px; height: 16px; }
.hidden-overlay { visibility: hidden !important; opacity: 0; pointer-events: none; transition: visibility 0s, opacity 0.3s ease; }
.image-count-badge { position: absolute; right: 20px; background-color: rgba(0, 0, 0, 0.7); color: white; border-radius: 5px; font-size: 1rem; z-index: 10; }
.text-shadow { text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7); }
.play-button { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); cursor: pointer; z-index: 10; display: flex; align-items: center; justify-content: center; transition: opacity 0.3s ease; opacity: 0.5; }
.play-button:hover { opacity: 1; }
.play-button i { font-size: 10rem; color: white; opacity: 0.8; }
.soundtrack-avatar { position: absolute; right: 20px; z-index: 10; }
.soundtrack-avatar-icon { width: 50px; height: 50px; border-radius: 50%; cursor: pointer; transition: transform 0.3s ease; }
.soundtrack-avatar-icon:hover { transform: scale(1.1); }
.soundtrack-avatar-icon { transition: transform 0.5s ease-in-out; }
.rotating-avatar { animation: rotate 1.5s linear infinite; }
@keyframes rotate {
from { transform: rotate(0deg); }
to { transform: rotate(360deg); }
}
.countdown-timer { position: absolute; right: 20px; background-color: rgba(0, 0, 0, 0.7); color: white; border-radius: 5px; font-size: 1rem; z-index: 10; padding: 5px 10px; }
.mute-btn { position: absolute; right: 90px; color: white; border: none; background: none; border-radius: 3px; padding: 2px 4px 1px 4px; cursor: pointer; z-index: 10; }
.mute-btn:hover { background-color: rgba(255, 255, 255, 0.7); }
.mute-btn i { font-size: 1.2rem; }
.seek-bar { position: absolute; left: 220px; right: 140px; z-index: 10; height: 3px; background-color: rgba(100, 100, 100, 0.6); border-radius: 5px; cursor: pointer; appearance: none; }
@media (max-width:991.98px) {
.seek-bar { left: 250px; }
}
.seek-bar:hover { height: 5px; background-color: rgba(255, 255, 255, 0.6); }
.seek-bar::-webkit-slider-thumb, .seek-bar::-moz-range-thumb { appearance: none; width: 10px; height: 10px; border-radius: 50%; background: rgba(0, 123, 255, 0.6); cursor: pointer; border: none; transform: translateY(-1px); transition: width 0.2s ease, height 0.2s ease, background 0.2s ease, transform 0.2s ease; }
.seek-bar:hover::-webkit-slider-thumb, .seek-bar:hover::-moz-range-thumb { width: 15px; height: 15px; background: rgba(0, 123, 255, 1); }



</style>
';

if (isset($muteBtnRight)) {
$postadditionalstyles='
<style>
.mute-btn {right: '.$muteBtnRight.'; }
</style>';
}

$elementcontent['seekbar']='
<!-- Seek bar -->
<input type="range" id="seekBar" class="seek-bar chrome-bottom-padding-seekbar post-overlay-vtoggle" value="0" min="0" max="100" step="0.1">
';

$elementcontent['mute-btn']='
<!-- Mute/Unmute button with Bootstrap icons -->
<button id="muteButton" class="mute-btn chrome-bottom-padding-2 post-overlay-vtoggle" onclick="toggleMute()">
<i id="muteIcon" class="bi bi-volume-up-fill"></i>
</button>
';

$elementcontent['countdowntimer']='
<!-- Countdown timer (remaining time) -->
<div class="countdown-timer chrome-bottom-padding-2 post-overlay-vtoggle monotypenumbers" id="countdownTimer">00:00</div>
';

$elementcontent['soundtrack-avatar']='
<!-- Avatar button linking to the soundtrack -->
<div class="soundtrack-avatar post-overlay-vtoggle chrome-bottom-padding-4" id="avatarButton">
<a href="/social/soundtrack?" data-bs-toggle="tooltip" data-bs-placement="top" title="Now playing: ' . htmlspecialchars($randomFile??'') . '">
        <img src="/public/avatars/soundtrack'.(rand(1,7)).'.png" alt="Soundtrack Avatar" class="soundtrack-avatar-icon" id="soundtrackAvatar">
    </a>
</div>
';

$elementcontent['playbutton']='
<!-- Play button overlay -->
<div class="play-button" id="playButton">
<i class="bi bi-play-fill"></i> <!-- Bootstrap Play icon -->
</div>
';


$elementcontent['js-multimediacontroller']="
<script>
$(document).ready(function() {
    var bg_multimediacomponent = document.getElementById('bg_multimediacomponent');
    var playButton = $('#playButton');
    var countdownTimer = $('#countdownTimer');    
    var soundtrackAvatar = $('#soundtrackAvatar');
    var postOverlay = $('.right-panel .post-overlay');
    var rightPanel = $('.right-panel');
    var muteButton = $('#muteButton');
    var muteIcon = $('#muteIcon');
    var seekBar = $('#seekBar');
 var isFirstPlay = true; // Track whether it's the first play event



  // Add an event listener for the 'play' event
    bg_multimediacomponent.addEventListener('play', function() {
        // If it's the first time the video is playing, unmute it
        if (isFirstPlay) {
            bg_multimediacomponent.muted = false; // Unmute the video
            isFirstPlay = false; // Set flag to prevent further unmuting
              updateMuteButton();
        }
    });

     // Ensure bg_multimediacomponent buffering before attempting to play
     bg_multimediacomponent.addEventListener('canplaythrough', function() {
        console.log('Video can play through without buffering');
    });


    // Hide play button when autoplay starts
    bg_multimediacomponent.addEventListener('play', function() {
        playButton.css('opacity', '0'); // Hide the play button visually
        soundtrackAvatar.addClass('rotating-avatar'); // Add the rotation class
    });

    
    // Initialize the button state based on the bg_multimediacomponent's mute state
    updateMuteButton();

    // Mute/Unmute Button Click Event
    $('#muteButton').on('click', function() {
        toggleMute();
    });

    // Function to toggle mute/unmute
    function toggleMute() {
        bg_multimediacomponent.muted = !bg_multimediacomponent.muted; // Toggle the muted property
        updateMuteButton(); // Update button icon accordingly
    }

    // Function to update the mute/unmute button based on the bg_multimediacomponent’s current mute state
    function updateMuteButton() {
        if (bg_multimediacomponent.muted) {
            $(muteIcon).removeClass('bi-volume-up-fill').addClass('bi-volume-mute-fill');
        } else {
            $(muteIcon).removeClass('bi-volume-mute-fill').addClass('bi-volume-up-fill');
        }
    }

    // Update seek bar as the bg_multimediacomponent plays
    bg_multimediacomponent.addEventListener('timeupdate', function() {
        var value = (bg_multimediacomponent.currentTime / bg_multimediacomponent.duration) * 100;
        $('#seekBar').val(value);
    });

    // Seek when user interacts with the seek bar
    $('#seekBar').on('input', function() {
        var seekTo = bg_multimediacomponent.duration * (this.value / 100);
        bg_multimediacomponent.currentTime = seekTo;
    });

    playButton.on('click', function() {
        if (bg_multimediacomponent.paused) {
            bg_multimediacomponent.play();
            playButton.css('opacity', '0');
            soundtrackAvatar.addClass('rotating-avatar');  // Add the rotation class
            postOverlay.css('background', 'rgba(0, 0, 0, 0)');  // Remove overlay background
            rightPanel.css('background-color', '#424242'); // Darker gray background

        } else {
            bg_multimediacomponent.pause();
            playButton.css('opacity', '1');            
            soundtrackAvatar.removeClass('rotating-avatar');  // Remove the rotation class
            postOverlay.css('background', 'rgba(0, 0, 0, 0.5)');  // Restore overlay background
            rightPanel.css('background-color', '#757575'); // Restore original background
        }
    });

    bg_multimediacomponent.addEventListener('play', function() {
        playButton.css('opacity', '0');        
        soundtrackAvatar.addClass('rotating-avatar');  // Add the rotation class
        postOverlay.css('background', 'rgba(0, 0, 0, 0)');  // Remove overlay background
        rightPanel.css('background-color', '#424242'); // Darker gray background
    });

    bg_multimediacomponent.addEventListener('pause', function() {
        playButton.css('opacity', '1');        
        soundtrackAvatar.removeClass('rotating-avatar');  // Remove the rotation class
        postOverlay.css('background', 'rgba(0, 0, 0, 0.5)');  // Restore overlay background
        rightPanel.css('background-color', '#757575'); // Restore original background
    });

    bg_multimediacomponent.addEventListener('timeupdate', function() {
        var remainingTime = bg_multimediacomponent.duration - bg_multimediacomponent.currentTime;
        var minutes = Math.floor(remainingTime / 60);
        var seconds = Math.floor(remainingTime % 60);
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        countdownTimer.text(minutes + ':' + seconds);
    });

    bg_multimediacomponent.addEventListener('ended', function() {
        playButton.css('opacity', '1');        
        soundtrackAvatar.removeClass('rotating-avatar');  // Remove the rotation class
        postOverlay.css('background', 'rgba(0, 0, 0, 0.5)');  // Restore overlay background
        rightPanel.css('background-color', '#757575'); // Restore original background
    });

});
</script>
";