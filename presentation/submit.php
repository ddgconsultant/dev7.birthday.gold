<?php
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $presentation = $_POST['presentation'];
    $grouping = $_POST['grouping'];
    $slide_order = $_POST['slide_order'];
    $section_prefix = $_POST['section_prefix'];
    $section_class = $_POST['section_class'];
    $section_tag = $_POST['section_tag'];
    $content = $_POST['content'];
    $speech_script = $_POST['speech_script'];
    $createby = $_POST['createby'];
    $modifyby = $_POST['modifyby'];
    $status = $_POST['status'];

    $sql = "INSERT INTO bg_slides (presentation, grouping, slide_order, section_prefix, section_class, section_tag, content, speech_script, createby, modifyby, status)
    VALUES ('$presentation', '$grouping', '$slide_order', '$section_prefix', '$section_class', '$section_tag', '$content', '$speech_script', '$createby', '$modifyby', '$status')";

    if ($conn->query($sql) === TRUE) {
        echo "New slide created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}