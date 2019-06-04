<?php

include_once('solr-ping.php');

define('PORT', 8984);
define('COLLECTION', 'qa-2018-08');
define('SOLR_PATH', '/home/pkiraly/solr-7.2.1');

$endTime = time() + 60;
while (time() < $endTime) {
  if (!isSolrAvailable(PORT, COLLECTION)) {
    restartSolr();
  }
  sleep(1);
}

function restartSolr() {
  echo date("Y-m-d H:i:s"), " restarting Solr (solr-checker)\n";
  exec(sprintf('%s/bin/solr start -p %d -m 512m', SOLR_PATH, PORT));
  sleep(10);
}
