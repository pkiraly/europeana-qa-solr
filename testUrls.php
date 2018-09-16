<?php

$urls = [
  'completeness' => 'http://localhost:8984/solr/qa-2018-08/select?q=*:*%20NOT%20collection_i:[*%20TO%20*]',
  'multilinguality' => 'http://localhost:8984/solr/qa-2018-08/select?q=*:*%20NOT%20provider_dc_title_taggedliterals_f:[*%20TO%20*]',
  'languages' => 'http://localhost:8984/solr/qa-2018-08/select?q=*:*%20NOT%20distinctlanguages_in_providerproxy_f:[*%20TO%20*]'
];

foreach ($urls as $feature => $url) {
  $response = json_decode(file_get_contents($url . '&rows=1'));
  printf("%s: %d\n", $feature, $response->response->numFound);
}
