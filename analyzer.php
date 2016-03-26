<?php
define('LN', "\n");

define('BASE_URL', "http://localhost:8983/solr/europeana");
define('TERMS', BASE_URL . '/terms?wt=json&terms.limit=1&terms.sort=index&terms.fl=%s&terms.prefix=%s');
define('ANALYSIS', BASE_URL . '/analysis/field?analysis.fieldname=%s&wt=json&analysis.fieldvalue=%s');

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

/*
$cursorMark = '*';
$done = FALSE;
while (!$done) {
  $docs = fetchRecords($cursorMark);
  processRecords($docs);
  if ($cursorMark == $docs->nextCursorMark) {
    $done = TRUE;
  }
  $cursorMark = $docs->nextCursorMark;
}
*/

/*
for ($i = 0; $i < 10; $i++) {
  $docs = json_decode(file_get_contents(BASE_URL . '/select?q=*:*&wt=json&indent=true&fl=id,title_txt,description_txt&rows=1000&start=' . ($i*1000)));

  $N = $docs->response->numFound;
  foreach ($docs->response->docs as $doc) {
    // echo $doc->id, "\n";
    foreach ($fields as $field) {
      if (isset($doc->{$field})) {
        foreach ($doc->{$field} as $value) {
          $terms = getTerms($field, $value);
          $total = getWeights($field, $terms, $N);
          // printf("\t%s: %s ==> %s\n", $field, $value, $total);
          printf("%s ==> %s\n", (int)($total*100), $value);
        }
      }
    }
  }
}
*/

function processRecords($docs) {
  global $fields;
  $N = $docs->response->numFound;
  foreach ($docs->response->docs as $doc) {
    // echo $doc->id, "\n";
    foreach ($fields as $field) {
      if (isset($doc->{$field})) {
        foreach ($doc->{$field} as $value) {
          $terms = getTerms($field, $value);
          $total = getWeights($field, $terms, $N);
          // printf("\t%s: %s ==> %s\n", $field, $value, $total);
          printf("%s ==> %s\n", (int)($total*100), $value);
        }
      }
    }
  }
}

function fetchRecordsWithStart($start = 0) {
  return json_decode(file_get_contents(BASE_URL . '/select?q=*:*&wt=json&indent=true&fl=id,title_txt,description_txt&rows=1000&start=' . $start));
}

function fetchRecordsWithCursor($cursorMark = '*') {
  return json_decode(file_get_contents(BASE_URL . '/select?q=*:*&wt=json&indent=true&fl=id,title_txt,description_txt&rows=1000&cursorMark=' . $cursorMark));
}

function getWeights($field, $terms, $N) {
  $total = 0.0;
  foreach ($terms as $term => $term_frequency) {
    $doc_frequency = getDocFrequency($field, $term);
    $weight = getWeight($term_frequency, $doc_frequency, $N);
    // printf("%s %s (%d, %d)\n", $term, $weight, $term_frequency, $doc_frequency);
    $total += $weight;
  }
  return $total;
}

function getDocFrequency($field, $term) {
  $term_response = json_decode(file_get_contents(sprintf(TERMS, $field, urlencode($term))));
  if ($term_response->terms->{$field}[0] == $term)
    return $term_response->terms->{$field}[1];
  else 
  	print_r($term_response);
    echo "for $term\n";
  return 0;
}

function getWeight($term_frequency, $doc_frequency, $N) {
  return $term_frequency * log10(1 + ($N/$doc_frequency));
}

function getTerms($field, $value) {
  $analysis_response = json_decode(file_get_contents(sprintf(ANALYSIS, $field, urlencode($value))));
  $analysis = end($analysis_response->analysis->field_names->{$field}->index);
  $terms = array();
  foreach ($analysis as $term) {
    if (isset($terms[$term->text]))
      $terms[$term->text]++;
    else
      $terms[$term->text] = 1;
  }
  return $terms;
}