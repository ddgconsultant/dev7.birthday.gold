<?php
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 
session_start();

$user_id = $_SESSION['user_id']; // Assuming you have a session-based user system
$presentation_set = $_POST['presentation_set'];
$formname = $_POST['formname'];
$formsection = $_POST['formsection'];
$form_data = json_encode($_POST);
$metadata = json_encode($_SERVER);

// Insert form data into bg_slideformdata table
$query = "INSERT INTO bg_slideformdata (user_id, presentation_set, formname, formsection, form_data, metadata) VALUES (?, ?, ?, ?, ?, ?)";
$database->execute($query, [$user_id, $presentation_set, $formname, $formsection, $form_data, $metadata]);

// Store form data in session for later use
$_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST);

// Redirect to next slide
$next_slide_id = isset($_POST['next_slide_id']) ? intval($_POST['next_slide_id']) : 1;
header("Location: index.php?content=$presentation_set&slide_id=$next_slide_id");
exit;
?>
