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

$fields = [
  'dc_title' => 'dc:title',
  'dc_description' => 'dc:description',
  'dcterms_alternative' => 'dcterms:alternative',
];

$client = new SolrClient($options);

$file = $argv[1];
printf("Starting %s -- %s\n", $file, date('H:i:s'));
$target = sprintf('http://localhost:50075/webhdfs/v1/europeana/%s?op=OPEN&namenoderpcaddress=localhost:54310&offset=0', $file);
$handle = fopen($target, 'r');
if ($handle) {
  $i = 0;
  $errors = 0;
  while (($line = fgets($handle)) !== FALSE) {
    $i++;
    $obj = json_decode($line);
    $doc = new SolrInputDocument();
    // echo $obj->identifier, "\n";
    $doc->addField('id', $obj->identifier);
    if (is_string($obj->{'ore:Aggregation'}[0]->{'edm:dataProvider'}[0])) {
      $doc->addField('dataProvider_s', $obj->{'ore:Aggregation'}[0]->{'edm:dataProvider'}[0]);
    } else if (isset($obj->{'ore:Aggregation'}[0]->{'edm:dataProvider'}[0]->{'#value'})) {
      $doc->addField('dataProvider_s', $obj->{'ore:Aggregation'}[0]->{'edm:dataProvider'}[0]->{'#value'});
    } else {
      printf(
        "ERROR. Data provider is not a string. File: %s. data provider: %s\n",
        $file,
        json_encode($obj->{'ore:Aggregation'}[0]->{'edm:dataProvider'}[0])
      );
    }
    if (is_string($obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0])) {
      $doc->addField('provider_s', $obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0]);
    } else if (isset($obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0]->{'#value'})) {
      $doc->addField('provider_s', $obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0]->{'#value'});
    } else {
      print_r($obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0]);
    }
    $doc->addField('type_s', $obj->{'ore:Proxy'}[0]->{'edm:type'}[0]);
    foreach ($fields as $solrField => $jsonField) {
      $values = getField($jsonField);
      if (!empty($values)) {
        foreach ($values as $value) {
          if ($value != "") {
            $doc->addField($solrField . '_txt', $value);
            $doc->addField($solrField . '_ss', $value);
          }
        }
      }
    }
    $doc->addField('collection_s', $obj->{'edm:EuropeanaAggregation'}[0]->{'edm:collectionName'}[0]);
    $doc->addField('language_s', $obj->{'edm:EuropeanaAggregation'}[0]->{'edm:language'}[0]);
    $doc->addField('country_s', $obj->{'edm:EuropeanaAggregation'}[0]->{'edm:country'}[0]);
    try {
      $updateResponse = $client->addDocument($doc);
    } catch(Exception $e) {
      printf("Error: %s, length: %d\n", $e->getMessage(), strlen($line));
      $errors++;
    }
    if ($i % 1000 == 0) {
      printf("%s) So far %d records indexed (%d errors) -- %s\n", $file, $i, $errors, date('H:i:s'));
      $client->commit();
    }
  }
}
$client->commit();
printf("%s) %d records indexed (%d errors) -- %s\n", $file, $i, $errors, date('H:i:s'));

function getField($field) {
  global $obj, $file;
  $values = [];

  if (isset($obj->{'ore:Proxy'}[0]->{$field})) {
    if (is_array($obj->{'ore:Proxy'}[0]->{$field})) {
      foreach ($obj->{'ore:Proxy'}[0]->{$field} as $instance) {
        if (is_string($instance)) {
          $values[] = $instance;
        } else if (isset($instance->{'#value'})) {
          $values[] = $instance->{'#value'};
        } else if (isset($instance->{'@resource'})) {
          printf(
            "ERROR. Unexpected @resource type in file: %s, field %s: %s.\n",
            $file,
            $field,
            json_encode($instance)
          );
        } else {
          echo 'unrecognized instance type', json_encode($instance), "\n";
        }
      }
    } else {
      echo 'unrecognized type: ' . json_encode($obj->{'ore:Proxy'}[0]->{$field}), "\n";
    }
  }

  return $values;
}

