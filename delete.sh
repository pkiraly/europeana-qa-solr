#!/usr/bin/env bash

VERSION=qa-2019-03
curl "http://localhost:8984/solr/$VERSION/update" -H "Content-type: text/xml" --data-binary '<delete><query>*:*</query></delete>'
curl "http://localhost:8984/solr/$VERSION/update" -H "Content-type: text/xml" --data-binary '<commit />'
curl "http://localhost:8984/solr/$VERSION/update?optimize=true"
