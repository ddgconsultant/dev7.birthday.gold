<?PHP

include($_SERVER['DOCUMENT_ROOT'] . '/social/components/element-mediacontroller.inc');


echo '
<!-- Error Message -->
<div class="error-message" id="errorMessage">Unable to complete the action</div>
';


// Start the right-panel and video container without controls

$videos = [
    'dotsave.app_pinterest_video_downloader_1725809982704.mp4',
    'dotsave.app_pinterest_video_downloader_1725922142310.mp4',
    'dotsave.app_pinterest_video_downloader_1725924984685.mp4',
    'dotsave.app_pinterest_video_downloader_1725925046292.mp4',
    'SampleVideo_1280x720_5mb.mp4',
    'Tutorial.mp4',
    'dotsave.app_pinterest_video_downloader_1725925484754.mp4',
    'dotsave.app_pinterest_video_downloader_1725925355411.mp4',
    'dotsave.app_pinterest_video_downloader_1725925516368.mp4'
];

// Randomly selecting one
$random_video = $videos[array_rand($videos)];

echo '
<div class="right-panel p-0 m-0">




<div class="right-panel p-0 m-0">
    <div style="position: relative; height: 100%;"> <!-- Ensure elements are positioned relative to this container -->
        <video src="https://cdn.birthday.gold/public/videos/'.$random_video .'"  class="video-player"
            style="width: 100% !important; height: 100% !important; object-fit: contain !important;" 
            id="bg_multimediacomponent"   preload="auto" muted autoplay loop playsinline></video>
  


'.$elementcontent['seekbar'].'
'.$elementcontent['mute-btn'].'
'.$elementcontent['countdowntimer'].'
'.$elementcontent['soundtrack-avatar'].'
'.$elementcontent['playbutton'].'
';


// Close the postCarousel and right-panel containers
echo '
    </div>
    </div>
';

include($_SERVER['DOCUMENT_ROOT'] . '/social/components/overlay.inc');
?>
</div>

<?PHP
echo $elementcontent['js-multimediacontroller'];


