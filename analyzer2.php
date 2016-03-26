<?php

define('SOLR_SERVER_HOSTNAME', 'localhost');
define('SOLR_SERVER_PORT', '8983');
define('SOLR_SERVER_PATH', 'solr/europeana');
define('SOLR_SEARCH_PATH', 'http://localhost:8983/solr/europeana/tvrh/?q=*:*&version=2.2&indent=on&qt=tvrh&tv=true&tv.all=true&f.includes.tv.tf=true&tv.fl=dc_title_txt,dc_description_txt,dcterms_alternative_txt&wt=json&json.nl=map&rows=1000&fl=id&start=');

$options = array(
  'hostname' => SOLR_SERVER_HOSTNAME,
  'port'     => SOLR_SERVER_PORT,
  'path'     => SOLR_SERVER_PATH,
  // 'login'    => SOLR_SERVER_USERNAME,
  // 'password' => SOLR_SERVER_PASSWORD,
);

$client = new SolrClient($options);

$fields = array('title_txt', 'description_txt');
$start = 0;
$done = FALSE;
while (!$done) {
  $docs = fetchRecordsWithStart($start);
  processRecords($docs);
  if ($docs->response->numFound <= $start) {
    $done = TRUE;
  }
  $start += 1000;
}

function fetchRecordsWithStart($start) {
  return json_decode(file_get_contents(SOLR_SEARCH_PATH . $start));
}

function processRecords($docs) {
  $res = array();
  foreach ($docs->termVectors as $docId => $doc) {
    if ($docId == 'uniqueKeyFieldName' || $docId == 'warnings')
      continue;
    $res[$docId] = array('dc_title_txt' => 0.0, 'dc_description_txt' => 0.0, 'dcterms_alternative_txt' => 0.0);
    foreach ($doc as $field => $terms) {
      if ($field == 'uniqueKey')
      	continue;
      $res[$docId][$field] = array();
      foreach ($terms as $term => $tfidf) {
      	$res[$docId][$field] = $tfidf->{'tf-idf'};
      }
    }
  }
  foreach ($res as $docId => $values) {
  	printf("%s,%.8f,%.8f,%.8f\n", $docId, $values['dc_title_txt'], $values['dc_description_txt'], $values['dcterms_alternative_txt']);
  }
}
