<?php
/**
 * cp uniqueness-master-setlist.txt setlist.txt
 * change OUTPUT_DIRECTORY!
 */

include_once('solr-ping.php');

define('MAX_THREADS', 1);
define('SET_FILE_NAME', 'multilinguality-setlist.txt');
define('PORT', 8984);
define('COLLECTION', 'qa-2018-08');

if (!isSolrAvailable(PORT, COLLECTION))
  die("Solr is not available");

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
  global $all_parameters;

  if (filesize(SET_FILE_NAME) > 3) {
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
      # echo 'nohup Rscript R/uniqueness.R --inputFile ' . $file . ' ' . $all_parameters . ' >>r-report.log 2>>r-report.log &' . "\n";
      exec('nohup php incremental-index-multilinguality.php ' . $file . ' >>index-report.log 2>>index-report.log &');
    }
  }
}
