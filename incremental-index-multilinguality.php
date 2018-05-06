<?php
define('BATCH_SIZE', 500);


$fileName = $argv[1];
$fields = explode(',', trim(file_get_contents('header-multilinguality.csv')));

$in = fopen($fileName, "r");
// $dir = '/projects/pkiraly/2018-03-23/split/uniqueness';
$out = [];
$ln = 1;
$records = [];
$ch = init_curl();

while (($line = fgets($in)) != false) {
  if (strpos($line, ',') != false) {
    if ($ln++ % 1000 == 0) {
      printf("%s/%d %s\n", $fileName, $ln, date('H:i:s'));
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
      update(json_encode($records));
      $records = [];
    }
  }
}
fclose($in);

if (!empty($records)) {
  update(json_encode($records));
}

// foreach ($out as $file => $lines) {
//  file_put_contents($dir . '/' . $file . '.csv', join("", $lines), FILE_APPEND);
// }

echo 'DONE', "\n";

function init_curl() {
  $ch = curl_init('http://localhost:8983/solr/qa-2018-03/update');
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  return $ch;
}

function update($data_string) {
  global $ch;
  // $ch = curl_init('http://localhost:8983/solr/qa-2018-03/update?commit=true');
  // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_HTTPHEADER,
    [
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data_string)
    ]
  );
  $result = curl_exec($ch);
  // echo json_encode($result), "\n";
  $info = curl_getinfo($ch);
  if ($info['http_code'] != 200) {
    print_r($info);
  }
  // curl_close($ch);
}