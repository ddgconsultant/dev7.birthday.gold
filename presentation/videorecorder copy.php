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
    <title>Video Interview</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        #recordedVideo {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Video Interview - Sales Rep</h1>
        <p>Describe what you think is the ideal customer for Birthday.Gold and what would your 30-second sales pitch be to them?</p>
        <div class="mb-3">
            <label for="videoSource">Video source: </label>
            <select id="videoSource" class="form-select"></select>
            <button id="muteCameraButton" class="btn btn-secondary"><i class="fas fa-eye-slash"></i></button>
            <label for="audioSource">Audio source: </label>
            <select id="audioSource" class="form-select"></select>
            <button id="muteAudioButton" class="btn btn-secondary"><i class="fas fa-microphone-slash"></i></button>
        </div>
        <div class="mb-3">
            <video id="video" width="640" height="480" autoplay muted></video>
            <video id="recordedVideo" width="640" height="480" controls></video>
        </div>
        <div class="mb-3">
            <button id="recordButton" class="btn btn-primary">Record</button>
            <button id="stopButton" class="btn btn-danger" disabled>Stop</button>
            <button id="previewButton" class="btn btn-secondary" style="display: none;">Preview</button>
            <button id="submitButton" class="btn btn-success" style="display: none;">Submit</button>
        </div>
        <input type="hidden" id="csrf_token" value="<?php echo $display->inputcsrf_token('tokenonly'); ?>">
    </div>

    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <?php echo $display->videorecorderJS(); ?>
</body>
</html>
