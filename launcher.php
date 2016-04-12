<?php
define('MAX_THREADS', 8);
define('SET_FILE_NAME', 'setlist.txt');
$threads = exec('ps aux | grep "[i]ndexing.php" | wc -l');
# echo 'threads: ', $threads, "\n";
if ($threads >= MAX_THREADS) {
  exit();
}

if (filesize(SET_FILE_NAME) > 3) {
  $contents = file_get_contents(SET_FILE_NAME);
  $lines = explode("\n", $contents);
  $sets = [];
  $slots = MAX_THREADS - $threads;
  for ($i = 1; $i <= $slots; $i++) {
    if (count($lines) > 0) {
      $sets[] = array_shift($lines);
    }
  }
  printf("Running threads: %d, slots: %d, new sets: %d\n", $threads, $slots, count($sets));
  $contents = join("\n", $lines);
  file_put_contents('setlist.txt', $contents);
  foreach ($sets as $set) {
    printf("%s launching set: %s\n", date("Y-m-d H:i:s"), $set);
    exec('nohup php indexing.php ' . $set . ' >>indexing-report.txt 2>>indexing-report.txt &');
  }
}

