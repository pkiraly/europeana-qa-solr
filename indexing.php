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
  'dc_title_txt' => 'dc:title',
  'dc_description_txt' => 'dc:description',
  'dcterms_alternative_txt' => 'dcterms:alternative',
];

$client = new SolrClient($options);

$file = $argv[1];
echo $file, ' ', date('H:i:s'), "\n";
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
    $doc->addField('dataProvider_s', $obj->{'ore:Aggregation'}[0]->{'edm:dataProvider'}[0]);
    if (is_string($obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0])) {
      $doc->addField('provider_s', $obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0]);
    } else if (isset($obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0]->{'#value'})) {
      $doc->addField('provider_s', $obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0]->{'#value'});
    } else {
      print_r($obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0]);
    }
    $doc->addField('type_s', $obj->{'ore:Proxy'}[0]->{'edm:type'}[0]);
    foreach ($fields as $solrField => $jsonField) {
      $value = getField($jsonField);
      if ($value != '') {
        $doc->addField($solrField, $value);
      }
    }
/*
    if (isset($obj->{'ore:Proxy'}[0]->{'dc:title'})) {
      if (is_string($obj->{'ore:Proxy'}[0]->{'dc:title'}[0])) {
        $doc->addField('dc_title_txt', $obj->{'ore:Proxy'}[0]->{'dc:title'}[0]);
      } else if (isset($obj->{'ore:Proxy'}[0]->{'dc:title'}[0]->{'#value'})) {
        $doc->addField('dc_title_txt', $obj->{'ore:Proxy'}[0]->{'dc:title'}[0]->{'#value'});
      } else {
        print_r($obj->{'ore:Proxy'}[0]->{'dc:title'});
      }
    }
    if (isset($obj->{'ore:Proxy'}[0]->{'dc:description'})) {
      if (is_string($obj->{'ore:Proxy'}[0]->{'dc:description'}[0])) {
        $doc->addField('dc_title_txt', $obj->{'ore:Proxy'}[0]->{'dc:description'}[0]);
      } else if (isset($obj->{'ore:Proxy'}[0]->{'dc:description'}[0]->{'#value'})) {
        $doc->addField('dc_title_txt', $obj->{'ore:Proxy'}[0]->{'dc:description'}[0]->{'#value'});
      } else {
        print_r($obj->{'ore:Proxy'}[0]->{'dc:description'});
      }
      # $doc->addField('dc_description_txt', $obj->{'ore:Proxy'}[0]->{'dc:description'}[0]->{'#value'});
    }
    if (isset($obj->{'ore:Proxy'}[0]->{'dcterms:alternative'})) {
      $doc->addField('dcterms_alternative_txt', $obj->{'ore:Proxy'}[0]->{'dcterms:alternative'}[0]->{'#value'});
    }
*/
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
      printf("So far %d records indexed (%d errors)\n", $i, $errors);
      $client->commit();
    }
  }
}
$client->commit();
printf("%d records indexed (%d errors) -- %s\n", $i, $errors, date('H:i:s'));

function getField($field) {
  global $obj;
  $value = '';

  if (isset($obj->{'ore:Proxy'}[0]->{$field})) {
    if (is_string($obj->{'ore:Proxy'}[0]->{$field}[0])) {
      $value = $obj->{'ore:Proxy'}[0]->{$field}[0];
    } else if (isset($obj->{'ore:Proxy'}[0]->{$field}[0]->{'#value'})) {
      $value = $obj->{'ore:Proxy'}[0]->{$field}[0]->{'#value'};
    } else {
      print_r($obj->{'ore:Proxy'}[0]->{$field});
    }
  }
  return $value;
}

