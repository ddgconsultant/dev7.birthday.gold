<?php



echo '
<!-- Error Message -->
<div class="error-message" id="errorMessage">Unable to complete the action</div>
';

// Start the right-panel and carousel container
echo '
<div class="right-panel p-0 m-0">
';



include($_SERVER['DOCUMENT_ROOT'] . '/social/components/element-text.inc');

include($_SERVER['DOCUMENT_ROOT'] . '/social/components/element-mediacontroller.inc');

$elementcontent['text']=str_replace(' chrome-bottom-padding-carousel ', ' chrome-bottom-padding-carousel-audio ', $elementcontent['text']);

echo $elementcontent['text'];



// Define the audio files in an array
$audioFiles = [
    'Epic.mp3',
    'File.mp3',
    'Glorious_Day_LONG.mp3',
    'file_example_MP3_5MG.mp3',
    'ambient-design-179116.mp3',
  # 'Main track.mp3',
  #  'Put the Good Stuff In.mp3'
];

// Randomly select an audio file
$randomFile = $audioFiles[array_rand($audioFiles)];



      echo '  
'.$elementcontent['seekbar'].'
'.$elementcontent['mute-btn'].'
'.$elementcontent['countdowntimer'].'
'.$elementcontent['soundtrack-avatar'].'
'.$elementcontent['playbutton'].'
';



// Determine the MIME type based on the file extension
$extension = pathinfo($randomFile, PATHINFO_EXTENSION);

$mimeType = 'audio/mpeg'; // Default to mp3 MIME type
if ($extension === 'wav') {
    $mimeType = 'audio/wav';
}

// Output the selected file in the audio source
echo '<audio id="bg_multimediacomponent" autoplay loop>
    <source src="https://cdn.birthday.gold/public/audios/' . urlencode($randomFile) . '" type="' . $mimeType . '"  >
    Your browser does not support the audio element.
</audio>';


// Close the postCarousel and right-panel containers
echo '
</div>
';



include($_SERVER['DOCUMENT_ROOT'] . '/social/components/overlay.inc');
?>


</div>
</div>

<?PHP 
echo $elementcontent['js-text'];


echo $elementcontent['js-multimediacontroller'];



