<?php
$addClasses[] = 'mail';
$addClasses[] = 'ai';
$addClasses[] = 'image';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
$additionalstyles = '';
$display_footertype = '';
$message_id = $_REQUEST['message_id'] ?? '';
$user_id = $_REQUEST['user_id'] ?? '';
$mail_server = $_REQUEST['mail_server'] ?? '';
$output = '';
#$ai->setDebug(true); 
#breakpoint($ai->test());
#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted('GET')) {
  if ($message_id && $user_id && $mail_server) {

    $message = $mail->getmessage($message_id, $mail_server);
    $output .= '<h5>Original Message:</h5><pre>' . print_r($message, true) . '</pre>';

    if (!empty($message['body'])) {
      $doc = new DOMDocument();
      libxml_use_internal_errors(true);
      $doc->loadHTML($message['body'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
      libxml_clear_errors();

      $imgs = $doc->getElementsByTagName('img');
      $ocr_summary = '';

      foreach ($imgs as $img) {
        $src = $img->getAttribute('src');
        if ($src) {
          $output .= '<h5>Image Source:</h5><div><img src="' . htmlspecialchars($src) . '" style="max-width:200px"><br>' . htmlspecialchars($src) . '</div>';

          // Download image temporarily for OCR
          $tmpfile = tempnam(sys_get_temp_dir(), 'ocr_');
          file_put_contents($tmpfile, file_get_contents($src));

          $text = $image->extractText($tmpfile);
          unlink($tmpfile);

          $ocr_summary .= $text['text'] . "\n";
          $output .= '<h5>Extracted Text:</h5><pre>' . htmlspecialchars($text['text']) . '</pre>';
        }
      }

      if (!empty(trim($ocr_summary))) {

        // Remove multiple spaces and clean special characters
// Remove special chars but keep line breaks
$cleaned = preg_replace('/[^A-Za-z0-9 .,!?\-\r\n]/', '', $ocr_summary);
// Normalize multiple spaces (but not line breaks)
$cleaned = preg_replace('/[^\S\r\n]+/', ' ', $cleaned);
// Trim each line
$cleaned = implode("\n", array_map('trim', explode("\n", $cleaned)));

        #$prompt = 'Summarize this marketing message in 1-2 sentences, capturing the main promotional offer and any important dates or conditions:\n' . $ocr_summary;
        $prompt = "Summarize this marketing message in 1-2 sentences, capturing the main promotional offer and any important dates or conditions:\n\n" . trim($cleaned);

        $output .= '<h5>Prompt Sent to AI:</h5><pre>' . htmlspecialchars($prompt) . '</pre>';
        $ai->setDebug(false); 
#$ai_result=$ai->test();
        $ai_summary = $ai->summarizeText($prompt);
      #  breakpoint($ai_summary);
     #   $ai_summary = $ai_result['summary'] ?? $ai_result['text'] ?? 'No summary generated.';
        $output .= '<h5>AI Summary:</h5><pre>' . htmlspecialchars($ai_summary) . '</pre>';
        $output .= '<h5>AI Raw Response:</h5><pre>' . print_r($ai_result, true) . '</pre>';

      }
    }
  }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '<style></style>';
echo '    
<div class="container main-content mt-0 pt-0">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Test Message Summary</h2>
    <a href="/admin" class="btn btn-sm btn-outline-secondary">Back to Admin</a>
  </div>

  <div class="card">
    <div class="card-body">
      ' . $output . '
    </div>
  </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
exit;
