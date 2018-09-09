<?php
/**
 * cp uniqueness-master-setlist.txt setlist.txt
 * change OUTPUT_DIRECTORY!
 */

include_once('solr-ping.php');

define('MAX_THREADS', 4);
define('SET_FILE_NAME', 'multilinguality-setlist.txt');
define('SOLR_PATH', '/home/pkiraly/solr-7.2.1');

$params = getopt("", ['port:', 'collection:']);
$errors = [];
$mandatory = ['port', 'collection'];
foreach ($mandatory as $param) {
  if (!isset($params[$param]))
    $errors[] = $param;
}

if (!empty($errors)) {
  die(sprintf("Error! Missing mandatory parameters: %s\n", join(', ', $errors)));
}

if (!isSolrAvailable($params['port'], $params['collection'])) {
  echo date("Y-m-d H:i:s"), "Solr is not available\n";
  exit(1);
}

$endTime = time() + 60;
$i = 1;
while (time() < $endTime) {
  $threads = exec('ps aux | grep "[ ]incremental-index-multilinguality.php" | wc -l');
  // echo 'threads: ', $threads, "\n";
  // echo date('h:i:s', time()), ' ', date('h:i:s', $endTime), "\n";
  if ($threads < MAX_THREADS) {
    if (filesize(SET_FILE_NAME) > 3) {
      launch_threads($threads);
    }
  }
  sleep(1);
}

function launch_threads($running_threads) {
  global $params;

  if (filesize(SET_FILE_NAME) > 3 && isSolrAvailable($params['port'], $params['collection'])) {
    $contents = file_get_contents(SET_FILE_NAME);
    $lines = explode("\n", $contents);
    $files = [];
    $slots = MAX_THREADS - $running_threads;
    for ($i = 1; $i <= $slots; $i++) {
      if (count($lines) > 0) {
        $files[] = array_shift($lines);
      }
    }
    printf("Running threads: %d, slots: %d, new files: %d\n", $running_threads, $slots, count($files));
    $contents = join("\n", $lines);
    file_put_contents(SET_FILE_NAME, $contents);
    foreach ($files as $file) {
      printf("%s launching set: %s, remaining sets: %d\n", date("Y-m-d H:i:s"), $file, count($lines));
      $cmd = sprintf(
        'nohup php incremental-index-multilinguality.php --port %s --collection %s %s >>index-report.log 2>>index-report.log &',
        $file, $params['port'], $params['collection']
      );
      exec($cmd);
    }
  }
}
