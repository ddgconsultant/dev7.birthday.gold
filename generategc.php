<?php #include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


/* $recipient=$session->get('generate_gc_to', '');
$giftcode=$session->get('generate_gc_code', $_REQUEST['q'] );
$message =$session->get('generate_gc_message', '');
$format=$session->get('generate_gc_format', 'pdf'); */


$data = base64_decode($_GET['data']);
parse_str($data, $gencert);

$recipient = $gencert['generate_gc_to'] ?? "";
$giftcode = $gencert['generate_gc_code'] ?? "NO CODE PROVIDED";
$message = $gencert['generate_gc_message'] ?? "";
$format = $gencert['generate_gc_format'] ?? "pdf";



/* $giftcode = "ABC123XYZ"; // Replace with your dynamic value
$recipient = "John Doe"; // Replace with your dynamic value
$message = "Happy Birthday! Enjoy your special day!"; // Replace with your dynamic value */
$creationDateTime = date("siH-dmY"); // Get current date and time
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body {
        font-family: 'Arial, sans-serif';
        background-color: #fff;
        color: #333;
    }
    .certificate {
        max-width: 1080px;
        margin: 20px auto;
        padding: 20px;
        background-color: #fff;
        border: 1px solid #aaa;
        border-radius: 15px;
        text-align: center;
        position: relative;
    }
    .certificate h1 {
        font-size: 2.5em;
        margin-bottom: 20px;
    }
    .certificate p {
        margin-bottom: 20px;
        font-size: 1.2em;
    }
    .certificate .fine-print {
        position: absolute;
        bottom: 0px;
        left: 20px;
        font-size: 0.8em;
        color: #aaa;
        font-family: monospace;
    }
    .certificate .redeem {
        font-size: 1.2em;
        color: navy;
    }
    .banner, .footer-banner {
        width: 100%;
        border-radius: 15px 15px 0 0;
    }
    .footer-banner {
        position: absolute;
        bottom: 0;
        border-radius: 0 0 15px 15px;
    }
</style>
</head>
<body>
<div class="certificate">
    <img src="/public/images/system/gcimage.jpg" alt="GCImage" class="banner">
    <p><strong>Your Gift Code:</strong> <?php echo htmlspecialchars($giftcode); ?></p>
<?PHP
if (!empty($recipient)) {  echo '<p><strong>To:</strong> ' . htmlspecialchars($recipient) . '</p>';}
if (!empty($message)) {  echo '<p>' . htmlspecialchars($message) . '</p><hr>';}
?>
    <p class="redeem"><i>Redeem your Lifetime Plan at: <a href="https://birthday.gold/redeem" target="_blank">https://birthday.gold/redeem</a></i></p>
    <p class="fine-print"><?php echo $creationDateTime; ?></p>
</div>
</body>
</html>
