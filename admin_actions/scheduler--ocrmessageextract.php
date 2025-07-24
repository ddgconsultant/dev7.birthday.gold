<?php
$addClasses[] = 'mail';
$addClasses[] = 'ai';
$addClasses[] = 'image';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$batchsize = $_GET['batchsize'] ?? 100;
$timeout = ($batchsize * 45) + 300;
echo '<h1>OCR Message Extract</h1>';
echo '<h2>START: ' . date('Y-m-d H:i:s') . ' -- batch=' . $batchsize . ' / timeout=' . $timeout . '</h2>';
http_response_code(200);
header('Content-Type: text/html');
header('Connection: close');
ob_end_flush();
flush();
if (function_exists('fastcgi_finish_request')) {
  fastcgi_finish_request();
}
set_time_limit($timeout);
ob_implicit_flush(true);
while (ob_get_level() > 0) ob_end_flush();

$messages_response = $mail->getMessagesToExtract($batchsize);
$counter = 0;

foreach ($messages_response['messages'] as $msg) {
  $counter++;
  $id = $msg['message_id'];
  $body = $msg['body'];
  $mailserver = $msg['mailserver'];
  echo '<hr>' . $mailserver . ' | # ' . $counter . ': ' . $id . ': ';
  $summary = strip_tags($body);
  $imgCount = 0;
  $skipped = 0;

  $unreachableCache = [];

  if (stripos($body, '<img') !== false) {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $summary = strip_tags($body);
    $imgs = $doc->getElementsByTagName('img');

    foreach ($imgs as $img) {
      $src = $img->getAttribute('src');
      if (!$src) {
        $skipped++;
        continue;
      }

      // Skip already unreachable image
      if (isset($unreachableCache[$src])) {
        echo "⚠ Skipped (cached unreachable): {$src}\n";
        $skipped++;
        continue;
      }

      $host = parse_url($src, PHP_URL_HOST);
      if (!$host || !checkdnsrr($host, 'A')) {
        echo "⚠ DNS failure: {$host} — skipping {$src}\n";
        $unreachableCache[$src] = true;
        $skipped++;
        flush();
        continue;
      }

      $headers = @get_headers($src, 1);
      if (!is_array($headers) || strpos($headers[0], '200') === false) {
        echo "⚠ Skipped (unreachable): {$src}\n";
        $unreachableCache[$src] = true;
        $skipped++;
        flush();
        continue;
      }

      $context = stream_context_create([
        'http' => ['timeout' => 5],
        'https' => ['timeout' => 5]
      ]);

      $imgData = @file_get_contents($src, false, $context);
      if ($imgData !== false) {
        $tmpfile = tempnam(sys_get_temp_dir(), 'ocr_');
        file_put_contents($tmpfile, $imgData);

        if (filesize($tmpfile) < 2048) {
          unlink($tmpfile);
          $skipped++;
          continue;
        }

        $ocr = $image->extractText($tmpfile);
        unlink($tmpfile);

        if (!empty($ocr['text'])) {
          $summary .= "\n" . trim($ocr['text']);
        }

        $imgCount++;
      } else {
        $unreachableCache[$src] = true;
        $skipped++;
      }
    }

    $final = trim($summary);

    if ($imgCount == 0 && $skipped > 0) {
      $mail->expireMessage($id, $mailserver);
      echo "❌ Message ID {$id} | All image fetches failed — expired.\n";
      flush();
      continue;
    }

  } else {
    $clean = $body;


    $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $clean);
    $clean = preg_replace('/\s*style\s*=\s*"(.*?)"/is', '', $clean);
    $clean = preg_replace("/\s*style\s*=\s*'(.*?)'/is", '', $clean);
    $clean = preg_replace('/<link[^>]+rel=["\']stylesheet["\'][^>]*>/i', '', $clean);
    $clean = preg_replace('/<img[^>]+src=["\']data:image\/[^"\']+["\'][^>]*>/i', '', $clean);
    $clean = preg_replace('/(&nbsp;[\s]*){2,}/i', ' ', $clean);
    $final = strip_tags($clean);
    // 1. Remove multiple chained CSS selector blocks like: a{...}b.class{...}#id{...}
$final = preg_replace('/(?:[#\.\*\w\-\[\]\=\(\)\s,:]+)\{[^{}]*\}(?=\s*[#\.\*\w\-\[\]\=\(\)\s,:]*\{[^{}]*\})*/', '', $final);

// 2. Remove standalone CSS rule blocks left over
$final = preg_replace('/(?:[#\.\*\w\-\[\]\=\(\)\s,:]+)\{[^{}]*\}/', '', $final);

// 3. Remove @media and other at-rules
$final = preg_replace('/@[\w\-]+[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/is', '', $final);

// 4. Remove remaining font-face or nested rule syntax
$final = preg_replace('/@font-face\s*\{[^{}]*\}/is', '', $final);

// 5. Final cleanup
$final = preg_replace('/[ \t]{2,}/', ' ', $final);          // Collapse spaces
$final = preg_replace('/\n{2,}/', "\n", $final);            // Collapse newlines
$final = preg_replace('/^\s+|\s+$/', '', $final);           // Trim
$final = html_entity_decode($final, ENT_QUOTES | ENT_HTML5); // Decode entities

// Remove raw inline CSS blobs like: selector{...}selector2{...}
$final = preg_replace('/(?:[#.\w\s,\*\-\[\]=\"\'\>\+\~:]+)\s*\{[^{}]*\}+/', '', $final);

// Kill leftover @media queries
$final = preg_replace('/@media[^{]+\{(?:[^{}]|\{[^{}]*\})*\}/is', '', $final);

// Strip lingering entities
$final = html_entity_decode($final, ENT_QUOTES | ENT_HTML5);

// Strip invisible junk
$final = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}\x{2060}\x{2800}]/u', '', $final);

