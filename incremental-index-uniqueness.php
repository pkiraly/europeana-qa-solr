<?php
define('BATCH_SIZE', 500);
include_once('solr-ping.php');

$fileName = $argv[1];
$firstLine = 0;
$fields = explode(',', trim(file_get_contents('header-uniqueness.csv')));

$in = fopen($fileName, "r");
// $dir = '/projects/pkiraly/2018-03-23/split/uniqueness';
$out = [];
$ln = 1;
$records = [];
$ch = init_curl();

$start = microtime(TRUE);
$indexTime = 0.0;
while (($line = fgets($in)) != false) {
  if (strpos($line, ',') != false) {
    $ln++;

    if ($ln < $firstLine)
      continue;

    if ($ln % 1000 == 0) {
      $totalTime = microtime(TRUE) - $start;
      printf("%s/%d %s (took: %.2f/%.2f - %.2f%%)\n", $fileName, $ln, date('H:i:s'), $totalTime, $indexTime, ($indexTime/$totalTime)*100);
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
      else if ($i > 2)
        $record->{$fields[$i]} = (object)["set" => $row[$i]];
    }

    $records[] = $record;

    if (count($records) == BATCH_SIZE) {
      if (isSolrAvailable()) {
        $updateStart = microtime(TRUE);
        update(json_encode($records));
        $indexTime += (microtime(TRUE) - $updateStart);
        $records = [];
      } else {
        echo 'Solr is not available', "\n";
        break;
      }
    }
  }
}
fclose($in);

if (isSolrAvailable()) {
  if (!empty($records)) {
    update(json_encode($records));
  }
  commit();
}

// foreach ($out as $file => $lines) {
//  file_put_contents($dir . '/' . $file . '.csv', join("", $lines), FILE_APPEND);
// }

curl_close($ch);
echo 'DONE', "\n";

function init_curl() {
  $ch = curl_init('http://localhost:8983/solr/qa-2018-03/update');
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

function commit() {
  global $ch;
  $data_string = '<commit/>';
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_HTTPHEADER,
    [
      'Content-Type: text/xml',
      'Content-Length: ' . strlen($data_string)
    ]
  );
  $result = curl_exec($ch);
  $info = curl_getinfo($ch);
  if ($info['http_code'] != 200) {
    print_r($info);
    print $result;
  }
}
