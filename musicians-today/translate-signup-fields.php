<?php

const TITLE = 'Are you a musician?';
const SUB = 'This server was created specifically for musicians. The idea for this server is to host people that actually make music (and hopefully shares it regularly!), although you don\'t need to be a professional. Please confirm that this is the case (it would be ideal if you have a link or two where we can listen to you).';

const DEEPL_AUTH_KEY = ''; // TODO: fill this!
const TRANSLATE_URL = 'https://api-free.deepl.com/v2/translate';

if (DEEPL_AUTH_KEY)
  die("No auth key is configured for the translation service!\n");

foreach (glob('simple_form.*.yml') as $filename) {
  preg_match('/simple_form\.(.*)\.yml/', $filename, $m);
  $lang = $m[1];

  $ss = 0; $ee = 0;

  echo "$lang: ";

  if ($lang === 'en') {
    write($filename, TITLE, SUB);
    $ss++;
  } else {
    try {
      write($filename, translate(TITLE, $lang), translate(SUB, $lang));
    } catch (Exception $e) {
      $ee++;
      echo $e->getMessage()."\n";
      continue;
    }
  }

  echo "good!\n";
}

echo "Done! $ss successes, $ee errors.\n";

function translate($text, $lang) {
  $data = request(TRANSLATE_URL, ['text' => $text, 'target_lang' => $lang]);
  if (!isset($data->translations[0]->text))
    throw new Exception('Translation not found in response');

  return $data->translations[0]->text;
}

function request($url, $body) {
  $header = 'Content-type: application/x-www-form-urlencoded';
  $header .= "\n" . 'Authorization: DeepL-Auth-Key ' . DEEPL_AUTH_KEY;

  $encoded = http_build_query($body);

  $options = ['http' => ['method' => 'POST', 'header' => $header, 'content' => $encoded]];

  $ctx = stream_context_create($options);
  $response = @file_get_contents($url, false, $ctx);
  if ($response === FALSE) {
    $e = error_get_last();
    throw new Exception('Error in request: '.$e['message']);
  } else
    return json_decode($response);
}

function write($filename, $title, $sub) {
  $escapedTitle = str_replace('"', "\\\"", $title);
  $escapedSub = str_replace('"', "\\\"", $sub);

  if (!copy($filename, $filename.'.TEMP'))
    throw new Exception('Error copying file');

  $h = fopen($filename.'.TEMP', 'r');
  if (!$h)
    throw new Exception('Error opening file for input');
  $h2 = fopen($filename, 'w');
  if (!$h2)
    throw new Exception('Error opening file for output');

  $cur = [];
  while (($line = fgets($h)) !== false) {
    if (preg_match('/^((  )*)([^:]+):/', $line, $m)) {
      $idx = strlen($m[1]) / 2;
      $cur[$idx] = $idx === 0 ? 'lang' : $m[3];
      while (count($cur) > ($idx + 1))
        array_pop($cur);
    }

    if ($cur === ['lang', 'simple_form', 'hints', 'invite_request', 'text'])
      fwrite($h2, "        text: \"$escapedSub\"\n");
    elseif ($cur === ['lang', 'simple_form', 'labels', 'invite_request', 'text'])
      fwrite($h2, "        text: \"$escapedTitle\"\n");
    else
      fwrite($h2, $line);
  }

  fclose($h);
  fclose($h2);

  unlink($filename.'.TEMP');
}
