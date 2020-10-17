<?php

include_once('solr-ping.php');

define('PORT', 8984);
define('COLLECTION', 'v2020-08');
define('SOLR_PATH', '/home/pkiraly/solr-8.2.0');

$endTime = time() + 60;
while (time() < $endTime) {
  if (!isSolrAvailable(PORT, COLLECTION)) {
    restartSolr();
  }
  sleep(1);
}

function restartSolr() {
  echo date("Y-m-d H:i:s"), " restarting Solr (solr-checker)\n";
  exec(sprintf('%s/bin/solr start -p %d -m 2g', SOLR_PATH, PORT));
  sleep(10);
}
