<?php

return array (
  'tesseract' => 
  array (
    'enabled' => true,
    'path' => 'C:/Program Files/Tesseract-OCR/tesseract.exe',
    'version' => 'Unknown',
    'languages' => 
    array (
      0 => 'eng',
    ),
    'default_language' => 'eng',
  ),
  'nsfw' => 
  array (
    'enabled' => true,
    'providers' => 
    array (
      'local' => 
      array (
        'enabled' => true,
        'threshold' => 0.7,
      ),
      'api' => 
      array (
        'enabled' => false,
        'provider' => '',
        'api_key' => '',
      ),
    ),
  ),
  'paths' => 
  array (
    'temp' => 'W:/BIRTHDAY_SERVER/dev7.birthday.gold/temp/image_processing',
    'logs' => 'W:/BIRTHDAY_SERVER/dev7.birthday.gold/logs/image_processing',
  ),
  'processing' => 
  array (
    'max_file_size' => 10485760,
    'allowed_types' => 
    array (
      0 => 'image/jpeg',
      1 => 'image/png',
      2 => 'image/gif',
      3 => 'image/webp',
    ),
  ),
  'system' => 
  array (
    'os' => 'windows',
    'php_version' => '8.1.11',
    'generated' => '2025-07-19 09:32:17',
  ),
);
