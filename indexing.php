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

// $files = array('00000000.json', '00001000.json', 'individuals.json');
$files = array("full.json");

$dir = '/home/kiru/data/europeana-oai-pmh/00743_A_DE_Landesarchiv_ese_6_0000002523/';
foreach ($files as $file) {
  echo $file, "\n";
  $target = sprintf('http://peter-gwdg:50075/webhdfs/v1/europeana/%s?op=OPEN&namenoderpcaddress=localhost:54310&offset=0', $file);
  // $handle = fopen($dir . $file, 'r');
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
      $doc->addField('provider_s', $obj->{'ore:Aggregation'}[0]->{'edm:provider'}[0]);
      $doc->addField('type_s', $obj->{'ore:Proxy'}[0]->{'edm:type'}[0]);
      if (isset($obj->{'ore:Proxy'}[0]->{'dc:title'})) {
        $doc->addField('title_txt', $obj->{'ore:Proxy'}[0]->{'dc:title'}[0]->{'#value'});
      }
      if (isset($obj->{'ore:Proxy'}[0]->{'dc:description'})) {
        $doc->addField('description_txt', $obj->{'ore:Proxy'}[0]->{'dc:description'}[0]->{'#value'});
      }
      $doc->addField('collection_s', $obj->{'edm:EuropeanaAggregation'}[0]->{'edm:collectionName'}[0]);
      $doc->addField('language_s', $obj->{'edm:EuropeanaAggregation'}[0]->{'edm:language'}[0]);
      $doc->addField('country_s', $obj->{'edm:EuropeanaAggregation'}[0]->{'edm:country'}[0]);
      // $doc->addField('json_st', $line);
      // print_r($doc);
    // $doc->addField('cat', 'Software');
    // $doc->addField('cat', 'Lucene');
      try {
        $updateResponse = $client->addDocument($doc);
      } catch(Exception $e) {
        printf("Error: %s, length: %d\n", $e->getMessage(), strlen($line));
        $errors++;
        // print_r($doc->toArray());

        // print_r($e);
      }
      if ($i % 100 == 0) {
        printf("So far %d records indexed (%d errors)\n", $i, $errors);
        $client->commit();
      }
    }
  }
  $client->commit();
}
printf("%d records indexed (%d errors)\n", $i, $errors);

// you will have to commit changes to be written if you didn't use $commitWithin

// print_r($updateResponse->getResponse());
