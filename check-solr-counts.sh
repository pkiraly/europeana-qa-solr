#!/usr/bin/env bash

VERSION=$1
if [[ "$VERSION" = "" ]]; then
  echo "Please specify a version"
  exit 1;
fi

echo "checking numbers in Solr index for $VERSION"

count_all=$(curl -s "http://localhost:8984/solr/$VERSION/select?q=id%3A*" | jq .response.numFound)
printf "all ids:\n\t%d\n" $count_all

count_completeness=$(curl -s "http://localhost:8984/solr/$VERSION/select?q=crd_PROVIDER_Proxy_rdf_about_i%3A%5B0%20TO%20*%5D" | jq .response.numFound)
printf "crd_PROVIDER_Proxy_rdf_about_i:[0 TO *]:\n\t%d\n" $count_completeness

count_multilinguality=$(curl -s "http://localhost:8984/solr/$VERSION/select?q=NumberOfLanguagesPerPropertyInProviderProxy_f%3A%5B0%20TO%20*%5D" | jq .response.numFound)
printf "NumberOfLanguagesPerPropertyInProviderProxy_f:[0 TO *]:\n\t%d\n" $count_completeness

count_languages=$(curl -s "http://localhost:8984/solr/v2019-10/select?q=languages_ss%3A*&rows=0" | jq .response.numFound)
printf "languages_ss:*:\n\t%d\n" $count_languages