// Normalize spacing
$final = preg_replace('/[ \t]+/', ' ', $final);
$final = preg_replace('/\n{2,}/', "\n", $final);
$final = trim($final);

    $final = preg_replace('/(?<!\w)([#.\*\w \[\]\(\)=:"\'\-_,]+)\s*\{[^{}]*\}(?=\s*[#.\*\w \[\]\(\)=:"\'\-_,]*\{)?/m', '', $final);
    while (preg_match('/([#.\*\w \[\]\(\)=:"\'\-_,]+)\s*\{[^{}]*\}/m', $final)) {
      $final = preg_replace('/([#.\*\w \[\]\(\)=:"\'\-_,]+)\s*\{[^{}]*\}/m', '', $final);
    }
    $final = preg_replace('/@media[^{]+\{(?:[^{}]|\{[^{}]*\})*\}/is', '', $final);
    $final = str_replace(["\r\n", "\r"], "\n", $final);
    $final = preg_replace("/\n{2,}/", "\n", $final);
    $final = preg_replace('/[ \t]+/', ' ', $final);
    $final = preg_replace('/(?:(^|\s))([#\.\w\*\s,\[\]="\'\-:>]+)\s*\{[^{}]+\}\s*/m', ' ', $final);
    $final = preg_replace('/@media[^{]+\{(?:[^{}]|\{[^{}]*\})*\}/is', ' ', $final);
    $final = html_entity_decode($final, ENT_QUOTES | ENT_HTML5);
    $final = preg_replace('/[^\S\r\n]{2,}/', ' ', $final);
    $final = preg_replace('/\n{2,}/', "\n", $final);
    $final = preg_replace('/\x{200B}|\x{200C}|\x{200D}|\x{FEFF}/u', '', $final);
    $final = preg_replace('/[\x{00AD}\x{2060}\x{2800}]/u', '', $final);
    $final = trim($final);
    $final = preg_replace([
      '/(?:[#\.\*\w\-\[\]\=\(\)\s,:@]+)\{[^{}]*\}(?=\s*[#\.\*\w\-\[\]\=\(\)\s,:@]*\{[^{}]*\})*/',
      '/@media[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/is'
  ], '', $final);

    require_once($dir['classes']. '/class.stringsanitizer.php');    
    // Convert to plain text
    $final = StringSanitizer::emailToPlainText($final);

    $final = trim($final);

  }

  $maxLength = 64000;
  if (strlen($final) > $maxLength) {
    $final = substr($final, 0, $maxLength - 3) . '...';
    echo "⚠ Truncated extract for message ID {$id} to {$maxLength} chars\n";
  }

  $resultx = $mail->updateExtractField($id, $mailserver, $final);
  echo "✓ Message ID {$id} | Extracted: {$imgCount} image(s) | Skipped: {$skipped} | Length: " . strlen($final) . ' = ' . ($resultx ? 'updated' : 'failed') . "\n";
  flush();
}

echo "Batch complete.\n";
echo '<h2>END: ' . date('Y-m-d H:i:s') . '</h2>';
flush();
