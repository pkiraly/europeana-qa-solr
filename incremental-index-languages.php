<?php
include_once('solr-ping.php');

define('CHECK_SIZE', 25);
define('BATCH_SIZE', 25);
define('COMMIT_SIZE', 500);

$long_opts = ['port:', 'collection:', 'file:', 'with-check', 'firstline::'];
$params = getopt("", $long_opts);
$errors = [];
foreach ($long_opts as $param) {
  if (preg_match('/\w:$/', $param)) {
    $param = str_replace(':', '', $param);
    if (!isset($params[$param]))
      $errors[] = $param;
  }
}

if (!empty($errors)) {
  die(sprintf("Error! Missing mandatory parameters: %s\n", join(', ', $errors)));
}
$doSolrCheck = isset($params['with-check']);
$firstLine = isset($params['firstline']) ? $params['firstline'] : 0;

$solr_base_url = sprintf('http://localhost:%d/solr/%s', $params['port'], $params['collection']);
$update_url = $solr_base_url . '/update';
$luke_url = $solr_base_url . '/admin/luke';
$commit_url = $solr_base_url . '/update?commit=true';

$fields = explode(',', trim(file_get_contents('header-languages.csv')));

$in = fopen($params['file'], "r");
$out = [];
$ln = 0;
$limbo = [];
$records = [];
$ch = init_curl();

while (!isSolrAvailable($params['port'], $params['collection'])) {
  sleep(10);
}

$batch_sent = 0;
$start = microtime(TRUE);
$indexTime = 0.0;
$existing = $missing = 0;
while (($line = fgets($in)) != false) {
  if (strpos($line, ',') != false) {
    $ln++;

    if ($ln < $firstLine)
      continue;

    $line = trim($line);
    $row = str_getcsv($line);
    $record = new stdClass();
    $record_languages = [];
    // echo $row[0], "\n";
    for ($i = 0; $i < count($row); $i++) {
      if ($i == 0) {
        $record->id = $row[$i];
      } else if ($i == 1) {
        // $record->dataset_i = $row[$i];
      } else if ($i == 2) {
        // $record->dataProvider_i = $row[$i];
      } else if ($i == 3) {
        // $record->provider_i = $row[$i];
      } else if ($i == 4) {
        // $record->country_i = $row[$i];
      } else if ($i == 5) {
        // $record->language_i = $row[$i];
      } else if ($i > 5) {
        $values = explode(';', $row[$i]);
        $field_languages = [];
        foreach ($values as $value) {
          list($language, $count) = explode(':', $value);
          if ($language != '_1') {
            # $field = sprintf("%s_%s_i", $fields[$i], trim($language));
            # $record->{$field} = (object)["set" => (int)$count];
            if ($language != '_0') {
              $record_languages[$language] = 1;
              $field_languages[] = $language;
            }
          }
        }
        if (!empty($field_languages))
          $record->{'lang_' . $fields[$i] . '_ss'} = (object)["set" => $field_languages];
      }
    }
    if (!empty($record_languages)) {
      $record->{'languages_ss'} = (object)["set" => array_keys($record_languages)];
    } else {
      $record->{'languages_ss'} = (object)["set" => ['NONE']];
    }

    if ($doSolrCheck) {
      $limbo[$record->id] = $record;
      if (count($limbo) % CHECK_SIZE == 0) {
        $missing_records = filterRecordsMissingFromSolr($limbo);
        $records = array_merge($records, $missing_records);
        $missing += count($missing_records);
        $existing += (CHECK_SIZE - count($missing_records));
        $limbo = [];
      }
    } else {
      $records[] = $record;
    }

    if (count($records) >= BATCH_SIZE) {
      while (!isSolrAvailable($params['port'], $params['collection'])) {
        sleep(10);
      }
      $updateStart = microtime(TRUE);
      update(json_encode($records));
      $indexTime += (microtime(TRUE) - $updateStart);
      $records = [];
      if ($batch_sent++ % COMMIT_SIZE == 0) {
        // commit();
      }
    }

    if ($ln % 1000 == 0) {
      $totalTime = microtime(TRUE) - $start;
      printf("%s %s/%d (took: total %.2f/indexing %.2f - %.2f%%)\n", date('H:i:s'), $params['file'], $ln, $totalTime, $indexTime, ($indexTime/$totalTime)*100);
      printf("total: %d, existing: %d, missing: %d (%.2f%%), in limbo: %d\n", $ln, $existing, $missing, ($missing * 100 / $ln), count($limbo));
      $start = microtime(TRUE);
      $indexTime = 0.0;
    }
  }
}
fclose($in);

if ($doSolrCheck && !empty($limbo)) {
  $missing_records = filterRecordsMissingFromSolr($limbo);
  $records = array_merge($records, $missing_records);
  $missing += count($missing_records);
  $existing += CHECK_SIZE - count($missing_records);
}

printf("total: %d, existing: %d vs missing: %d (%.2f%%)\n", $ln, $existing, $missing, ($missing * 100 / $ln));

while (!isSolrAvailable($params['port'], $params['collection'])) {
  sleep(10);
}

if (!empty($records)) {
  update(json_encode($records));
}
// commit(TRUE);

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
  global $solr_base_url;
  $response = json_decode(file_get_contents($solr_base_url . '/select?q=id:%22' . $id . '%22&fq=collection_i:[*%20TO%20*]&rows=0'));
  return $response->response->numFound == 0;
}

function filterRecordsMissingFromSolr($records) {
  global $solr_base_url;

  $field = 'languages_ss';
  $all_ids = array_keys($records);

  while (count($all_ids) > 0) {
    $records_to_process = [];
    $ids = '';
    do {
      if ($ids != '')
        $ids .= urlencode(' OR ');
      $id = array_shift($all_ids);
      $ids .= urlencode(sprintf('"%s"', $id));
      $records_to_process[] = $id;
    } while (strlen($ids) < 7000 && count($all_ids) > 0);

    $count = count($records_to_process);

    $query = 'q=id:(' . $ids . ')&fq=' . $field . ':[*%20TO%20*]&fl=id&rows=' . $count;
    $url = $solr_base_url . '/select?' . $query;
    $response = json_decode(file_get_contents($url));
    if (!is_object($response)) {
      echo 'URL: ', $url, "\n";
    } else {
      if ($response->response->numFound == $count)
        return [];

      foreach ($response->response->docs as $doc) {
        unset($records[$doc->id]);
      }
    }
  }


  return array_values($records);
}