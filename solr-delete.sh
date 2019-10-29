VERSION=v2019-10
curl -s http://localhost:8984/solr/$VERSION/update -H 'Content-type: text/xml' --data-binary '<delete><query>*:*</query></delete>'
curl -s http://localhost:8984/solr/$VERSION/update -H 'Content-type: text/xml' --data-binary '<commit/>'

