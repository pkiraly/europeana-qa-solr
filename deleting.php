<?php
define('SOLR_SERVER_HOSTNAME', 'localhost');
define('SOLR_SERVER_PORT', '8983');
define('SOLR_SERVER_PATH', 'solr/europeana');

$options = array(
  'hostname' => SOLR_SERVER_HOSTNAME,
  'port'     => SOLR_SERVER_PORT,
  'path'     => SOLR_SERVER_PATH,
  // 'login'    => SOLR_SERVER_USERNAME,
  // 'password' => SOLR_SERVER_PASSWORD,
);

$client = new SolrClient($options);
$client->deleteByQuery("*:*");
$client->commit();

