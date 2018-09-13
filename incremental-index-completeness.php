<?php
include_once('solr-ping.php');

define('BATCH_SIZE', 100);
define('COMMIT_SIZE', 500);

$long_opts = ['port:', 'collection:', 'file:', 'with-check'];
$params = getopt("", $long_opts);
$fileName = $argv[1];
$errors = [];
foreach ($long_opts as $param) {
  if (preg_match('/:$/', $param)) {
    $param = str_replace(':', '', $param);
    if (!isset($params[$param]))
      $errors[] = $param;
  }
}

$doSolrCheck = isset($params['with-check']) && $params['with-check'] == 'true';

echo "doSolrCheck? ", (int)$doSolrCheck, "\n";

if (!empty($errors)) {
  die(sprintf("Error! Missing mandatory parameters: %s\n", join(', ', $errors)));
}

$check_url_pattern = sprintf(
  'http://localhost:%d/solr/%s/select?q=id:%%22%%s%%22&fq=collection_i:[*%%20TO%%20*]&rows=0',
  $params['port'], $params['collection']
);
$update_url = sprintf('http://localhost:%d/solr/%s/update', $params['port'], $params['collection']);
$luke_url = sprintf('http://localhost:%d/solr/%s/admin/luke', $params['port'], $params['collection']);
$commit_url = sprintf('http://localhost:%s/solr/%s/update?commit=true', $params['port'], $params['collection']);

$firstLine = 0;
$fields = explode(',', trim(file_get_contents('header-completeness.csv')));

$in = fopen($params['file'], "r");
$out = [];
$ln = 1;
$records = [];
$ch = init_curl();

while (!isSolrAvailable($params['port'], $params['collection'])) {
  sleep(10);
}

$batch_sent = 0;
$start = microtime(TRUE);
$indexTime = 0.0;
while (($line = fgets($in)) != false) {
  if (strpos($line, ',') != false) {
    $ln++;

    if ($ln < $firstLine)
      continue;

    if ($ln % 1000 == 0) {
      $totalTime = microtime(TRUE) - $start;
      printf("%s %s/%d (took: %.2f/%.2f - %.2f%%)\n", date('H:i:s'), $params['file'], $ln, $totalTime, $indexTime, ($indexTime/$totalTime)*100);
      $start = microtime(TRUE);
      $indexTime = 0.0;
    }
    $line = trim($line);
    $row = str_getcsv($line);
    $record = new stdClass();
    // echo $row[0], "\n";
    for ($i = 0; $i < count($row); $i++) {
      if ($i == 0)
        $record->id = $row[$i];
      else {
        if ($doSolrCheck)
          $record->{$fields[$i]} = (object)["set" => $row[$i]];
        else if ($i > 2)
          $record->{$fields[$i]} = (object)["set" => $row[$i]];
      }
    }

    if ($doSolrCheck)
      echo sprintf("%s: %d", $record->id, isRecordMissingFromSolr($record->id));
    continue;

    if (!$doSolrCheck || isRecordMissingFromSolr($record->id))
      $records[] = $record;

    if (count($records) == BATCH_SIZE) {
      while (!isSolrAvailable($params['port'], $params['collection'])) {
        sleep(10);
      }
      $updateStart = microtime(TRUE);
      update(json_encode($records));
      $indexTime += (microtime(TRUE) - $updateStart);
      $records = [];
      if ($batch_sent++ % COMMIT_SIZE == 0) {
        commit();
      }
    }
  }
}
fclose($in);

while (!isSolrAvailable($params['port'], $params['collection'])) {
  sleep(10);
}

if (!empty($records)) {
  update(json_encode($records));
}
commit(TRUE);

curl_close($ch);
echo 'DONE', "\n";

function init_curl() {
  global $update_url;

  $ch = curl_init($update_url);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  return $ch;
}

function update($data_string) {
  global $ch;

  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string)
  ]);
  $result = curl_exec($ch);
  $info = curl_getinfo($ch);
  if ($info['http_code'] != 200) {
    print_r($info);
    print $result;
  }
}

function commit($forced = FALSE) {
  global $luke_url, $commit_url, $params;

  $allowed = TRUE;
  if (!$forced) {
    $allowed = FALSE;
    $luke_response = json_decode(file_get_contents($luke_url));
    $last_commit_timestamp = (int)($luke_response->index->userData->commitTimeMSec / 1000);
    if (time() > ($last_commit_timestamp + (5 * 60))) {
      $allowed = TRUE;
    } else {
      printf("%s Last commit was within 5 minutes (%s)\n", date('H:i:s'), date('H:i:s', $last_commit_timestamp));
    }
  }
  if ($allowed) {
    $ch = curl_init($commit_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    if ($info['http_code'] != 200) {
      print_r($info);
      print $result;
    } else {
      printf("%s %s committed\n", date('H:i:s'), $params['file']);
      sleep(5);
    }
    curl_close($ch);
  }
}

function isRecordMissingFromSolr($id) {
  global $check_url_pattern;
  $response = json_decode(file_get_contents(sprintf($check_url_pattern, $id)));
  return $response->response->numFound == 0;
}