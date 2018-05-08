<?php

/**
 * Is Solr available?
 */
function isSolrAvailable() {
  static $solrPingContext;
  if (!isset($solrPingContext)) {
    $solrPingContext = stream_context_create(['http' => ['method' => 'GET', 'header' => 'Content-Type: application/json']]);
  }

  $solrIsAvailable = FALSE;
  try {
    $result = @file_get_contents(
      'http://localhost:8983/solr/qa-2018-03/admin/ping',
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
  return $solrIsAvailable;
}
