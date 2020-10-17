<?php

/**
 * Is Solr available?
 */
function isSolrAvailable($port = 8983, $collection = 'v2019-08') {
  static $solrPingContext;
  // echo date("Y-m-d H:i:s"), "> is solr available? port: $port, collection: $collection\n";

  if (!isset($solrPingContext)) {
    $solrPingContext = stream_context_create(['http' => ['method' => 'GET', 'header' => 'Content-Type: application/json']]);
  }

  $solrIsAvailable = FALSE;
  try {
    $result = @file_get_contents(
      sprintf('http://localhost:%d/solr/%s/admin/ping', $port, $collection),
      null,
      $context
    );
    if ($result !== FALSE) {
      if (isset($http_response_header) && $http_response_header[0] == 'HTTP/1.1 200 OK') {
        $parsed = json_decode($result);
        if ($parsed->status == 'OK') {
          $solrIsAvailable = TRUE;
        }
      }
    }
  } catch (Exception $e) {
    $solrIsAvailable = FALSE;
  }
  // echo "result: $solrIsAvailable\n";
  return $solrIsAvailable;
}
